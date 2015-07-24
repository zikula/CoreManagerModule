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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use vierbergenlars\SemVer\version;
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
     * @Route("/add-release-ajax", options={"i18n"=false,"expose"=true})
     * @Method("POST")
     *
     * @param Request $request
     * @return PlainResponse
     */
    public function ajaxAction(Request $request)
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
        $githubApiWrapper = $this->get('zikula_core_manager_module.github_api_wrapper');
        $result = false;
        switch ($stage) {
            case 'promote-build':
                $result = $jenkinsApiWrapper->promoteBuild($data['job'], $data['build'], $data['isPreRelease'] ? Settings::RELEASE_CANDIDATE_PROMOTION_ID : Settings::RELEASE_PROMOTION_ID);
                break;
            case 'lock-build':
                $result = $jenkinsApiWrapper->lockBuild($data['job'], $data['build']);
                break;
            case 'add-build-description':
                $description = $jenkinsApiWrapper->getBuildDescription($data['job'], $data['build']);
                if (!empty($description)) {
                    $description = " & " . $description;
                }
                if ($data['isPreRelease']) {
                    $description = 'Release Candidate ' . $data['preRelease'] . $description;
                } else {
                    $description = 'Release ' . $data['version'] . $description;
                }
                $result = $jenkinsApiWrapper->setBuildDescription($data['job'], $data['build'], $description);
                break;
            case 'create-qa-ticket':
                // Guess the milestone to use.
                $milestone = $githubApiWrapper->getMilestoneByCoreVersion(new version($data['version']));
                // Create title.
                $title = 'QA testing for release of ' . $data['version'] . ' build #' . $data['build'];

                // Create issue without body.
                $return = $githubApiWrapper->createIssue($title, "Further information follows in just a second my dear email reader. Checkout the issue already!.", $milestone, Settings::$QA_ISSUE_LABELS);
                if (!isset($return['number'])) {
                    break;
                }
                $issueNumber = $return['number'];

                $description = preg_replace('#\r\n?#', "\n", $data['description']);
                $description = "> " . str_replace("\n\n", "\n\n> ", $description);

                // Prepare replacement array.
                $keys = array_map(function ($val) {
                    return '%' . strtoupper($val) . '%';
                }, array_keys($data));
                $values = array_values($data);
                $replacement = array_combine($keys, $values);
                $replacement['%QAISSUE%'] = $issueNumber;
                $description = strtr($description, $replacement);
                $replacement['%DESCRIPTION%'] = $description;

                // Replace placeholders in issue body and edit issue.
                $body = strtr(Settings::$QA_ISSUE_TEMPLATE, $replacement);
                $return = $githubApiWrapper->updateIssue($issueNumber, null, $body);

                $result = isset($return['number']);
                break;
            case 'create-release':
                //$githubApiWrapper->createRelease($data['version'], $data['isPreRelease'], $data['commmit'], $replacement['%DESCRIPTION%'])
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

        return new JsonResponse(['status' => $result]);
    }
}
