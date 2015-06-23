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

class PreReleaseBlock extends AbstractButtonBlock
{
    /**
     * initialise block
     */
    public function init()
    {
        SecurityUtil::registerPermissionSchema('ZikulaCoreManagerModule:preRelease:', 'Block title::');
    }

    /**
     * get information on block
     */
    public function info()
    {
        return array(
            'text_type' => 'preRelease',
            'module' => 'ZikulaCoreManagerModule',
            'text_type_long' => $this->__('Pre release button'),
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
        if (!SecurityUtil::checkPermission('ZikulaCoreManagerModule:preRelease:', "$blockinfo[title]::", ACCESS_OVERVIEW) || !ModUtil::available('ZikulaCoreManagerModule')) {
            return "";
        }
        parent::display($blockinfo);

        $releaseManager = $this->get('zikulacoremanagermodule.releasemanager');
        $releases = $releaseManager->getSignificantReleases();

        $preReleases = array_filter($releases, function (CoreReleaseEntity $release) {
            return $release->getState() === CoreReleaseEntity::STATE_PRERELEASE;
        });
        if (empty($preReleases)) {
            return "";
        }
        $this->view->assign('preRelease', current($preReleases));
        $this->view->assign('id', uniqid());
        $blockinfo['content'] = $this->view->fetch('Blocks/prerelease.tpl');

        return BlockUtil::themeBlock($blockinfo);
    }
}
