<?php

namespace Zikula\Module\CoreManagerModule\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Zikula\Common\Translator\TranslatorInterface;
use Zikula\Module\CoreManagerModule\Helper\ProgressDataStorageHelper;
use Zikula\Module\CoreManagerModule\Manager\GitHubApiWrapper;

class CommitType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var array
     */
    private $commits;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        TranslatorInterface $translator,
        GitHubApiWrapper $api,
        ProgressDataStorageHelper $storageHelper
    ) {
        $this->translator = $translator;
        $branch = $storageHelper->getData()['branch'];
        $this->commits = $api->getLastNCommitsOfBranch($branch, 10);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $commits = [];
        foreach ($this->commits as $commit) {
            $commits[$commit['sha']] = substr($commit['sha'], 0, 8) . " - " . $commit['commit']['message'];
        }
        $builder
            ->add('commit', ChoiceType::class, [
                'label' => $this->translator->__('Commit'),
                'label_attr' => ['class' => 'col-sm-3'],
                'choices' => $commits,
            ])
            ->add('next', SubmitType::class, [
                'label' => $this->translator->__('Next'),
                'icon' => 'fa-angle-double-right',
                'attr' => [
                    'class' => 'btn btn-success'
                ]
            ])
        ;
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return 'commit';
    }
}
