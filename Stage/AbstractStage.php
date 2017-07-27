<?php

namespace Zikula\Module\CoreManagerModule\Stage;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormInterface;
use Zikula\Component\Wizard\FormHandlerInterface;
use Zikula\Component\Wizard\InjectContainerInterface;
use Zikula\Component\Wizard\StageInterface;

abstract class AbstractStage implements StageInterface, FormHandlerInterface, InjectContainerInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

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
    abstract public function getFormType();

    public function getFormOptions()
    {
        // default
    }

    /**
     * Handle results of previously validated form
     *
     * @param FormInterface $form
     * @return boolean
     */
    public function handleFormResult(FormInterface $form)
    {
        return $this->addData($form->getData());
    }

    /**
     * The stage name
     *
     * @return string
     */
    public function getName()
    {
        $class = get_class($this);
        $class = explode('\\', $class);
        $class = $class[count($class) - 1];
        $class = substr($class, 0, strlen($class) - strlen('Stage'));
        $class = lcfirst($class);
        return $class;
    }

    /**
     * The stage's full template name, e.g. 'AcmeDemoBundle:Stage:prep.html.twig'
     * @return string
     */
    public function getTemplateName()
    {
        return 'ZikulaCoreManagerModule:AddRelease:' . ucfirst($this->getName()) . '.html.twig';
    }

    protected function getData()
    {
        return $this->container->get('zikula_core_manager_module.helper.progress_data_storage_helper')->getData();
    }

    protected function addData($data)
    {
        return $this->container->get('zikula_core_manager_module.helper.progress_data_storage_helper')->addData($data);
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

    /**
     * Returns an array of options applied to the Form.
     * @return array
     */
    public function getFormOptions()
    {
        return [];
    }
}
