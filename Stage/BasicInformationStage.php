<?php
/**
 * Copyright Zikula - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\Module\CoreManagerModule\Stage;

use Zikula\Component\Wizard\AbortStageException;
use Zikula\Module\CoreManagerModule\Form\Type\BasicInformationType;

class BasicInformationStage extends AbstractStage
{
    /**
     * Returns an instance of a Symfony Form Type
     */
    public function getFormType()
    {
        return BasicInformationType::class;
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
