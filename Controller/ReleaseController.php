<?php
/**
 * Created by PhpStorm.
 * User: Christian
 * Date: 21.07.2015
 * Time: 19:39
 */

namespace Zikula\Module\CoreManagerModule\Controller;


use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Zikula\Component\Wizard\FormHandlerInterface;
use Zikula\Component\Wizard\Wizard;
use Zikula\Component\Wizard\WizardCompleteInterface;
use Zikula\Core\Controller\AbstractController;

class ReleaseController extends AbstractController
{
    /**
     * @Route("/add-release/{stage}")
     * @param Request $request
     * @param $stage
     * @return RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function wizardAction(Request $request, $stage = null)
    {
        if (!\SecurityUtil::checkPermission('ZikulaCoreManagerModule:addRelease:', '::', ACCESS_ADD)) {
            throw new AccessDeniedException();
        }
        $request->attributes->set('_legacy', true);

        $wizard = new Wizard($this->container, realpath(__DIR__ . '/../Resources/config/release-stages.yml'));
        $currentStage = $wizard->getCurrentStage($stage);
        if ($currentStage instanceof WizardCompleteInterface) {
            return $currentStage->getResponse($request);
        }
        if ($wizard->isHalted()) {
            $request->getSession()->getFlashBag()->add('danger', $wizard->getWarning());

            // @todo..
            return $this->container->get('templating')->renderResponse('');
        }
        $templateParams = $currentStage->getTemplateParams();
        if ($currentStage instanceof FormHandlerInterface) {
            $form = $this->container->get('form.factory')->create($currentStage->getFormType());
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $currentStage->handleFormResult($form);
                $url = $this->container->get('router')->generate('zikulacoremanagermodule_release_wizard', array('stage' => $wizard->getNextStage()->getName()), true);

                return new RedirectResponse($url);
            }
            $templateParams['form'] = $form->createView();
        }

        return $this->get('templating')->renderResponse($currentStage->getTemplateName(), $templateParams);
    }
}
