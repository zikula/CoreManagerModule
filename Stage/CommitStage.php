<?php

namespace Zikula\Module\CoreManagerModule\Stage;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormInterface;
use Zikula\Component\Wizard\AbortStageException;
use Zikula\Component\Wizard\FormHandlerInterface;
use Zikula\Component\Wizard\InjectContainerInterface;
use Zikula\Component\Wizard\StageInterface;
use Zikula\Module\CoreManagerModule\Form\Type\CommitType;

class CommitStage extends AbstractStage
{
    /**
     * Returns an instance of a Symfony Form Type
     *
     * @return \Symfony\Component\Form\FormTypeInterface
     */
    public function getFormType()
    {
        $branch = $this->getData()['branch'];
        $commits = $this->container->get('zikula_core_manager_module.github_api_wrapper')->getLastNCommitsOfBranch($branch, 10);

        return new CommitType($commits);
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
        if (empty($data['branch'])) {
            throw new \LogicException('Branch not yet set!');
        }

        return true;
    }
}
