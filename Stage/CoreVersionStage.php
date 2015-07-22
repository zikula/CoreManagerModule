<?php

namespace Zikula\Module\CoreManagerModule\Stage;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormInterface;
use Zikula\Component\Wizard\AbortStageException;
use Zikula\Component\Wizard\FormHandlerInterface;
use Zikula\Component\Wizard\InjectContainerInterface;
use Zikula\Component\Wizard\StageInterface;
use Zikula\Module\CoreManagerModule\Form\Type\CoreVersionType;

class CoreVersionStage extends AbstractStage
{
    /**
     * Returns an instance of a Symfony Form Type
     *
     * @return \Symfony\Component\Form\FormTypeInterface
     */
    public function getFormType()
    {
        return new CoreVersionType($this->container->get('zikula_core_manager_module.github_api_wrapper')->getAllowedCoreVersions());
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
}
