<?php

namespace Zikula\Module\CoreManagerModule\Stage;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormInterface;
use Zikula\Component\Wizard\AbortStageException;
use Zikula\Component\Wizard\FormHandlerInterface;
use Zikula\Component\Wizard\InjectContainerInterface;
use Zikula\Component\Wizard\StageInterface;
use Zikula\Module\CoreManagerModule\Form\Type\BasicInformationType;

class BasicInformationStage extends AbstractStage
{
    /**
     * Returns an instance of a Symfony Form Type
     *
     * @return \Symfony\Component\Form\FormTypeInterface
     */
    public function getFormType()
    {
        return new BasicInformationType();
    }

    /**
     * Logic to determine if the stage is required or can be skipped
     *
     * @return boolean
     * @throws AbortStageException
     */
    public function isNecessary()
    {
        $data = $this->getData();
        if (empty($data['build'])) {
            throw new \LogicException('Build not yet set!');
        }

        return true;
    }
/*
    public function getTemplateParameters()
    {
        return $this->getData();
    }*/
}
