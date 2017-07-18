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

namespace Zikula\Module\CoreManagerModule\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Zikula\Core\Controller\AbstractController;

/**
 * UI operations executable by general users.
 */
class UserController extends AbstractController
{
    /**
     * @Route("/download", options={"zkNoBundlePrefix" = 1})
     */
    public function viewCoreReleasesAction()
    {
        $releaseManager = $this->get('zikula_core_manager_module.releasemanager');

        return $this->render('@ZikulaCoreManagerModule/User/viewreleases.html.twig', [
            'releases' => $releaseManager->getSignificantReleases(false)
        ]);
    }
}
