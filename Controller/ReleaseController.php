<?php
/**
 * Copyright Zikula - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
        if (empty($data) || $data === 'null' || $data === 'false' || $data === 'Array') {
            throw new \RuntimeException('Could not decode user data.');
        }
        $gitHubApiWrapper = $this->get('zikula_core_manager_module.github_api_wrapper');
        $result = false;
        switch ($stage) {
            case 'create-qa-ticket':
                // guess the milestone to use
                $milestone = $gitHubApiWrapper->getMilestoneByCoreVersion(new version($data['version']));
                // create title
                $title = 'QA testing for release of ' . $data['version'];

                // create issue without body
                $return = $gitHubApiWrapper->createIssue(
                    $title,
                    'Further information follows in just a second my dear email reader. Checkout the issue already!.',
                    $milestone,
                    Settings::$QA_ISSUE_LABELS
                );
                if (!isset($return['number'])) {
                    break;
                }
                $issueNumber = $return['number'];

                // prepare replacement array
                $keys = array_map(function ($val) {
                    return '%' . strtoupper($val) . '%';
                }, array_keys($data));
                $values = array_values($data);
                $replacement = array_combine($keys, $values);
                $replacement['%QAISSUE%'] = $issueNumber;

                // replace placeholders in issue body
                $body = strtr(Settings::$QA_ISSUE_TEMPLATE, $replacement);
                // update the issue
                $return = $gitHubApiWrapper->updateIssue($issueNumber, null, $body);

                if (isset($return['number'])) {
                    $data['github_qa_ticket_url'] = $return['html_url'];
                    $dataHelper->setData($data);
                    $result = true;
                }
                break;
            case 'create-distribution-tag':
                $commits = $gitHubApiWrapper->getLastNCommitsOfBranch('dist', 'master', 1);
                $lastCommit = array_shift($commits);
                $gitHubApiWrapper->createDistributionTag($data['tag'], $data['tag'], $lastCommit['sha']);
                $result = true;
                break;
            case 'download-artifacts':
                $data['artifactsArchivePath'] = $gitHubApiWrapper->downloadReleaseAssets($data['artifactsUrl']);
                $dataHelper->setData($data);
                $result = true;
                break;
            case 'create-core-release':
                if (!isset($data['github_qa_ticket_url'])) {
                    $data['github_qa_ticket_url'] = '';
                }
                $description = str_replace('%QATICKETURL%', $data['github_qa_ticket_url'], $data['description']);
                $return = $gitHubApiWrapper->createRelease('core', $data['title'], $description, $data['isPreRelease'], $data['tag'], $data['commit']);
                if (isset($return['id'])) {
                    $data['github_core_release_id'] = $return['id'];
                    $dataHelper->setData($data);
                    $result = true;
                }
                break;
            case 'create-distribution-release':
                if (!isset($data['github_qa_ticket_url'])) {
                    $data['github_qa_ticket_url'] = '';
                }
                $description = str_replace('%QATICKETURL%', $data['github_qa_ticket_url'], $data['description']);
                $return = $gitHubApiWrapper->createRelease('dist', $data['title'], $description, $data['isPreRelease'], $data['tag'], $data['commit']);
                if (isset($return['id'])) {
                    $data['github_distribution_release_id'] = $return['id'];
                    $dataHelper->setData($data);
                    $result = true;
                }
                break;
            case 'copy-assets-to-core':
                $result = $gitHubApiWrapper->createReleaseAssets('core', $data['github_core_release_id'], $data['artifactsArchivePath']);
                break;
            case 'copy-assets-to-distribution':
                $result = $gitHubApiWrapper->createReleaseAssets('dist', $data['github_distribution_release_id'], $data['artifactsArchivePath']);
                break;
            case 'update-core-version': // currently unused
                $coreFile = $gitHubApiWrapper->getFile(Settings::CORE_PHP_FILE, $data['commit']);
                if (false === $coreFile) {
                    break;
                }
                $version = new version($data['version']);
                $version->inc('patch');
                $coreFile = preg_replace(Settings::CORE_PHP_FILE_VERSION_REGEXP, $version->getVersion(), $coreFile);

                $return = $gitHubApiWrapper->updateFile(Settings::CORE_PHP_FILE, $coreFile, 'Update Core version.', $data['commit']);
                if (isset($return['commit'])) {
                    $result = true;
                }
                break;
            case 'close-core-milestone':
                // guess the milestone to close.
                $milestone = $gitHubApiWrapper->getMilestoneByCoreVersion(new version($data['version']));
                if (null !== $milestone) {
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
