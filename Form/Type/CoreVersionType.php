<?php
/**
 * Copyright Zikula - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\Module\CoreManagerModule\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Zikula\Common\Translator\TranslatorInterface;
use Zikula\Module\CoreManagerModule\Manager\GitHubApiWrapper;

class CoreVersionType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var array
     */
    private $versions;

    /**
     * @var array
     */
    private $tags;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        TranslatorInterface $translator,
        GitHubApiWrapper $api
    ) {
        $this->translator = $translator;
        $this->versions = $api->getAllowedCoreVersions();
        $this->tags = $api->getAvailableTags();
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $versionChoices = new ArrayChoiceList($this->versions);
        $tagChoices = new ArrayChoiceList($this->tags);
        $builder
            ->add('version', ChoiceType::class, [
                'label' => $this->translator->__('Core version'),
                'choices' => $versionChoices->getChoices(),
                'choices_as_values' => true
            ])
            ->add('tag', ChoiceType::class, [
                'label' => $this->translator->__('Tag'),
                'choices' => $tagChoices->getChoices(),
                'choices_as_values' => true
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
        return 'coreVersion';
    }
}
