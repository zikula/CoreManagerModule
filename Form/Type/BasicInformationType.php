<?php

namespace Zikula\Module\CoreManagerModule\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use vierbergenlars\SemVer\version;
use Zikula\Common\Translator\TranslatorInterface;
use Zikula\Module\CoreManagerModule\Helper\ProgressDataStorageHelper;
use Zikula\Module\CoreManagerModule\Manager\GitHubApiWrapper;
use Zikula\Module\CoreManagerModule\Settings;

class BasicInformationType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var string
     */
    private $defaultTitle;
    /**
     * @var
     */
    private $defaultAnnouncement;

    public function __construct(
        TranslatorInterface $translator,
        GitHubApiWrapper $api,
        ProgressDataStorageHelper $storageHelper
    ) {
        $this->translator = $translator;
        /** @var version $version */
        $version = new version($storageHelper->getData()['version']);
        $defaultTitle = sprintf(Settings::RELEASE_TITLE, $api->versionToMajorMinorPatch($version));
        $defaultAnnouncement = sprintf(Settings::RELEASE_ANNOUNCEMENT, $version->getVersion(), (new \DateTime())->format('d F, Y'));

        if (($rc = $api->versionIsPreRelease($version)) !== false) {
            $defaultTitle .= sprintf(Settings::RELEASE_CANDIDATE_TITLE_AMENDMENT, $rc);
            $defaultAnnouncement .= Settings::RELEASE_CANDIDATE_ANNOUNCEMENT_AMENDMENT;
        }

        $this->defaultTitle = $defaultTitle;
        $this->defaultAnnouncement = $defaultAnnouncement;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, [
                'label' => $this->translator->__('Release title'),
                'label_attr' => ['class' => 'col-sm-3'],
                'data' => $this->defaultTitle
            ])
            ->add('description', TextareaType::class, [
                'attr' => ['rows' => 20],
                'label' => $this->translator->__('Release announcement'),
                'label_attr' => ['class' => 'col-sm-3'],
                'data' => $this->defaultAnnouncement,
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
        return 'basicInformation';
    }
}
