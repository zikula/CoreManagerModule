<?php

namespace Zikula\Module\CoreManagerModule\Stage;

use Symfony\Component\Form\FormInterface;
use Zikula\Component\Wizard\AbortStageException;
use Zikula\Module\CoreManagerModule\Form\Type\JenkinsBuildType;

class JenkinsBuildStage extends AbstractStage
{
    public function getFormType()
    {
        return JenkinsBuildType::class;
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
        list($build, $job) = explode('|', $data['build']);
        $data['build'] = $build;
        $data['job'] = $job;
        $this->addData($data);

        return true;
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
        if (empty($data['commit'])) {
            throw new \LogicException('Commit not yet set!');
        }

        return true;
    }
}
