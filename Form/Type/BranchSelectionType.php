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

class BranchSelectionType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var array
     */
    private $branches;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        TranslatorInterface $translator,
        GitHubApiWrapper $api,
        ProgressDataStorageHelper $storageHelper
    ) {
        $this->translator = $translator;
        $branches = $api->getBranches();
        $previousRelease = $api->getBranchOfPreviousRelease($storageHelper->getData()['version']);
        if ($previousRelease !== null) {
            usort($branches, function ($a, $b) use ($previousRelease) {
                if ($a != $previousRelease && $b != $previousRelease) {
                    return 0;
                }
                return $a == $previousRelease ? -1 : 1;
            });
        }

        $this->branches = $branches;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('branch', ChoiceType::class, [
                'label' => $this->translator->__('Branch'),
                'label_attr' => ['class' => 'col-sm-3'],
                'choices' => new ArrayChoiceList($this->branches),
            ])
            ->add('next', SubmitType::class, [
                'label' => $this->translator->__('Next'),
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
        return 'branchSelection';
    }
}
