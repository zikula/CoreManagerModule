<?php
/**
 * Copyright Zikula - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\Module\CoreManagerModule\Block;

use Zikula\BlocksModule\AbstractBlockHandler;
use Zikula\Module\CoreManagerModule\Block\Form\Type\ButtonBlockType;
use Zikula\Module\CoreManagerModule\Entity\CoreReleaseEntity;

class LatestReleaseBlock extends AbstractBlockHandler
{
    /**
     * {@inheritdoc}
     */
    public function display(array $properties)
    {
        if (!$this->hasPermission('ZikulaCoreManagerModule:LatestReleaseBlock:', "$properties[title]::", ACCESS_OVERVIEW)) {
            return "";
        }

        $releaseManager = $this->get('zikula_core_manager_module.releasemanager');
        $releases = $releaseManager->getSignificantReleases();

        $supportedReleases = array_filter($releases, function (CoreReleaseEntity $release) {
            return $release->getState() === CoreReleaseEntity::STATE_SUPPORTED;
        });
        if (empty($supportedReleases)) {
            return "";
        }

        return $this->renderView('@ZikulaCoreManagerModule/Blocks/latestrelease.html.twig', [
            'btnBlock' => $properties['btnBlock'],
            'supportedRelease' => current($supportedReleases),
            'id' => uniqid()
        ]);
    }

    public function getFormClassName()
    {
        return ButtonBlockType::class;
    }
}
