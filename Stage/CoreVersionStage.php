<?php

namespace Zikula\Module\CoreManagerModule\Stage;

use Symfony\Component\Form\FormInterface;
use vierbergenlars\SemVer\version;
use Zikula\Component\Wizard\AbortStageException;
use Zikula\Module\CoreManagerModule\Form\Type\CoreVersionType;
use Zikula\Module\CoreManagerModule\Manager\GitHubApiWrapper;

class CoreVersionStage extends AbstractStage
{
    /**
     * @var GitHubApiWrapper
     */
    protected $gitHubApiWrapper;

    /**
     * Returns an instance of a Symfony Form Type
     *
     * @return \Symfony\Component\Form\FormTypeInterface
     */
    public function getFormType()
    {
        $this->gitHubApiWrapper = $this->container->get('zikula_core_manager_module.github_api_wrapper');

        return new CoreVersionType($this->gitHubApiWrapper->getAllowedCoreVersions());
    }

    /**
     * Handle results of previously validated form
     *
     * @param FormInterface $form
     * @return boolean
     */
    public function handleFormResult(FormInterface $form)
    {
        $data = $form->getData();
        $rc = $this->gitHubApiWrapper->versionIsPreRelease(new version($data['version']));
        $data['isPreRelease'] = $rc !== false;
        $data['preRelease'] = $rc;
        $this->addData($data);
    }

    /**
     * Logic to determine if the stage is required or can be skipped
     *
     * @return boolean
     * @throws AbortStageException
     */
    public function isNecessary()
    {
        return true;
    }
}
