<?php

namespace Zikula\Module\CoreManagerModule\Stage;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormInterface;
use Zikula\Component\Wizard\AbortStageException;
use Zikula\Component\Wizard\FormHandlerInterface;
use Zikula\Component\Wizard\InjectContainerInterface;
use Zikula\Component\Wizard\StageInterface;
use Zikula\Module\CoreManagerModule\Form\Type\BranchSelectionType;

class BranchSelectionStage extends AbstractStage
{
    /**
     * Returns an instance of a Symfony Form Type
     *
     * @return \Symfony\Component\Form\FormTypeInterface
     */
    public function getFormType()
    {
        $api = $this->container->get('zikula_core_manager_module.github_api_wrapper');
        $branches = $api->getBranches();
        $previousRelease = $api->getBranchOfPreviousRelease($this->getData()['version']);
        if ($previousRelease !== null) {
            usort($branches, function ($a, $b) use ($previousRelease) {
                if ($a != $previousRelease && $b != $previousRelease) {
                    return 0;
                }
                return $a == $previousRelease ? -1 : 1;
            });
        }
        return new BranchSelectionType($branches);
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
        if (empty($data['version'])) {
            throw new \LogicException('Version not yet set!');
        }

        return true;
    }
}
