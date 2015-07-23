<?php

namespace Zikula\Module\CoreManagerModule\Stage;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormInterface;
use Zikula\Component\Wizard\AbortStageException;
use Zikula\Component\Wizard\FormHandlerInterface;
use Zikula\Component\Wizard\InjectContainerInterface;
use Zikula\Component\Wizard\StageInterface;
use Zikula\Module\CoreManagerModule\Form\Type\JenkinsBuildType;

class JenkinsBuildStage extends AbstractStage
{
    /**
     * Returns an instance of a Symfony Form Type
     *
     * @return \Symfony\Component\Form\FormTypeInterface
     */
    public function getFormType()
    {
        $version = new \vierbergenlars\SemVer\version($this->getData()['version']);
        $version = $version->getMajor() . "." . $version->getMinor() . "." . $version->getPatch();
        $api = $this->container->get('zikulacoremanagermodule.releasemanager');
        $buildsArr = $api->getMatchingJenkinsBuilds($version, $this->getData()['commit']);
        $job = $api->getJobMatchingZikulaVersion($version);
        $builds = [];
        foreach ($buildsArr as $build) {
            $builds[$build->getNumber() . "|" . $job->getName()] = "#" . $build->getNumber();
        }
        if (count($builds) == 0) {
            throw new \RuntimeException('No matching Jenkins builds for commit ' . $this->getData()['commit']);
        }

        return new JenkinsBuildType($builds);
    }

    /**
     * Handle results of previously validated form
     *
     * @param FormInterface $form
     * @return boolean
     */
    public function handleFormResult(FormInterface $form)
    {
        list($build, $job) = explode('|', $form->getData());
        $data['build'] = $build;
        $data['job'] = $job;
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
        $data = $this->getData();
        if (empty($data['commit'])) {
            throw new \LogicException('Commit not yet set!');
        }

        return true;
    }
}
