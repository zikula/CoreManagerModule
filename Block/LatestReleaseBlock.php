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

use BlockUtil;
use ModUtil;
use SecurityUtil;
use Zikula\Module\CoreManagerModule\AbstractButtonBlock;
use Zikula\Module\CoreManagerModule\Entity\CoreReleaseEntity;

class LatestReleaseBlock extends AbstractButtonBlock
{
    /**
     * initialise block
     */
    public function init()
    {
        SecurityUtil::registerPermissionSchema('ZikulaCoreManagerModule:latestRelease:', 'Block title::');
    }

    /**
     * get information on block
     */
    public function info()
    {
        return array(
            'text_type' => 'latestRelease',
            'module' => 'ZikulaCoreManagerModule',
            'text_type_long' => $this->__('Latest release button'),
            'allow_multiple' => true,
            'form_content' => false,
            'form_refresh' => false,
            'show_preview' => true,
            'admin_tableless' => true
        );
    }

    /**
     * {@inheritdoc}
     */
    public function display($blockinfo)
    {
        if (!SecurityUtil::checkPermission('ZikulaCoreManagerModule:latestRelease:', "$blockinfo[title]::", ACCESS_OVERVIEW) || !ModUtil::available('ZikulaCoreManagerModule')) {
            return "";
        }
        parent::display($blockinfo);

        $releaseManager = $this->get('zikulacoremanagermodule.releasemanager');
        $releases = $releaseManager->getSignificantReleases();

        $supportedReleases = array_filter($releases, function (CoreReleaseEntity $release) {
            return $release->getState() === CoreReleaseEntity::STATE_SUPPORTED;
        });
        if (empty($supportedReleases)) {
            return "";
        }
        $this->view->assign('supportedRelease', current($supportedReleases));
        $this->view->assign('id', uniqid());
        $blockinfo['content'] = $this->view->fetch('Blocks/latestrelease.tpl');

        return BlockUtil::themeBlock($blockinfo);
    }
}
