<?php
/**
 * Copyright Zikula - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\Module\CoreManagerModule\Stage;

use Zikula\Common\Translator\TranslatorTrait;
use Zikula\Component\Wizard\AbortStageException;
use Zikula\Module\CoreManagerModule\Form\Type\ExecuteType;

class ExecuteStage extends AbstractStage
{
    use TranslatorTrait;

    public function setTranslator($translator)
    {
        $this->translator = $translator;
    }

    public function getFormType()
    {
        return ExecuteType::class;
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
        if (empty($data['title'])) {
            throw new \LogicException('Title not yet set!');
        }
        if (empty($data['artifactsUrl'])) {
            throw new \LogicException('Artifacts download URL not yet set!');
        }

        return true;
    }

    /**
     * An array of template parameters required in the stage template
     *
     * @return array
     */
    public function getTemplateParams()
    {
        $this->setTranslator($this->container->get('translator.default'));

        $isPreRelease = $this->getData()['isPreRelease'];
        if ($isPreRelease) {
            $stages[] = [
                'name' => $this->__('create-qa-ticket'),
                'pre' => $this->__('Create QA ticket'),
                'during' => $this->__('Creating QA ticket'),
                'success' => $this->__('QA ticket created'),
                'fail' => $this->__('QA ticket could not be created')
            ];
            $stages[] = [
                'name' => $this->__('download-artifacts'),
                'pre' => $this->__('Download artifacts from last core build'),
                'during' => $this->__('Downloading core artifacts'),
                'success' => $this->__('Core artifacts downloaded'),
                'fail' => $this->__('Core artifacts could not be downloaded')
            ];
        } else {
            // disabled because the tag actually isn't created despite a successful response
            /*$stages[] = [
                'name' => $this->__('create-distribution-tag'),
                'pre' => $this->__('Create distribution tag'),
                'during' => $this->__('Creating distribution tag'),
                'success' => $this->__('Distribution tag created'),
                'fail' => $this->__('Distribution tag could not be created')
            ];*/
            $stages[] = [
                'name' => $this->__('download-artifacts'),
                'pre' => $this->__('Download artifacts from last distribution build'),
                'during' => $this->__('Downloading distribution artifacts'),
                'success' => $this->__('Distribution artifacts downloaded'),
                'fail' => $this->__('Distribution artifacts could not be downloaded')
            ];
        }

        $stages[] = [
            'name' => $this->__('create-core-release'),
            'pre' => $this->__('Create GitHub core release'),
            'during' => $this->__('Creating GitHub core release'),
            'success' => $this->__('GitHub core release created'),
            'fail' => $this->__('GitHub core release could not be created')
        ];
        if (!$isPreRelease) {
            $stages[] = [
                'name' => $this->__('create-distribution-release'),
                'pre' => $this->__('Create GitHub distribution release'),
                'during' => $this->__('Creating GitHub distribution release'),
                'success' => $this->__('GitHub distribution release created'),
                'fail' => $this->__('GitHub distribution release could not be created')
            ];
        }

        $stages[] = [
            'name' => $this->__('copy-assets-to-core'),
            'pre' => $this->__('Copy assets to core release'),
            'during' => $this->__('Copying assets to core release (takes longer)'),
            'success' => $this->__('Assets copied to core'),
            'fail' => $this->__('Assets could not be copied to core')
        ];
        if (!$isPreRelease) {
            $stages[] = [
                'name' => $this->__('copy-assets-to-distribution'),
                'pre' => $this->__('Copy assets to distribution release'),
                'during' => $this->__('Copying assets to distribution release (takes longer)'),
                'success' => $this->__('Assets copied to distribution'),
                'fail' => $this->__('Assets could not be copied to distribution')
            ];

            /*$stages[] = [
                'name' => $this->__('update-core-version'),
                'pre' => $this->__('Update Core version'),
                'during' => $this->__('Updating Core version'),
                'success' => $this->__('Core version updated'),
                'fail' => $this->__('Core version could not be updated')
            ];*/
            $stages[] = [
                'name' => $this->__('close-core-milestone'),
                'pre' => $this->__('Close core milestone'),
                'during' => $this->__('Closing core milestone'),
                'success' => $this->__('Core milestone closed'),
                'fail' => $this->__('Core milestone could not be closed')
            ];
        }

        $stages[] = [
            'name' => $this->__('finish'),
            'pre' => $this->__('Finish'),
            'during' => $this->__('Finishing'),
            'success' => $this->__('Finished'),
            'fail' => $this->__('Error while finishing')
        ];

        return ['stages' => $stages];
    }
}
