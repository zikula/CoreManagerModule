<?php
/**
 * Copyright Zikula Foundation 2014 - Zikula Application Framework
 *
 * This work is contributed to the Zikula Foundation under one or more
 * Contributor Agreements and licensed to You under the following license:
 *
 * @license GNU/LGPv3 (or at your option any later version).
 * @package Zikula
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
 */

namespace Zikula\Module\CoreManagerModule\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Github\HttpClient\Message\ResponseMediator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use vierbergenlars\SemVer\version;
use Zikula\Common\Translator\TranslatorInterface;
use Zikula\Common\Translator\TranslatorTrait;
use Zikula\ExtensionsModule\Api\ApiInterface\VariableApiInterface;
use Zikula\Module\CoreManagerModule\Entity\CoreReleaseEntity;
use Zikula\Module\CoreManagerModule\Helper\AnnouncementHelper;
use Zikula\Module\CoreManagerModule\Helper\ClientHelper;

/**
 * Class ReleaseManager.
 */
class ReleaseManager
{
    use TranslatorTrait;

    /**
     * @var VariableApiInterface
     */
    private $variableApi;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var string
     */
    private $repo;

    private $client;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var ClientHelper
     */
    private $clientHelper;

    /**
     * @var AnnouncementHelper
     */
    private $announcementHelper;

    /**
     * @var bool
     */
    private $isMainInstance;

    /**
     * @param TranslatorInterface $translator
     * @param VariableApiInterface $variableApi
     * @param EntityManagerInterface $em
     * @param RouterInterface $router
     * @param EventDispatcherInterface $eventDispatcher
     * @param ClientHelper $clientHelper
     * @param AnnouncementHelper $announcementHelper
     */
    public function __construct(
        TranslatorInterface $translator,
        VariableApiInterface $variableApi,
        EntityManagerInterface $em,
        RouterInterface $router,
        EventDispatcherInterface $eventDispatcher,
        ClientHelper $clientHelper,
        AnnouncementHelper $announcementHelper
    ) {
        $this->variableApi = $variableApi;
        $this->client = $clientHelper->getGitHubClient();
        $this->em = $em;
        $this->repo = $variableApi->get('ZikulaCoreManagerModule', 'github_core_repo', 'zikula/core');
        $this->router = $router;
        $this->eventDispatcher = $eventDispatcher;
        $this->isMainInstance = $variableApi->get('ZikulaCoreManagerModule', 'is_main_instance', false);
        $this->setTranslator($translator);
        $this->clientHelper = $clientHelper;
        $this->announcementHelper = $announcementHelper;
    }

    public function setTranslator($translator)
    {
        $this->translator = $translator;
    }

