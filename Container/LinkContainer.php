<?php

/*
 * This file is part of the ZikulaPagesModule package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\Module\CoreManagerModule\Container;

use Symfony\Component\Routing\RouterInterface;
use Zikula\Common\Translator\TranslatorInterface;
use Zikula\Core\LinkContainer\LinkContainerInterface;
use Zikula\PermissionsModule\Api\ApiInterface\PermissionApiInterface;

class LinkContainer implements LinkContainerInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var PermissionApiInterface
     */
    private $permissionApi;

    /**
     * LinkContainer constructor.
     * @param TranslatorInterface $translator
     * @param RouterInterface $router
     * @param PermissionApiInterface $permissionApi
     */
    public function __construct(
        TranslatorInterface $translator,
        RouterInterface $router,
        PermissionApiInterface $permissionApi
    ) {
        $this->translator = $translator;
        $this->router = $router;
        $this->permissionApi = $permissionApi;
    }

    public function getLinks($type = LinkContainerInterface::TYPE_ADMIN)
    {
        $links = [];
        if (LinkContainerInterface::TYPE_ADMIN == $type) {
            if ($this->permissionApi->hasPermission('ZikulaPagesModule::', '::', ACCESS_ADMIN)) {
                $links[] = array(
                    'url' => $this->router->generate('zikulacoremanagermodule_user_viewcorereleases'),
                    'text' => $this->translator->__('Core releases'),
                    'title' => $this->translator->__('View core releases'),
                    'icon' => 'th-list');

                $links[] = array(
                    'url' => $this->router->generate('zikulacoremanagermodule_release_wizard'),
                    'text' => $this->translator->__('Add release'),
                    'title' => $this->translator->__('Add new release'),
                    'icon' => 'wrench');

                $links[] = array(
                    'url' => $this->router->generate('zikulacoremanagermodule_admin_index'),
                    'text' => $this->translator->__('Settings'),
                    'title' => $this->translator->__('Edit settings'),
                    'icon' => 'wrench');

                $links[] = array(
                    'url' => $this->router->generate('zikulacoremanagermodule_admin_reloadcorereleases'),
                    'text' => $this->translator->__('Reload core releases'),
                    'title' => $this->translator->__('Reload all core releases'),
                    'icon' => 'gears');
            }
        }

        return $links;
    }

    public function getBundleName()
    {
        return 'ZikulaCoreManagerModule';
    }
}
