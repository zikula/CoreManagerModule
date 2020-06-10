<?php
/**
 * Copyright Zikula - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
        if (!$this->hasPermission('ZikulaCoreManagerModule:AnyReleaseBlock:', "$properties[title]::", ACCESS_OVERVIEW)) {
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
