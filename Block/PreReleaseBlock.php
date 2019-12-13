<?php
/**
 * Copyright Zikula Foundation 2014 - Zikula Application Framework
 *
 * This work is contributed to the Zikula Foundation under one or more
 * Contributor Agreements and licensed to You under the following license:
 *
 * @license MIT
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
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
