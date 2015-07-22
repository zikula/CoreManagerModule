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
        $defaultAnnouncement = 'Zikula Core **' . $this->getData()['version'] . '** is available as of today, ' . (new \DateTime())->format('d F, Y') . '.';
        if (($rc = $this->api->versionIsPreRelease($version)) !== false) {
            $defaultTitle = 'Zikula Core ' . $this->api->versionToMajorMinorPatch($version) . " Release Candidate $rc";
            $defaultAnnouncement .= <<<EOD
 Immediate testing is encouraged. You may download the RC from our [links at zikula.org](http://zikula.org/library/releases). Release testing guidelines may be found in [the Core wiki](https://github.com/zikula/core/wiki/Release-Testing-Guidelines). Installation and upgrade documentation can be found in the /docs directory.

Our Quality Assurance cycle, explained also in [the Core wiki](https://github.com/zikula/core/wiki/Release-Management#release-candidates-rc), will be followed in order to achieve our General Release. Please register your vote on the promotion of this build in the [promotion ticket](%QATICKETURL%).

Please report all bugs and concerns to our [issue tracker on Github](https://github.com/zikula/core/issues). Please understand that bugs will not necessarily halt the release of this build. Bugs may be fixed or postponed to another release.
EOD;
        } else {
            $defaultTitle = 'Zikula Core ' . $version->getVersion();
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
