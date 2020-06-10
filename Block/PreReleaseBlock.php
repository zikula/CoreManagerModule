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

class PreReleaseBlock extends AbstractBlockHandler
{
    /**
     * {@inheritdoc}
     */
    public function display(array $properties)
    {
        if (!$this->hasPermission('ZikulaCoreManagerModule:PreReleaseBlock:', "$properties[title]::", ACCESS_OVERVIEW)) {
            return "";
        }

        $releaseManager = $this->get('zikula_core_manager_module.releasemanager');
        $releases = $releaseManager->getSignificantReleases();

        $preReleases = array_filter($releases, function (CoreReleaseEntity $release) {
            return $release->getState() === CoreReleaseEntity::STATE_PRERELEASE;
        });
        if (empty($preReleases)) {
            return "";
        }

        return $this->renderView('@ZikulaCoreManagerModule/Blocks/prerelease.html.twig', [
            'btnBlock' => $properties['btnBlock'],
            'preRelease' => current($preReleases),
            'id' => uniqid()
        ]);
    }

    public function getFormClassName()
    {
        return ButtonBlockType::class;
    }
}
