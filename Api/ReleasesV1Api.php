<?php
/**
 * Copyright Zikula - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\Module\CoreManagerModule\Api;

use Doctrine\ORM\EntityManager;
use Zikula\Module\CoreManagerModule\Helper\CoreReleaseEntityHelper;
use Zikula\Module\CoreManagerModule\Entity\CoreReleaseEntity;
use Zikula\Module\CoreManagerModule\Manager\ReleaseManager;

class ReleasesV1Api
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var ReleaseManager
     */
    protected $releaseManager;

    /**
     * @var CoreReleaseEntityHelper
     */
    private $entityHelper;

    public function __construct(
        EntityManager $entityManager,
        ReleaseManager $releaseManager,
        CoreReleaseEntityHelper $entityHelper
    ) {
        $this->em = $entityManager;
        $this->releaseManager = $releaseManager;
    }

    public function getSignificantReleases($onlyNewestVersion)
    {
        $releases = $this->releaseManager->getSignificantReleases($onlyNewestVersion);

        return array_map(function (CoreReleaseEntity $releaseEntity) {
            return $releaseEntity->toArray();
        }, $releases);
    }

    public function getReleaseStates()
    {
        $return = array();
        $states = array(
            CoreReleaseEntity::STATE_SUPPORTED,
            CoreReleaseEntity::STATE_OUTDATED,
            CoreReleaseEntity::STATE_PRERELEASE,
            CoreReleaseEntity::STATE_DEVELOPMENT
        );
        foreach ($states as $state) {
            $return[$state] =  array(
                'text' => $this->entityHelper->stateToText($state),
                'textPlural' => $this->entityHelper->stateToText($state, 'plural')
            );
        }
        return $return;
    }
}
