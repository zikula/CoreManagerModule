<?php
/**
 * Created by PhpStorm.
 * User: Christian
 * Date: 21.07.2015
 * Time: 19:39
 */

namespace Zikula\Module\CoreManagerModule\Controller;


use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Zikula\Component\Wizard\FormHandlerInterface;
use Zikula\Component\Wizard\Wizard;
use Zikula\Component\Wizard\WizardCompleteInterface;
use Zikula\Core\Controller\AbstractController;
use Zikula\Core\Response\PlainResponse;
use Zikula\Module\CoreManagerModule\Settings;

/**
 * @Route("/admin")
 */
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

    /**
     * @Route("/add-release/ajax", options={"i18n"=false,"expose"=true})
     * @Method("POST")
     *
     * @param Request $request
     * @return PlainResponse
     */
    public function ajax(Request $request)
    {
        if (!\SecurityUtil::checkPermission('ZikulaCoreManagerModule:addRelease:', '::', ACCESS_ADD)) {
            throw new AccessDeniedException();
        }
        $stage = $request->request->get('stage', false);
        if ($stage === false) {
            throw new \RuntimeException('No stage parameter received.');
        }
        $data = \UserUtil::getVar('ZikulaCoreManagerModule_release');
        if (empty($data) || $data === "null" || $data === "false" || $data === "Array") {
            throw new \RuntimeException('Could not decode user data.');
        }
        $data = json_decode($data, true);
        $jenkinsApiWrapper = $this->get('zikula_core_manager_module.jenkins_api_wrapper');
        switch ($stage) {
            case 'promote-build':
                $jenkinsApiWrapper->promoteBuild($data['job'], $data['build'], $data['isPreRelease'] ? Settings::RELEASE_CANDIDATE_PROMOTION_ID : Settings::RELEASE_PROMOTION_ID);
                break;
            case 'lock-build':
                $jenkinsApiWrapper->lockBuild($data['job'], $data['build']);
                break;
            case 'add-build-description':
                if ($data['isPreRelease']) {
                    $description = 'Release Candidate ' . $data['preRelease'];
                } else {
                    $description = 'Release ' . $data['version'];
                }
                $jenkinsApiWrapper->setBuildDescription($data['job'], $data['build'], $description);
                break;
            case 'create-qa-ticket':
                break;
            case 'create-release':
                break;
            case 'copy-assets':
                break;
/*
            case 'copy-job':
                $jenkinsApiWrapper->copyJob($data['job'], 'COPY');
                break;
            case 'disable-job':
                $jenkinsApiWrapper->disableJob($data['job']);
                break;

            case 'create-changelog':
                break;
            case 'create-upgrading':
                break;
*/
            case 'finish':
                break;
            default:
                throw new \RuntimeException('Invalid stage parameter received');
        }

        return new PlainResponse();
    }
}
