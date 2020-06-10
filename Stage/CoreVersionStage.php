<?php
/**
 * Copyright Zikula - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\Module\CoreManagerModule\Stage;

use Symfony\Component\Form\FormInterface;
use vierbergenlars\SemVer\version;
use Zikula\Component\Wizard\AbortStageException;
use Zikula\Module\CoreManagerModule\Form\Type\CoreVersionType;

class CoreVersionStage extends AbstractStage
{
    public function getFormType()
    {
        return CoreVersionType::class;
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
        $rc = $this->container->get('zikula_core_manager_module.github_api_wrapper')->versionIsPreRelease(new version($data['version']));
        $data['isPreRelease'] = null !== $rc;
        $data['preRelease'] = $rc;
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
        return true;
    }
}
