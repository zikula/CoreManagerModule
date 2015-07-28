<?php

namespace Zikula\Module\CoreManagerModule\Stage;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\DateTime;
use vierbergenlars\SemVer\version;
use Zikula\Component\Wizard\AbortStageException;
use Zikula\Component\Wizard\FormHandlerInterface;
use Zikula\Component\Wizard\InjectContainerInterface;
use Zikula\Component\Wizard\StageInterface;
use Zikula\Module\CoreManagerModule\Form\Type\BasicInformationType;
use Zikula\Module\CoreManagerModule\Manager\GitHubApiWrapper;
use Zikula\Module\CoreManagerModule\Settings;

class BasicInformationStage extends AbstractStage
{
    /**
     * @var GitHubApiWrapper
     */
    protected $api;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->api = $this->container->get('zikula_core_manager_module.github_api_wrapper');
    }

    /**
     * Returns an instance of a Symfony Form Type
     *
     * @return \Symfony\Component\Form\FormTypeInterface
     */
    public function getFormType()
    {
        /** @var version $version */
        $version = new version($this->getData()['version']);
        $defaultTitle = sprintf(Settings::RELEASE_TITLE, $this->api->versionToMajorMinorPatch($version));
        $defaultAnnouncement = sprintf(Settings::RELEASE_ANNOUNCEMENT, $version->getVersion(), (new \DateTime())->format('d F, Y'));

        if (($rc = $this->api->versionIsPreRelease($version)) !== false) {
            $defaultTitle .= sprintf(Settings::RELEASE_CANDIDATE_TITLE_AMENDMENT, $rc);
            $defaultAnnouncement .= Settings::RELEASE_CANDIDATE_ANNOUNCEMENT_AMENDMENT;
        }

        return new BasicInformationType($defaultTitle, $defaultAnnouncement);
    }

    /**
     * Logic to determine if the stage is required or can be skipped
     *
     * @return boolean
     * @throws AbortStageException
     */
    public function isNecessary()
    {
        $data = $this->getData();
        if (empty($data['build'])) {
            throw new \LogicException('Build not yet set!');
        }

        return true;
    }
}
