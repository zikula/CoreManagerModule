<?php

namespace Zikula\Module\CoreManagerModule\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
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
        $route = $this->router->generate('zikulacoremanagermodule_webhook_jenkins', ['code' => 'SECURITYTOKEN'], RouterInterface::ABSOLUTE_URL);
        $builder
            ->add('is_main_instance', CheckboxType::class, [
                'required' => false,
                'label' => $this->__('Main instance'),
                'help' => $this->__('Only tick this box at one place, i.e. the zikula.org site. It must not be ticked at other community sites.')
            ])
            ->add('github_core_repo', TextType::class, [
                'label' => $this->__('Core repository'),
                'help' => $this->__('Fill in the name of the core repository. This should always be "zikula/core"')
            ])
            ->add('github_token', PasswordType::class, [
                'label' => $this->__('Access Token'),
                'help' => $this->__f('Create a personal access token at %s to raise your api limits.', ['%s' => '<a href="https://github.com/settings/applications">https://github.com/settings/applications</a>'])
            ])
            ->add('github_webhook_token', PasswordType::class, [
                'label' => $this->__('Webhook Security Token'),
                'help' => $this->__f('Create a secrete webhook token at %s to verify payloads from the Zikula Core repository.', ['%s' => '<a href="https://developer.github.com/webhooks/securing">https://developer.github.com/webhooks/securing/</a>'])
            ])
            ->add('jenkins_server', UrlType::class, [
                'label' => $this->__('URL of the Jenkins server'),
                'help' => $this->__('Make sure to include "http://". Do not include "www". Example: "http://ci.zikula.org"')
            ])
            ->add('jenkins_token', PasswordType::class, [
                'label' => $this->__('Jenkins Security token'),
                'help' => $this->__f('A security token to verify requests from Jenkins. Please setup Jenkins to make a POST request to the following url everytime a build has finished: %s. You can use the "Post Completed Build Result Plugin" to do the job: https://wiki.jenkins-ci.org/display/JENKINS/Post+Completed+Build+Result+Plugin.', ['%s' => '<a href="' . $route . '">' . $route . '</a>'])
            ])
            ->add('jenkins_user', TextType::class, [
                'label' => $this->__('Jenkins Username'),
                'help' => $this->__('Must be set to the User ID found under the API Token section at your user account\'s settings.')
            ])
            ->add('jenkins_password', PasswordType::class, [
                'label' => $this->__('Jenkins Password'),
                'help' => $this->__('Must be set to the API Token found under the API Token section at your user account\'s settings .')
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
