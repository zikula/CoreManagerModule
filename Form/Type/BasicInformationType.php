<?php
/**
 * Copyright Zikula - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\Module\CoreManagerModule\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
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
     * @var string
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
        $versionNumberShort = $api->versionToMajorMinorPatch($version);
        $versionNumberLong = $version->getVersion();
        $defaultTitle = sprintf(Settings::RELEASE_TITLE, $versionNumberShort);
        $defaultAnnouncement = sprintf(Settings::RELEASE_ANNOUNCEMENT, $versionNumberLong, (new \DateTime())->format('d F, Y'));

        $rc = $api->versionIsPreRelease($version);
        if (false !== $rc) {
            $defaultTitle .= sprintf(Settings::RELEASE_CANDIDATE_TITLE_AMENDMENT, $rc);
            $rcAddition = Settings::RELEASE_CANDIDATE_ANNOUNCEMENT_AMENDMENT;
            $rcAddition = str_replace('%QATICKETURL%', '§QATICKETURL§', $rcAddition);
            $rcAddition = sprintf($rcAddition, $versionNumberShort, $versionNumberLong, (new \DateTime())->format('d. F Y'));
            $rcAddition = str_replace('§QATICKETURL§', '%QATICKETURL%', $rcAddition);
            $defaultAnnouncement .= $rcAddition;
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
                'data' => $this->defaultTitle
            ])
            ->add('description', TextareaType::class, [
                'attr' => ['rows' => 20],
                'label' => $this->translator->__('Release announcement'),
                'data' => $this->defaultAnnouncement,
            ])
            ->add('artifactsUrl', UrlType::class, [
                'label' => $this->translator->__('Artifacts download URL'),
                'help' => $this->translator->__f(
                    'Lookup "release-archives" URL of latest build at %coreUrl% for pre releases or at %distUrl% for final releases.',
                    [
                        '%coreUrl%' => 'https://github.com/zikula/core/actions',
                        '%distUrl%' => 'https://github.com/zikula/distribution/actions'
                    ]
                )
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
        return 'basicInformation';
    }
}