    /**
     * This returns "significant" releases only. They are sorted by (1) state ASC and (2) version DESC.
     *
     * Example: given there is
     * - a prerelease 1.3.5-rc1
     * - an outdated release 1.3.5
     *
     * Only the outdated release will be returned as it "overweights" the prerelease.
     */
    public function getSignificantReleases($onlyNewestVersion = true)
    {
        // Get all the releases.
        $releases = $this->em->getRepository(CoreReleaseEntity::class)->findAll();

        // Create a version map. This makes it possible to check what kind of releases are available for one specific
        // version.
        $versionMap = [];
        foreach ($releases as $release) {
            // As the version could be 1.3.5-rc1, we need to transform them into x.y.z to be able to compare.
            $version = new version($release->getSemver());
            $version = $this->versionToMajorMinorPatch($version);
            $versionMap[$version][$release->getState()][] = $release->getId();
        }

        // This array will hold all the ids of versions we want to return.
        $ids = [];
        foreach ($versionMap as $version => $stateReleaseMap) {
            // Now check if there is a supported version. If so, ignore all the outdated versions, prereleases and
            // development versions. We only want to serve the supported version. If there isn't a supported version
            // but an outdated version, serve the outdated version but ignore all prereleases and development versions
            // and so on.
            // Make sure to show the development version even if there is a prerelease.
            $showDev = true;
            if (isset($stateReleaseMap[CoreReleaseEntity::STATE_SUPPORTED])) {
                // There is a supported core release for version x.y.z
                $ids[CoreReleaseEntity::STATE_SUPPORTED][$version][] = $stateReleaseMap[CoreReleaseEntity::STATE_SUPPORTED][0];
                $showDev = false;
            } else if (isset($stateReleaseMap[CoreReleaseEntity::STATE_OUTDATED])) {
                // There is an outdated core release for version x.y.z
                $ids[CoreReleaseEntity::STATE_OUTDATED][$version][] = $stateReleaseMap[CoreReleaseEntity::STATE_OUTDATED][0];
                $showDev = false;
            } else if (isset($stateReleaseMap[CoreReleaseEntity::STATE_PRERELEASE])) {
                // There is at least one prerelease core for version x.y.z
                // There might be multiple prereleases. Sort them by id and use the latest one.
                rsort($stateReleaseMap[CoreReleaseEntity::STATE_PRERELEASE]);
                $ids[CoreReleaseEntity::STATE_PRERELEASE][$version][] = $stateReleaseMap[CoreReleaseEntity::STATE_PRERELEASE][0];
            }
            if (isset($stateReleaseMap[CoreReleaseEntity::STATE_DEVELOPMENT]) && $showDev) {
                // There is at least one development core for version x.y.z
                // There might be multiple development cores. Sort them by id and use the latest one.
                rsort($stateReleaseMap[CoreReleaseEntity::STATE_DEVELOPMENT]);
                $ids[CoreReleaseEntity::STATE_DEVELOPMENT][$version][] = $stateReleaseMap[CoreReleaseEntity::STATE_DEVELOPMENT][0];
            }
        }

        if ($onlyNewestVersion) {
            // Make sure the newest core versions are at the first position in the arrays.
            foreach ($ids as $state => $versions) {
                krsort($ids[$state]);
            }
        }

        // Now filter out all the releases.
        $releases = array_filter($releases, function (CoreReleaseEntity $release) use ($ids, $onlyNewestVersion) {
            // Check if we want core releases with the state of the current release.
            if (!isset($ids[$release->getState()])) {
                return false;
            }
            // This is all the ids of releases we want for that specific state.
            $idList = $ids[$release->getState()];

            if ($onlyNewestVersion) {
                // We only want the newest version.
                $idList = current($idList);

                return in_array($release->getId(), $idList);
            }

            foreach ($idList as $version => $ids) {
                if (in_array($release->getId(), $ids)) {
                    return true;
                }
            }

            return false;
        });

        // Finally, sort all releases by (1) state ASC (meaning supported first, development last) and (2) by version
        // DESC  and (3) by release DESC.
        usort($releases, function (CoreReleaseEntity $a, CoreReleaseEntity $b) {
            $states = [$a->getState(), $b->getState()];
            if ($states[0] !== $states[1]) {
                return ($states[0] > $states[1]) ? 1 : -1;
            }
            $v1 = new version($a->getSemver());
            $v2 = new version($b->getSemver());
            $v1 = $v1->getMajor() . '.' . $v1->getMinor() . '.' . $v1->getPatch();
            $v2 = $v2->getMajor() . '.' . $v2->getMinor() . '.' . $v2->getPatch();
            if ($v1 !== $v2) {
                return version_compare($v2, $v1);
            }
            $ids = [$a->getId(), $b->getId()];
            if ($ids[0] !== $ids[1]) {
                return ($ids[0] > $ids[1]) ? -1 : 1;
            }

            return 0;
        });

        return $releases;
    }

    public function reloadReleases($createNewsArticles = true)
    {
        $this->reloadReleasesFromGitHub($createNewsArticles);

        return true;
    }

