<?php

namespace Zikula\Module\CoreManagerModule\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use vierbergenlars\SemVer\version;
use Zikula\Module\CoreManagerModule\Manager\GitHubApiWrapper;

class BranchSelectionType extends AbstractType
{
    /**
     * @var array
     */
    private $branches;

    /**
     * {@inheritdoc}
     */
    public function __construct(array $branches)
    {
        $this->branches = $branches;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('branch', 'choice', [
            'label' => __('Branch', 'ZikulaCoreManagerModule'),
            'label_attr' => ['class' => 'col-sm-3'],
            'choice_list' => new ChoiceList($this->branches, $this->branches),
        ])->add('next', 'submit', [
            'label' => __('Next', 'ZikulaCoreManagerModule'),
        ]);
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return 'branchSelection';
    }
}
