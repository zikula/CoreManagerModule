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

use Zikula\Module\CoreManagerModule\AbstractButtonBlock;
use Zikula\Module\CoreManagerModule\Entity\CoreReleaseEntity;

class JenkinsBuildBlock extends AbstractButtonBlock
{
    /**
     * {@inheritdoc}
     */
    public function display(array $properties)
    {
        if (!$this->hasPermission('ZikulaCoreManagerModule:jenkinsBuild:', "$properties[title]::", ACCESS_OVERVIEW)) {
            return "";
        }

        $releaseManager = $this->get('zikula_core_manager_module.releasemanager');
        $releases = $releaseManager->getSignificantReleases(false);

        $developmentReleases = array_filter($releases, function (CoreReleaseEntity $release) {
            return $release->getState() === CoreReleaseEntity::STATE_DEVELOPMENT;
        });

        if (empty($developmentReleases)) {
            return "";
        }

        return $this->renderView('@ZikulaCoreManagerModuleModule/Blocks/jenkinsbuilds.html.twig', [
            'content' => $properties['content'],
            'developmentReleases', $developmentReleases,
            'id' => uniqid()
        ]);
    }
}
