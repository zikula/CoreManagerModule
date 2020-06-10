<?php
/**
 * Copyright Zikula - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\Module\CoreManagerModule\Controller;

use Symfony\Component\Routing\Annotation\Route;
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
