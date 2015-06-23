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

use Github\HttpClient\Message\ResponseMediator;
use SecurityUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Zikula\Module\CoreManagerModule\Entity\CoreReleaseEntity;
use Zikula\Module\CoreManagerModule\Manager\ReleaseManager;
use Zikula\Module\CoreManagerModule\Util;

/**
 * @Route("/admin")
 *
 * UI operations executable by admins only.
 */
class AdminController extends \Zikula_AbstractController
{
    /**
     * @Route("")
     * @Method("GET")
     * The default entry point.
     *
     * @return Response
     * @throws AccessDeniedException
     */
    public function indexAction()
    {
        if (!SecurityUtil::checkPermission($this->name.'::', '::', ACCESS_ADMIN)) {
            throw new AccessDeniedException();
        }

        // Rate limit check
        $client = Util::getGitHubClient();
        $response = $client->getHttpClient()->get('rate_limit');
        $rate = ResponseMediator::getContent($response);
        $rate = $rate['rate'];

        $now = new \DateTime('now');
        $reset = \DateTime::createFromFormat('U', $rate['reset'], new \DateTimeZone('UTC'));
        $rate['minutesUntilReset'] = $now->diff($reset)->format('%i');

        $this->view->assign('rate', $rate);

        $this->view->assign('hasPushAccess', Util::hasGitHubClientPushAccess($client));

        $this->view->assign('settings', $this->getVars());

        return $this->response($this->view->fetch('Admin/modifyconfig.tpl'));
    }

    /**
     * @Route("")
     * @Method("POST")
     *
     * @return RedirectResponse
     * @throws AccessDeniedException
     */
    public function modifyConfigAction()
    {
        if (!SecurityUtil::checkPermission($this->name.'::', '::', ACCESS_ADMIN)) {
            throw new AccessDeniedException();
        }

        $modvars = $this->request->request->get('settings');
        $this->setVars($modvars);

        // Check if GitHub authentication works after changing token.
        $client = Util::getGitHubClient(false);

        if ($client === false) {
            $this->setVar('github_token', null);
            \LogUtil::registerError('GitHub token is invalid, authorization failed!');
        }

        return new RedirectResponse($this->get('router')->generate('zikulacoremanagermodule_admin_index'));
    }

    /**
     * @Route("/releases/toggle-state/{id}")
     * @ParamConverter(class="ZikulaCoreManagerModule:CoreReleaseEntity")
     */
    public function toggleReleaseStateAction(CoreReleaseEntity $release)
    {
        if (!SecurityUtil::checkPermission($this->name.'::', '::', ACCESS_MODERATE)) {
            throw new AccessDeniedException();
        }

        if ($release->getState() === CoreReleaseEntity::STATE_OUTDATED) {
            $release->setState(CoreReleaseEntity::STATE_SUPPORTED);
        } else if ($release->getState() === CoreReleaseEntity::STATE_SUPPORTED) {
            $release->setState(CoreReleaseEntity::STATE_OUTDATED);
        } else {
            throw new NotFoundHttpException('Cannot change release state - must be outdated or supported to change it!');
        }

        $this->entityManager->merge($release);
        $this->entityManager->flush();

        return new RedirectResponse($this->get('router')->generate('zikulacoremanagermodule_user_viewcorereleases'));
    }

    /**
     * @Route("/releases/reload")
     * @Method("GET")
     */
    public function reloadCoreReleasesAction()
    {
        if (!SecurityUtil::checkPermission($this->name.'::', '::', ACCESS_MODERATE)) {
            throw new AccessDeniedException();
        }

        return $this->response($this->view->fetch('Admin/reloadreleases.tpl'));
    }

    /**
     * @Route("/releases/reload")
     * @Method("POST")
     */
    public function doReloadCoreReleasesAction(Request $request)
    {
        if (!SecurityUtil::checkPermission($this->name.'::', '::', ACCESS_MODERATE)) {
            throw new AccessDeniedException();
        }

        /** @var ReleaseManager $releaseManager */
        $releaseManager = $this->get('zikulacoremanagermodule.releasemanager');
        $releaseManager->reloadReleases('all', (bool)$request->request->get('createnews', false));

        $request->getSession()->getFlashBag()->add('state', $this->__('Reloaded all core releases from GitHub.'));

        return new RedirectResponse($this->get('router')->generate('zikulacoremanagermodule_user_viewcorereleases'));
    }
}
