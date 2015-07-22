<?php

namespace Zikula\Module\CoreManagerModule\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use vierbergenlars\SemVer\version;
use Zikula\Module\CoreManagerModule\Manager\GitHubApiWrapper;

class BasicInformationType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', 'text', [
            'label' => __('Release title', 'ZikulaCoreManagerModule'),
            'label_attr' => ['class' => 'col-sm-3'],
        ])->add('description', 'textarea', [
            'attr' => ['rows' => 20],
            'label' => __('Release announcement', 'ZikulaCoreManagerModule'),
            'label_attr' => ['class' => 'col-sm-3']
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
        return 'basicInformation';
    }
}
