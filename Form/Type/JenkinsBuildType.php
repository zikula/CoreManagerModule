<?php

namespace Zikula\Module\CoreManagerModule\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Zikula\Common\Translator\TranslatorInterface;
use Zikula\Module\CoreManagerModule\Helper\ProgressDataStorageHelper;
use Zikula\Module\CoreManagerModule\Manager\ReleaseManager;

class JenkinsBuildType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var array
     */
    private $builds;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        TranslatorInterface $translator,
        ReleaseManager $manager,
        ProgressDataStorageHelper $storageHelper
    ) {
        $this->translator = $translator;
        $version = new \vierbergenlars\SemVer\version($storageHelper->getData()['version']);
        $version = $version->getMajor() . "." . $version->getMinor() . "." . $version->getPatch();
        $buildsArr = $manager->getMatchingJenkinsBuilds($version, $storageHelper->getData()['commit']);
        $job = $manager->getJobMatchingZikulaVersion($version);
        $builds = [];
        foreach ($buildsArr as $build) {
            $builds[$build->getNumber() . "|" . $job->getName()] = "#" . $build->getNumber();
        }
        if (count($builds) == 0) {
            throw new \RuntimeException('No matching Jenkins builds for commit ' . $storageHelper->getData()['commit']);
        }

        $this->builds = $builds;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('build', ChoiceType::class, [
                'label' => $this->translator->__('Jenkins build'),
                'label_attr' => ['class' => 'col-sm-3'],
                'choices' => new ArrayChoiceList($this->builds),
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
        return 'jenkinsBuild';
    }
}
