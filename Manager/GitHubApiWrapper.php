<?php
/**
 * Copyright Zikula - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\Module\CoreManagerModule\Manager;

use Github\Api\Issue\Labels;
use Github\HttpClient\Message\ResponseMediator;
use Github\ResultPager;
use Zikula\ExtensionsModule\Api\ApiInterface\VariableApiInterface;
use Zikula\Module\CoreManagerModule\Helper\ClientHelper;
use vierbergenlars\SemVer\SemVerException;
use vierbergenlars\SemVer\version;

class GitHubApiWrapper
{
    /**
     * @var VariableApiInterface
     */
    private $variableApi;

    protected $githubClient;
    protected $coreRepository;
    protected $coreOrganization;
    protected $distRepository;
    protected $distOrganization;

    public function __construct(
        VariableApiInterface $variableApi,
        ClientHelper $clientHelper
    ) {
        $this->variableApi = $variableApi;
        $this->githubClient = $clientHelper->getGitHubClient(false);
        $core = explode('/', $this->variableApi->get('ZikulaCoreManagerModule', 'github_core_repo'));
        $this->coreOrganization = $core[0];
        $this->coreRepository = $core[1];
        $dist = explode('/', $this->variableApi->get('ZikulaCoreManagerModule', 'github_dist_repo'));
        $this->distOrganization = $dist[0];
        $this->distRepository = $dist[1];
    }

    public function getLastNCommitsOfBranch($repoType, $branch, $n)
    {
        $commits = $this->githubClient->repository()->commits()->all(
            'dist' === $repoType ? $this->distOrganization : $this->coreOrganization,
            'dist' === $repoType ? $this->distRepository : $this->coreRepository,
            [
                'sha' => $branch
            ]
        );

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
            return [
                'version' => new version($release['tag_name']),
                'branch' => $release['target_commitish']
            ];
        }, $releases);
        usort($releases, function ($a, $b) {
            return version::rcompare($a['version'], $b['version']);
        });

        foreach ($releases as $release) {
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
                '0.0.1-RC1',
                '0.1.0-RC1',
                '1.0.0-RC1'
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
            if (false !== ($currentPreRelease = self::versionIsPreRelease($version))) {
                // Seems like the newest released version is a pre release.
                // Allow to either release the final version or another pre release.
                $allowedCoreVersions[] = new version(self::versionToMajorMinorPatch($version));
                $allowedCoreVersions[] = new version(self::versionToMajorMinorPatch($version) . '-RC' . ++$currentPreRelease);
            } else {
                // This is a full version. Allow to release a higher version if the previous version isn't equal to
                // the higher one.
                if (!is_int($version->getPatch())) {
                    throw new \RuntimeException('The patch number of the ' . $version->getVersion() . ' version must be an integer.');
                }
                $newPatchVersion = ((int)$version->getPatch()) + 1;
                $newVersion = $version->getMajor() . '.' . $version->getMinor() . '.' . $newPatchVersion;
                if ($newVersion != $lastVersion) {
                    // give choice of releasing RC or full patch without RC
                    //$allowedCoreVersions[] = new version($newVersion . '-RC1');
                    $allowedCoreVersions[] = new version($newVersion);
                }
            }
        }
        usort($allowedCoreVersions, function (version $a, version $b) {
            return version::rcompare($a, $b);
        });

        // Now add all the new possible versions.
        // First of, allow a new major version
        $extraVersions[] = new version(($allowedCoreVersions[0]->getMajor() + 1) . '.0.0-RC1');

        $majorPrefix = null;
        foreach ($allowedCoreVersions as $allowedCoreVersion) {
            if ($allowedCoreVersion->getMajor() !== $majorPrefix) {
                $majorPrefix = $allowedCoreVersion->getMajor();
                if (false !== self::versionIsPreRelease($allowedCoreVersion)) {
                    $extraVersions[] = new version($allowedCoreVersion->getMajor() . '.' . ($allowedCoreVersion->getMinor() + 1) . '.0-RC1');
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
        return $version->getMajor() . '.' . $version->getMinor() . '.' . $version->getPatch();
    }

    public function versionIsPreRelease(version $version)
    {
        if (count($version->getPrerelease()) > 1) {
            throw new \RuntimeException('Unexpected pre release string.');
        }
        if (count($version->getPrerelease()) == 0) {
            return false;
        }
        $pattern = '/^rc(\d+)$/i';
        preg_match($pattern, $version->getPrerelease()[0], $matches);
        if (count($matches) != 2 || strlen($matches[1]) == 0) {
            throw new \RuntimeException('The pre release suffix of the ' . $version->getVersion() . ' tag does not match the RegExp ' . $pattern);
        }

        return $matches[1];
    }

    /**
     * @return array
     */
    public function getAvailableTags()
    {
        $tagInfo = $this->githubClient->repository()->tags($this->coreOrganization, $this->coreRepository);
        $tags = [];
        foreach ($tagInfo as $tag) {
            $tags[] = $tag['name'];
        }

        return $tags;
    }

    public function createDistributionTag($tag, $message, $shaObject)
    {
        $tagData = [
            'tag' => $tag,
            'message' => $message,
            'object' => $shaObject,
            'type' => 'commit',
            'tagger' => [
                'name' => 'zikula-bot',
                'email' => 'info@ziku.la',
                'date' => date('c')
            ]
        ];

        $tag = $client->api('gitData')->tags()->create($this->distOrganization, $this->distRepository, $tagData);
    }

    public function createIssue($title, $body, $milestone, $labels)
    {
        if ($milestone != null) {
            $milestone = $milestone['number'];
        }
        $this->makeSureLabelsExist($labels);

        return $this->githubClient->issues()->create($this->coreOrganization, $this->coreRepository, [
            'title' => $title,
            'body' => $body,
            'milestone' => $milestone,
            'labels' => $labels
        ]);
    }

    public function updateIssue($id, $title, $body)
    {
        $update = [];
        if ($title !== null) {
            $update['title'] = $title;
        }
        if ($body !== null) {
            $update['body'] = $body;
        }

        return $this->githubClient->issues()->update($this->coreOrganization, $this->coreRepository, $id, $update);
    }

    public function createRelease($repoType, $title, $body, $preRelease, $tag, $target)
    {
        return $this->githubClient->repo()->releases()->create(
            'dist' === $repoType ? $this->distOrganization : $this->coreOrganization,
            'dist' === $repoType ? $this->distRepository : $this->coreRepository,
            [
                'tag_name' => $tag,
                'target_commitish' => $target,
                'name' => $title,
                'body' => $body,
                'draft' => false,
                'prerelease' => $preRelease
            ]
        );
    }

    public function getMilestoneByCoreVersion(version $version)
    {
        // Remove pre release from version.
        $version = new version($version->getMajor() . '.' . $version->getMinor() . '.' . $version->getPatch());

        // Look through open milestones.
        $milestones = $this->githubClient->issues()->milestones()->all($this->coreOrganization, $this->coreRepository, ['state' => 'open']);
        foreach ($milestones as $milestone) {
            $milestoneTitle = $milestone['title'];
            try {
                if (version::eq($version, new version($milestoneTitle))) {
                    return $milestone;
                }
            } catch (SemVerException $exception) {
                // skip milestone
            }
        }

        return null;
    }

    public function downloadReleaseAssets($artifactsDownloadUrl)
    {
        // convert public URL to API route
        $urlParts = parse_url($artifactsDownloadUrl);
        $urlParts = $urlParts['path'];
        $urlParts = explode('/', $urlParts);
        $artifactId = array_pop($urlParts);
        $artifactRoute = 'repos/' . $urlParts[1] . '/' . $urlParts[2] . '/actions/artifacts/' . $artifactId . '/zip';

        // download file
        $zipPath = tempnam(sys_get_temp_dir(), 'asset-download');
        $client = $this->githubClient->getHttpClient();
        $response = $client->get($artifactRoute);
        $content = ResponseMediator::getContent($response);
        file_put_contents($zipPath, $content);

        return $zipPath;
    }

    public function createReleaseAssets($repoType, $releaseId, $zipPath)
    {
        // open zip file
        $zip = new ZipArchive;
        if (true !== $zip->open($zipPath)) {
            return false;
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $fileName = $zip->getNameIndex($i);
            $fileInfo = pathinfo($fileName);
            $fileExtension = $fileInfo['extension'];
            $contentType = null;
            switch ($fileExtension) {
                case 'zip':
                    $contentType = 'application/zip';
                    break;
                case 'gz':
                    $contentType = 'application/gzip';
                    break;
                case 'txt':
                    $contentType = 'text/plain';
                    break;
                default:
                    $contentType = null;
            }
            if (!$asset['content_type']) {
                // GitHub won't allow us to upload files without specifying the content type.
                // Skip those files (but there shouldn't be any).
                continue;
            }
            $asset = [
                'name' => $fileName,
                'download_url' => $downloadUrl,
                'file_content' => file_get_contents('zip://' . $zipPath . '#' . $fileName)
            ];

            $return = $this->createReleaseAsset($repoType, $releaseId, $asset);
            if (!isset($return['id'])) {
                $zip->close();                  

                return false;
            }
            $result = true;
        }
        $zip->close();                  

        return true;
    }

    private function createReleaseAsset($repoType, $releaseId, $asset)
    {
        return $this->githubClient->repo()->releases()->assets()->create(
            'dist' === $repoType ? $this->distOrganization : $this->coreOrganization,
            'dist' === $repoType ? $this->distRepository : $this->coreRepository,
            $releaseId,
            $asset['name'],
            $asset['content_type'],
            $asset['file_content']
        );
    }

    public function closeMilestone($milestone)
    {
        return $this->githubClient->issues()->milestones()->update($this->coreOrganization, $this->coreRepository, $milestone['number'], [
            'state' => 'closed'
        ]);
    }

    public function getFile($file, $ref)
    {
        $exists = $this->githubClient->repo()->contents()->exists($this->coreOrganization, $this->coreRepository, $file, $ref);
        if (!$exists) {
            return false;
        }
        return $this->githubClient->repo()->contents()->download($this->coreOrganization, $this->coreRepository, $file, $ref);
    }

    public function updateFile($path, $content, $commitMessage, $ref)
    {
        return $this->githubClient->repo()->contents()->update($this->coreOrganization, $this->coreRepository, $path, $content, $commitMessage, $ref);
    }

    /**
     * @param array $labels
     */
    private function makeSureLabelsExist($labels)
    {
        $paginator = new ResultPager($this->githubClient);
        $definedLabels = $paginator->fetchAll(new Labels($this->githubClient), 'all', [$this->coreOrganization, $this->coreRepository]);
        $definedLabelNames = array_column($definedLabels, 'name');

        foreach ($labels as $label) {
            if (!in_array($label, $definedLabelNames)) {
                $this->githubClient->issues()->labels()->create($this->coreOrganization, $this->coreRepository, ['name' => $label, 'color' => 'cdcdcd']);
            }
        }
    }
}
