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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Zikula\Core\Controller\AbstractController;
use Zikula\Module\CoreManagerModule\Entity\CoreReleaseEntity;
use Zikula\Module\CoreManagerModule\Form\Type\ConfigType;
use Zikula\Module\CoreManagerModule\Form\Type\ConfirmReloadType;
use Zikula\Module\CoreManagerModule\Manager\ReleaseManager;
use Zikula\ThemeModule\Engine\Annotation\Theme;

/**
 * @Route("/admin")
 *
 * UI operations executable by admins only.
 */
class AdminController extends AbstractController
{
    /**
     * @Route("")
     * @Theme("admin")
     *
     * @return Response
     * @throws AccessDeniedException
     */
    public function indexAction(Request $request)
    {
        if (!$this->hasPermission($this->name.'::', '::', ACCESS_ADMIN)) {
            throw new AccessDeniedException();
        }

        // Rate limit check
        $client = $this->container->get('zikula_core_manager_module.client_helper')->getGitHubClient();
        $response = $client->getHttpClient()->get('rate_limit');
        $rate = ResponseMediator::getContent($response);
        $rate = $rate['rate'];

        $now = new \DateTime('now');
        $reset = \DateTime::createFromFormat('U', $rate['reset'], new \DateTimeZone('UTC'));
        $rate['minutesUntilReset'] = $now->diff($reset)->format('%i');

        $form = $this->createForm(ConfigType::class, $this->getVars());
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->setVars($form->getData());
            $this->addFlash('info', $this->__('Settings updated!'));
            $client = $this->container->get('zikula_core_manager_module.client_helper')->getGitHubClient(false);
            if ($client === false) {
                $this->setVar('github_token', null);
                $this->addFlash('error', 'GitHub token is invalid, authorization failed!');
            }
        }

        return $this->render('@ZikulaCoreManagerModule/Admin/modifyconfig.html.twig', [
            'form' => $form->createView(),
            'rate' => $rate,
            'hasPushAccess' => $this->container->get('zikula_core_manager_module.client_helper')->hasGitHubClientPushAccess($client),
        ]);
    }

    /**
     * @Route("/releases/toggle-state/{id}")
     * @ParamConverter(class="ZikulaCoreManagerModule:CoreReleaseEntity")
     */
    public function toggleReleaseStateAction(CoreReleaseEntity $release)
    {
        if (!$this->hasPermission($this->name.'::', '::', ACCESS_MODERATE)) {
            throw new AccessDeniedException();
        }

        if ($release->getState() === CoreReleaseEntity::STATE_OUTDATED) {
            $release->setState(CoreReleaseEntity::STATE_SUPPORTED);
        } else if ($release->getState() === CoreReleaseEntity::STATE_SUPPORTED) {
            $release->setState(CoreReleaseEntity::STATE_OUTDATED);
        } else {
            throw new NotFoundHttpException('Cannot change release state - must be outdated or supported to change it!');
        }

        $this->getDoctrine()->getManager()->merge($release);
        $this->getDoctrine()->getManager()->flush();

        return $this->redirectToRoute('zikulacoremanagermodule_user_viewcorereleases');
    }

    /**
     * @Route("/releases/reload")
     * @Theme("admin")
     */
    public function reloadCoreReleasesAction(Request $request)
    {
        if (!$this->hasPermission($this->name.'::', '::', ACCESS_MODERATE)) {
            throw new AccessDeniedException();
        }
        $form = $this->createForm(ConfirmReloadType::class, [], [
            'translator' => $this->getTranslator()
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var ReleaseManager $releaseManager */
            $releaseManager = $this->get('zikula_core_manager_module.releasemanager');
            $releaseManager->reloadReleases($form->get('createnews')->getData());
            $this->addFlash('info', $this->__('Reloaded all core releases from GitHub.'));

            return $this->redirectToRoute('zikulacoremanagermodule_user_viewcorereleases');
        }

        return $this->render('@ZikulaCoreManagerModule/Admin/reloadreleases.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
