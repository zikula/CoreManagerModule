<?php

namespace Zikula\Module\CoreManagerModule\Stage;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormInterface;
use Zikula\Component\Wizard\AbortStageException;
use Zikula\Component\Wizard\FormHandlerInterface;
use Zikula\Component\Wizard\InjectContainerInterface;
use Zikula\Component\Wizard\StageInterface;
use Zikula\Module\CoreManagerModule\Form\Type\CoreVersionAndBranchType;

class ChooseCoreVersionAndBranchStage implements StageInterface, FormHandlerInterface, InjectContainerInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * Require the Symfony Container on instantiation
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Returns an instance of a Symfony Form Type
     *
     * @return \Symfony\Component\Form\FormTypeInterface
     */
    public function getFormType()
    {
        return new CoreVersionAndBranchType($this->container->get('zikula_core_manager_module.github_api_wrapper'));
    }

    /**
     * Handle results of previously validated form
     *
     * @param FormInterface $form
     * @return boolean
     */
    public function handleFormResult(FormInterface $form)
    {
        \UserUtil::setVar('ZikulaCoreManagerModule_release', $form->getData());
    }

    /**
     * The stage name
     *
     * @return string
     */
    public function getName()
    {
        return 'chooseCoreVersionAndBranch';
    }

    /**
     * The stage's full template name, e.g. 'AcmeDemoBundle:Stage:prep.html.twig'
     * @return string
     */
    public function getTemplateName()
    {
        return 'ZikulaCoreManagerModule:AddRelease:CoreVersionAndBranch.html.twig';
    }

    /**
     * Logic to determine if the stage is required or can be skipped
     *
     * @return boolean
     * @throws AbortStageException
     */
    public function isNecessary()
    {
        // TODO: Implement isNecessary() method.
        return true;
    }

    /**
     * An array of template parameters required in the stage template
     *
     * @return array
     */
    public function getTemplateParams()
    {
        return [];
    }
}
