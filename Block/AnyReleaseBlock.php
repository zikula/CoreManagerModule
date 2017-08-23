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
use Zikula\Module\CoreManagerModule\Block\Form\Type\AnyReleaseBlockType;
use Zikula\Module\CoreManagerModule\Entity\CoreReleaseEntity;

class AnyReleaseBlock extends AbstractBlockHandler
{
    /**
     * {@inheritdoc}
     */
    public function display(array $properties)
    {
        if (!$this->hasPermission('ZikulaCoreManagerModule:jenkinsBuild:', "$properties[title]::", ACCESS_OVERVIEW)) {
            return "";
        }
        /** @var \Symfony\Bridge\Doctrine\ManagerRegistry $em */
        $em = $this->get('doctrine')->getManager();
        $release = $em->getRepository('ZikulaCoreManagerModule:CoreReleaseEntity')->find($properties['release']);

        return $this->renderView('@ZikulaCoreManagerModule/Blocks/anyrelease.html.twig', [
            'btnBlock' => $properties['btnBlock'],
            'release' => $release,
            'id' => uniqid()
        ]);
    }

    public function getFormClassName()
    {
        return AnyReleaseBlockType::class;
    }

    public function getFormOptions()
    {
        $releaseManager = $this->get('zikula_core_manager_module.releasemanager');
        /** @var CoreReleaseEntity[] $releases */
        $releases = $releaseManager->getSignificantReleases(false);
        $choices = [];
        foreach ($releases as $release) {
            if ($release->getState() === CoreReleaseEntity::STATE_SUPPORTED) {
                $choices[$release->getName()] = $release->getId();
            }
        }

        return ['choices' => $choices];
    }
}
