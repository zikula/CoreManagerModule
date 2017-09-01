<?php

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
        // Assert core version correct
        $stages[] = [
            'name' => $this->__('promote-build'),
            'pre' => $this->__('Promote Jenkins Build'),
            'during' => $this->__('Promoting Jenkins Build'),
            'success' => $this->__('Jenkins Build promoted'),
            'fail' => $this->__('Jenkins Build could not be promoted')
        ];
        $stages[] = [
            'name' => $this->__('lock-build'),
            'pre' => $this->__('Lock Jenkins Build'),
            'during' => $this->__('Locking Jenkins Build'),
            'success' => $this->__('Jenkins Build locked'),
            'fail' => $this->__('Jenkins Build could not be locked.')
        ];
        $stages[] = [
            'name' => $this->__('add-build-description'),
            'pre' => $this->__('Add Jenkins Build description'),
            'during' => $this->__('Adding Jenkins Build description'),
            'success' => $this->__('Jenkins Build description added'),
            'fail' => $this->__('Jenkins Build description could not be added')
        ];
        if ($this->getData()['isPreRelease']) {
            $stages[] = [
                'name' => $this->__('create-qa-ticket'),
                'pre' => $this->__('Create QA ticket'),
                'during' => $this->__('Creating QA ticket'),
                'success' => $this->__('QA ticket created'),
                'fail' => $this->__('QA ticket could not be created')
            ];
        }
        $stages[] = [
            'name' => $this->__('create-release'),
            'pre' => $this->__('Create GitHub Release'),
            'during' => $this->__('Creating GitHub Release'),
            'success' => $this->__('GitHub Release created'),
            'fail' => $this->__('GitHub Release could not be created')
        ];

        if (!$this->getData()['isPreRelease']) {
            $stages[] = [
                'name' => $this->__('update-core-version'),
                'pre' => $this->__('Update Core version'),
                'during' => $this->__('Updating Core version'),
                'success' => $this->__('Core version updated'),
                'fail' => $this->__('Core version could not be updated')
            ];
            $stages[] = [
                'name' => $this->__('close-milestone'),
                'pre' => $this->__('Close milestone'),
                'during' => $this->__('Closing milestone'),
                'success' => $this->__('Milestone closed'),
                'fail' => $this->__('Milestone could not be closed')
            ];
            // Update Core Version
            // Close milestone
        }
        $stages[] = [
            'name' => $this->__('copy-assets'),
            'pre' => $this->__('Copy assets from Jenkins to GitHub'),
            'during' => $this->__('Copying assets from Jenkins to GitHub (takes longer)'),
            'success' => $this->__('Assets copied'),
            'fail' => $this->__('Assets could not be copied')
        ];

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
