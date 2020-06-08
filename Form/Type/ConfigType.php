<?php

namespace Zikula\Module\CoreManagerModule\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Routing\RouterInterface;
use Zikula\Common\Translator\TranslatorInterface;
use Zikula\Common\Translator\TranslatorTrait;

class ConfigType extends AbstractType
{
    use TranslatorTrait;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @param TranslatorInterface $translator
     * @param RouterInterface $router
     */
    public function __construct(
        TranslatorInterface $translator,
        RouterInterface $router
    ) {
        $this->setTranslator($translator);
        $this->router = $router;
    }

    public function setTranslator($translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('is_main_instance', CheckboxType::class, [
                'required' => false,
                'label' => $this->__('Main instance'),
                'help' => $this->__('Only tick this box at one place, i.e. the ziku.la site. It must not be ticked at other community sites.')
            ])
            ->add('github_core_repo', TextType::class, [
                'label' => $this->__('Core repository'),
                'help' => $this->__('Fill in the name of the core repository. This should always be "zikula/core"')
            ])
            ->add('github_dist_repo', TextType::class, [
                'label' => $this->__('Distribution repository'),
                'help' => $this->__('Fill in the name of the distribution repository. This should always be "zikula/distribution"')
            ])
            ->add('github_token', TextType::class, [
                'label' => $this->__('Access Token'),
                'help' => $this->__f('Create a personal access token at %s to raise your api limits.', ['%s' => '<a href="https://github.com/settings/tokens">https://github.com/settings/tokens</a>'])
            ])
            ->add('github_webhook_token', TextType::class, [
                'label' => $this->__('Webhook Security Token'),
                'help' => $this->__f('Create a secret webhook token at %s to verify payloads from the Zikula Core repository.', ['%s' => '<a href="https://github.com/zikula/core/settings/hooks">https://github.com/zikula/core/settings/hooks</a>'])
            ])
            ->add('save', SubmitType::class, [
                'label' => $this->__('Save'),
                'icon' => 'fa-check',
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
        return 'coremanagermodule_config';
    }
}