    /**
     * Update or add one specific release.
     *
     * @param array               $release           The release data from the GitHub api.
     * @param bool                $createNewsArticle Whether or not to create pending news articles for new releases.
     * @param CoreReleaseEntity[] $dbReleases        INTERNAL: used in self::reloadAllReleases()
     *
     * @return bool|CoreReleaseEntity False if it's a draft; true if a release is edited; the release itself if it's new.
     */
    public function updateGitHubRelease($release, $createNewsArticle = true, $dbReleases = null)
    {
        if ($release['draft']) {
            // Ignore drafts.
            return false;
        }
        $id = $release['id'];

        if ($release['prerelease']) {
            $state = CoreReleaseEntity::STATE_PRERELEASE;
        } else {
            $state = CoreReleaseEntity::STATE_SUPPORTED;
        }

        if (null === $dbReleases) {
            $dbRelease = $this->em->getRepository('ZikulaCoreManagerModule:CoreReleaseEntity')->findOneBy(['id' => $id]);
            if ($dbRelease) {
                $dbReleases[$id] = $dbRelease;
            } else {
                $dbReleases = [];
            }
        }

        if (!array_key_exists($id, $dbReleases)) {
            // This is a new release.
            $dbRelease = new CoreReleaseEntity($id);
            $mode = 'new';
        } else {
            $dbRelease = $dbReleases[$id];
            $mode = 'edit';
            if ($dbRelease->getState() === CoreReleaseEntity::STATE_OUTDATED) {
                // Make sure not to override the state if it has been set to "outdated".
                $state = CoreReleaseEntity::STATE_OUTDATED;
            }
        }

        $dbRelease->setName($release['name']);
        // Make sure to cast null to string if description is empty!
        $dbRelease->setDescription((string)$this->markdown($release['body']));
        $dbRelease->setSemver($release['tag_name']);
        $dbRelease->setSourceUrls(array (
            'zip' => $release['zipball_url'],
            'tar' => $release['tarball_url']
        ));
        $dbRelease->setState($state);

        $assets = [];
        foreach ($release['assets'] as $asset) {
            if ($asset['state'] != 'uploaded') {
                continue;
            }
            $assets[] = array (
                'name' => $asset['name'],
                'download_url' => $asset['browser_download_url'],
                'size' => $asset['size'],
                'content_type' => $asset['content_type']
            );
        }
        $dbRelease->setAssets($assets);

        if ($mode == 'new') {
            $this->em->persist($dbRelease);
        } else {
            $this->em->merge($dbRelease);
        }

        $this->em->flush();

        if ($mode == 'new' && $createNewsArticle) {
            $this->announcementHelper->createNewsArticle($dbRelease);
        } elseif (null !== $dbRelease->getNewsId()) {
            $this->announcementHelper->updateNewsArticle($dbRelease);
        }

        return true;
    }

    /**
     * @return CoreReleaseEntity[]
     */
    private function reloadReleasesFromGitHub($createNewsArticles)
    {
        $repo = explode('/', $this->repo);
        $releases = $this->client->api('repo')->releases()->all($repo[0], $repo[1]);
        /** @var CoreReleaseEntity[] $dbReleases */
        $_dbReleases = $this->em->getRepository(CoreReleaseEntity::class)->findAll();
        $dbReleases = [];
        foreach ($_dbReleases as $_dbRelease) {
            $dbReleases[$_dbRelease->getId()] = $_dbRelease;
        }
        unset($_dbReleases, $_dbRelease);

        // Make sure to always have at least the id "0" in the array, as the IN() SQL statement fails otherwise.
        $ids = [0];
        foreach ($releases as $release) {
            $ids[] = $release['id'];
            $this->updateGitHubRelease($release, $createNewsArticles, $dbReleases);
        }

        /** @var QueryBuilder $qb */
        $qb = $this->em->createQueryBuilder();
        $removedReleases = $qb->select('r')
            ->from('ZikulaCoreManagerModule:CoreReleaseEntity', 'r')
            ->where($qb->expr()->not($qb->expr()->in('r.id', implode(', ', $ids))))
            ->getQuery()->execute();

        foreach ($removedReleases as $removedRelease) {
            $this->em->remove($removedRelease);
        }

        $this->em->flush();
    }

    /**
     * "Markdownify" a text using GitHub's flavoured markdown (resulting in @ zikula and zikula/core#123 links).
     *
     * @param string $text The text to "markdownify"
     *
     * @return string
     */
    private function markdown($text)
    {
        $settings = [
            'text' => $text,
            'mode' => 'gfm',
            'context' => $this->repo
        ];

        $response = $this->client->getHttpClient()->post('markdown', json_encode($settings));

        return ResponseMediator::getContent($response);
    }

    /**
     * @param $version
     * @return string
     */
    private function versionToMajorMinorPatch($version)
    {
        return $version->getMajor() . '.' . $version->getMinor() . '.' . $version->getPatch();
    }
}
