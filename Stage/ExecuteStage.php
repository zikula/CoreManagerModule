<?php

namespace Zikula\Module\CoreManagerModule\Stage;

use Zikula\Component\Wizard\AbortStageException;
use Zikula\Module\CoreManagerModule\Form\Type\ExecuteType;

class ExecuteStage extends AbstractStage
{
    /**
     * Returns an instance of a Symfony Form Type
     *
     * @return \Symfony\Component\Form\FormTypeInterface
     */
    public function getFormType()
    {
        return new ExecuteType();
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
        $stages = [
            [
                'name' => 'promote-build',
                'pre' => 'Promote Jenkins Build',
                'during' => 'Promoting Jenkins Build',
                'success' => 'Jenkins Build promoted',
                'fail' => 'Jenkins Build could not be promoted'
            ],
            [
                'name' => 'lock-build',
                'pre' => 'Lock Jenkins Build',
                'during' => 'Locking Jenkins Build',
                'success' => 'Jenkins Build locked',
                'fail' => 'Jenkins Build could not be locked.'
            ],
            [
                'name' => 'add-build-description',
                'pre' => 'Add Jenkins Build description',
                'during' => 'Adding Jenkins Build description',
                'success' => 'Jenkins Build description added',
                'fail' => 'Jenkins Build description could not be added'
            ],
            [
                'name' => 'create-qa-ticket',
                'pre' => 'Create QA ticket',
                'during' => 'Creating QA ticket',
                'success' => 'QA ticket created',
                'fail' => 'QA ticket could not be created'
            ],
            [
                'name' => 'create-release',
                'pre' => 'Create GitHub Release',
                'during' => 'Creating GitHub Release',
                'success' => 'GitHub Release created',
                'fail' => 'GitHub Release could not be created'
            ],
            [
                'name' => 'copy-assets',
                'pre' => 'Copy assets from Jenkins to GitHub',
                'during' => 'Copying assets from Jenkins to GitHub (takes longer)',
                'success' => 'Assets copied',
                'fail' => 'Assets could not be copied'
            ],
        ];

        if (!$this->getData()['isPreRelease']) {
            $stages[] = [
                'name' => 'copy-job',
                'pre' => 'Copy old Jenkins Job',
                'during' => 'Copying Jenkins Job',
                'success' => 'Jenkins Job copied',
                'fail' => 'Jenkins Job could not be copied'
            ];
            $stages[] = [
                'name' => 'disable-job',
                'pre' => 'Dsiable old Jenkins Job',
                'during' => 'Disabling Jenkins Job',
                'success' => 'Jenkins Job disabled',
                'fail' => 'Jenkins Job could not be disabled'
            ];
            //$version = $this->getData()
            if (0) {
                $stages[] = [
                    'name' => 'create-changelog',
                    'pre' => 'Create new changelog',
                    'during' => 'Creating new changelog',
                    'success' => 'New changelog created',
                    'fail' => 'New changelog could not be created'
                ];
                $stages[] = [
                    'name' => 'create-upgrading',
                    'pre' => 'Create new upgrading file',
                    'during' => 'Creating new upgrading file',
                    'success' => 'New upgrading file created',
                    'fail' => 'New upgrading file could not be created'
                ];
            }
        }
        $stages[] = [
            'name' => 'finish',
            'pre' => 'Finish',
            'during' => 'Finishing',
            'success' => 'Finished',
            'fail' => 'Error while finishing'
        ];

        return ['stages' => $stages];
    }
}
