<?php

namespace Zikula\Module\CoreManagerModule\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use vierbergenlars\SemVer\version;
use Zikula\Module\CoreManagerModule\Manager\GitHubApiWrapper;

class CoreVersionAndBranchType extends AbstractType
{
    /**
     * @var GitHubApiWrapper
     */
    private $gitHubApiWrapper;

    /**
     * {@inheritdoc}
     */
    public function __construct(GitHubApiWrapper $gitHubApiWrapper)
    {
        $this->gitHubApiWrapper = $gitHubApiWrapper;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $versions = $this->getAllowedCoreVersions();
        $builder->add('version', 'choice', [
            'label' => __('Core version', 'ZikulaCoreManagerModule'),
            'label_attr' => ['class' => 'col-sm-3'],
            'choice_list' => new ChoiceList($versions, $versions),
        ])->add('next', 'submit', [
            'label' => __('Next', 'ZikulaCoreManagerModule'),
        ]);
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return 'coreVersionAndBranch';
    }

    /**
     * @return array
     */
    private function getAllowedCoreVersions()
    {
        $releases = $this->gitHubApiWrapper->getReleases();
        if (count($releases) == 0) {
            return [
                '"0.0.1-rc1'
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
            if ($currentPreRelease = self::vIsPreRelease($version)) {
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
        if (self::vIsPreRelease($allowedCoreVersions[0]) === false) {
            $extraVersions[] = new version(($allowedCoreVersions[0]->getMajor() + 1) . ".0.0-rc1");
        }

        $majorPrefix = null;
        foreach ($allowedCoreVersions as $allowedCoreVersion) {
            if ($allowedCoreVersion->getMajor() !== $majorPrefix) {
                $majorPrefix = $allowedCoreVersion->getMajor();
                if (self::vIsPreRelease($allowedCoreVersion) !== false) {
                    $extraVersions[] = new version($allowedCoreVersion->getMajor() . "." . ($allowedCoreVersion->getMinor() + 1) . ".0-rc1");
                }
            }
        }
        $versions = array_merge($extraVersions, $allowedCoreVersions);

        foreach ($versions as $key => $version) {
            $versions[$key] = $version->getVersion();
        }

        return $versions;
    }

    private static function versionToMajorMinorPatch(version $version)
    {
        return $version->getMajor() . "." . $version->getMinor() . "." . $version->getPatch();
    }

    private static function vIsPreRelease(version $version)
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
