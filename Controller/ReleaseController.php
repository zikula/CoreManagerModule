<?php

namespace Zikula\Module\CoreManagerModule\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use vierbergenlars\SemVer\version;
use Zikula\Component\Wizard\FormHandlerInterface;
use Zikula\Component\Wizard\Wizard;
use Zikula\Component\Wizard\WizardCompleteInterface;
use Zikula\Core\Controller\AbstractController;
use Zikula\Module\CoreManagerModule\Settings;
use Zikula\ThemeModule\Engine\Annotation\Theme;

/**
 * @Route("/admin")
 */
class ReleaseController extends AbstractController
{
    /**
     * @Route("/add-release/{stage}")
     * @Theme("admin")
     * @param Request $request
     * @param $stage
     * @return RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function wizardAction(Request $request, $stage = null)
    {
        if (!$this->hasPermission('ZikulaCoreManagerModule:addRelease:', '::', ACCESS_ADD)) {
            throw new AccessDeniedException();
        }

        $wizard = new Wizard($this->container, realpath(__DIR__ . '/../Resources/config/release-stages.yml'));
        $currentStage = $wizard->getCurrentStage($stage);
        if ($currentStage instanceof WizardCompleteInterface) {
            return $currentStage->getResponse($request);
        }
        if ($wizard->isHalted()) {
            $this->addFlash('danger', $wizard->getWarning());

            // @todo..
            return $this->render('');
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

        return $this->render($currentStage->getTemplateName(), $templateParams);
    }

    /**
     * @Route("/add-release-ajax", methods = {"POST"}, options={"i18n"=false,"expose"=true})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function ajaxAction(Request $request)
    {
        if (!$this->hasPermission('ZikulaCoreManagerModule:addRelease:', '::', ACCESS_ADD)) {
            throw new AccessDeniedException();
        }
        set_time_limit(300);
        ignore_user_abort(true);

        $stage = $request->request->get('stage', false);
        if ($stage === false) {
            throw new \RuntimeException('No stage parameter received.');
        }
        $dataHelper = $this->container->get('zikula_core_manager_module.progress_data_storage_helper');
        $data = $dataHelper->getData();
        if (empty($data) || $data === "null" || $data === "false" || $data === "Array") {
            throw new \RuntimeException('Could not decode user data.');
        }
        $jenkinsApiWrapper = $this->get('zikula_core_manager_module.jenkins_api_wrapper');
        $gitHubApiWrapper = $this->get('zikula_core_manager_module.github_api_wrapper');
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
                $milestone = $gitHubApiWrapper->getMilestoneByCoreVersion(new version($data['version']));
                // Create title.
                $title = 'QA testing for release of ' . $data['version'] . ' build #' . $data['build'];

                // Create issue without body.
                $return = $gitHubApiWrapper->createIssue($title, "Further information follows in just a second my dear email reader. Checkout the issue already!.", $milestone, Settings::$QA_ISSUE_LABELS);
                if (!isset($return['number'])) {
                    break;
                }
                $issueNumber = $return['number'];

                // Prepare replacement array.
                $keys = array_map(function ($val) {
                    return '%' . strtoupper($val) . '%';
                }, array_keys($data));
                $values = array_values($data);
                $replacement = array_combine($keys, $values);
                $replacement['%QAISSUE%'] = $issueNumber;

                // Replace placeholders in issue body and edit issue.
                $body = strtr(Settings::$QA_ISSUE_TEMPLATE, $replacement);
                $return = $gitHubApiWrapper->updateIssue($issueNumber, null, $body);

                if (isset($return['number'])) {
                    $data['github_qa_ticket_url'] = $return['html_url'];
                    $dataHelper->setData($data);
                    $result = true;
                }
                break;
            case 'create-release':
                if (!isset($data['github_qa_ticket_url'])) {
                    $data['github_qa_ticket_url'] = '';
                }
                $description = str_replace('%QATICKETURL%', $data['github_qa_ticket_url'], $data['description']);
                $return = $gitHubApiWrapper->createRelease($data['title'], $description, $data['isPreRelease'], $data['version'], $data['commit']);
                if (isset($return['id'])) {
                    $data['github_release_id'] = $return['id'];
                    $dataHelper->setData($data);
                    $result = true;
                }
                break;
            case 'copy-assets':
                $assets = $jenkinsApiWrapper->getAssets($data['job'], $data['build']);
                if ($assets === false) {
                    return new JsonResponse(['status' => false]);
                }
                $result = true;
                foreach ($assets as $asset) {
                    if (!$asset['content_type']) {
                        // GitHub won't allow us to upload files without specifying the content type.
                        // Skip those files (but there shouldn't be any).
                        continue;
                    }
                    $return = $gitHubApiWrapper->createReleaseAsset($data['github_release_id'], $asset);
                    if (!isset($return['id'])) {
                        $result = false;
                        break;
                    }
                }
                break;
            case 'update-core-version': // currently unused
                $coreFile = $gitHubApiWrapper->getFile(Settings::CORE_PHP_FILE, $data['commit']);
                if ($coreFile === false) {
                    break;
                }
                $version = new version($data['version']);
                $version->inc('patch');
                $coreFile = preg_replace(Settings::CORE_PHP_FILE_VERSION_REGEXP, $version->getVersion(), $coreFile);

                $return = $gitHubApiWrapper->updateFile(Settings::CORE_PHP_FILE, $coreFile, "Update Core version.", $data['commit']);
                if (isset($return['commit'])) {
                    $result = true;
                }
                break;
            case 'close-milestone':
                // Guess the milestone to close.
                $milestone = $gitHubApiWrapper->getMilestoneByCoreVersion(new version($data['version']));
                if ($milestone !== null) {
                    $return = $gitHubApiWrapper->closeMilestone($milestone);
                    $result = isset($return['number']);
                } else {
                    $result = true;
                }
                break;
            case 'finish':
                break;
            default:
                throw new \RuntimeException('Invalid stage parameter received');
        }

        return new JsonResponse(['status' => $result]);
    }
}
