<?php

namespace Zikula\Module\CoreManagerModule\Stage;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormInterface;
use Zikula\Component\Wizard\AbortStageException;
use Zikula\Component\Wizard\FormHandlerInterface;
use Zikula\Component\Wizard\InjectContainerInterface;
use Zikula\Component\Wizard\StageInterface;
use Zikula\Module\CoreManagerModule\Form\Type\BranchSelectionType;

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

    /**
     * Handle results of previously validated form
     *
     * @param FormInterface $form
     * @return boolean
     */
    public function handleFormResult(FormInterface $form)
    {
        $data = $this->getData();
        \UserUtil::setVar('ZikulaCoreManagerModule_release', json_encode(array_merge($data, $form->getData())));
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
        $data = \UserUtil::getVar('ZikulaCoreManagerModule_release');
        if (empty($data)) {
            $data = '[]';
        }
        return json_decode($data, true);
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
