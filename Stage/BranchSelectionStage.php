<?php

namespace Zikula\Module\CoreManagerModule\Stage;

use Zikula\Component\Wizard\AbortStageException;
use Zikula\Module\CoreManagerModule\Form\Type\BranchSelectionType;

class BranchSelectionStage extends AbstractStage
{
    public function getFormType()
    {
        return BranchSelectionType::class;
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
