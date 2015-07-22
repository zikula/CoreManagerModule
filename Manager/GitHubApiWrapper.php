<?php

namespace Zikula\Module\CoreManagerModule\Manager;

use Github\HttpClient\Message\ResponseMediator;
use Zikula\Module\CoreManagerModule\Util;
use vierbergenlars\SemVer\version;

class GitHubApiWrapper
{
    protected $githubClient;
    protected $core;
    protected $coreRepository;
    protected $coreOrganization;

    public function __construct()
    {
        $this->githubClient = Util::getGitHubClient(false);
        $this->core = $core = \ModUtil::getVar('ZikulaCoreManagerModule', 'github_core_repo');
        $core = explode('/', $core);
        $this->coreOrganization = $core[0];
        $this->coreRepository = $core[1];
    }

    public function getLastNCommitsOfBranch($branch, $n)
    {
        $commits = $this->githubClient->repository()->commits()->all($this->coreOrganization, $this->coreRepository, array('sha' => $branch));

        return array_slice($commits, 0, $n);
    }

    public function getBranchOfPreviousRelease($tag)
    {
        $previousRelease = $this->getPreviousRelease($tag);
        if ($previousRelease === null) {
            return null;
        }

        return $previousRelease['branch'];
    }

    /**
     * @param string $tag The tag to check
     * @return null|array The version and the branch the tag is based on. Can be null.
     */
    private function getPreviousRelease($tag)
    {
        $releases = $this->githubClient->repository()->releases()->all($this->coreOrganization, $this->coreRepository);
        $releases = array_map(function ($release) {
            return ['version' => new version($release['tag_name']), 'branch' => $release['target_commitish']];
        }, $releases);
        usort($releases, function ($a, $b) {
            return version::rcompare($a['version'], $b['version']);
        });

        foreach($releases as $release) {
            if (version::lt($release['version'], $tag)) {
                return $release;
            }
        }

        return null;
    }

    /**
     * @return array
     */
    public function getBranches()
    {
        $branches = $this->githubClient->repository()->branches($this->coreOrganization, $this->coreRepository);

        return array_column($branches, 'name');
    }

    /**
     * @return array
     */
    public function getAllowedCoreVersions()
    {
        $releases = $this->githubClient->repository()->releases()->all($this->coreOrganization, $this->coreRepository);
        if (count($releases) == 0) {
            return [
                '0.0.1-rc1',
                '0.1.0-rc1',
                '1.0.0-rc1'
            ];
        }

        /**
         * @var $semVerVersions version[]
         */
        $semVerVersions = [];
        foreach ($releases as $release) {
            $semVerVersions[] = new version($release['tag_name']);
        }
        usort($semVerVersions, function (version $a, version $b) {
            return version::rcompare($a, $b);
        });

        /**
         * @var $allowedCoreVersions version[]
         */
        $allowedCoreVersions = [];
        /**
         * @var $extraVersions version[]
         */
        $extraVersions = [];
        $currentVersion = null;
        $lastVersion = null;
        foreach ($semVerVersions as $version) {
            if (self::versionToMajorMinorPatch($version) === $currentVersion) {
                continue;
            } else {
                $lastVersion = $currentVersion;
                $currentVersion = self::versionToMajorMinorPatch($version);
            }
            if ($currentPreRelease = self::versionIsPreRelease($version)) {
                // Seems like the newest released version is a pre release.
                // Allow to either release the final version or another pre release.
                $allowedCoreVersions[] = new version(self::versionToMajorMinorPatch($version));
                $allowedCoreVersions[] = new version(self::versionToMajorMinorPatch($version) . "-rc" . ++$currentPreRelease);
            } else {
                // This is a full version. Allow to release a higher version if the previous version isn't equal to
                // the higher one.
                if (!is_int($version->getPatch())) {
                    throw new \RuntimeException("The patch number of the " . $version->getVersion() . " version must be an integer.");
                }
                $newPatchVersion = ((int)$version->getPatch()) + 1;
                $newVersion = $version->getMajor() . "." . $version->getMinor() . "." . $newPatchVersion;
                if ($newVersion != $lastVersion) {
                    $allowedCoreVersions[] = new version($newVersion . "-rc1");
                }
            }
        }
        usort($allowedCoreVersions, function (version $a, version $b) {
            return version::rcompare($a, $b);
        });

        // Now add all the new possible versions.
        // First of, allow a new major version, if the highest version isn't a pre release.
        if (self::versionIsPreRelease($allowedCoreVersions[0]) === false) {
            $extraVersions[] = new version(($allowedCoreVersions[0]->getMajor() + 1) . ".0.0-rc1");
        }

        $majorPrefix = null;
        foreach ($allowedCoreVersions as $allowedCoreVersion) {
            if ($allowedCoreVersion->getMajor() !== $majorPrefix) {
                $majorPrefix = $allowedCoreVersion->getMajor();
                if (self::versionIsPreRelease($allowedCoreVersion) !== false) {
                    $extraVersions[] = new version($allowedCoreVersion->getMajor() . "." . ($allowedCoreVersion->getMinor() + 1) . ".0-rc1");
                }
            }
        }
        /**
         * @var $versions version[]
         */
        $versions = array_merge($extraVersions, $allowedCoreVersions);

        foreach ($versions as $key => $version) {
            $versions[$key] = $version->getVersion();
        }

        return $versions;
    }

    public function versionToMajorMinorPatch(version $version)
    {
        return $version->getMajor() . "." . $version->getMinor() . "." . $version->getPatch();
    }

    public function versionIsPreRelease(version $version)
    {
        if (count($version->getPrerelease()) > 1) {
            throw new \RuntimeException('Unexpected pre release string.');
        }
        if (count($version->getPrerelease()) == 0) {
            return false;
        }
        $pattern = '/^rc(\d+)$/';
        preg_match($pattern, $version->getPrerelease()[0], $matches);
        if (count($matches) != 2 || strlen($matches[1]) == 0) {
            throw new \RuntimeException('The pre release suffix of the ' . $version->getVersion() . ' tag does not match the RegExp ' . $pattern);
        }
        return $matches[1];
    }
}
