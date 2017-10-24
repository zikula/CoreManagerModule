<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\Module\CoreManagerModule\Helper;

use Doctrine\ORM\EntityManagerInterface;
use MU\NewsModule\Entity\Factory\EntityFactory;
use MU\NewsModule\Helper\WorkflowHelper;
use Zikula\Bundle\CoreBundle\HttpKernel\ZikulaHttpKernelInterface;
use Zikula\Common\Translator\TranslatorInterface;
use Zikula\Common\Translator\TranslatorTrait;
use Zikula\Module\CoreManagerModule\Entity\CoreReleaseEntity;

class AnnouncementHelper
{
    use TranslatorTrait;

    /**
     * @var ZikulaHttpKernelInterface
     */
    private $kernel;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var EntityFactory
     */
    private $entityFactory = null;

    /**
     * @var WorkflowHelper
     */
    private $workflowHelper = null;

    /**
     * @param TranslatorInterface $translator
     * @param ZikulaHttpKernelInterface $kernel
     * @param EntityManagerInterface $em
     */
    public function __construct(
        TranslatorInterface $translator,
        ZikulaHttpKernelInterface $kernel,
        EntityManagerInterface $em
    ) {
        $this->setTranslator($translator);
        $this->kernel = $kernel;
        $this->em = $em;
    }

    public function setTranslator($translator)
    {
        $this->translator = $translator;
    }

    /**
     * Sets News entity factory reference.
     *
     * @param EntityFactory $entityFactory
     */
    public function setNewsEntityFactory(EntityFactory $entityFactory)
    {
        $this->entityFactory = $entityFactory;
    }

    /**
     * Sets News workflow helper reference.
     *
     * @param WorkflowHelper $workflowHelper
     */
    public function setNewsWorkflowHelper(WorkflowHelper $workflowHelper)
    {
        $this->workflowHelper = $workflowHelper;
    }

    /**
     * Creates a news article about a new release.
     *
     * @param CoreReleaseEntity $release
     */
    public function createNewsArticle(CoreReleaseEntity $release)
    {
        if (!$this->kernel->isBundle('MUNewsModule')) {
            return;
        }

        $title = $teaser = '';
        switch ($release->getState()) {
            case CoreReleaseEntity::STATE_SUPPORTED:
                $title = $this->__f('%s released!', ['%s' => $release->getNameI18n()]);
                $teaser = '<p>' . $this->__f('The core development team is proud to announce the availabilty of %s.', ['%s' => $release->getNameI18n()]) . '</p>';
                break;
            case CoreReleaseEntity::STATE_PRERELEASE:
                $title = $this->__f('%s ready for testing!', ['%s' => $release->getNameI18n()]);
                $teaser = '<p>' . $this->__f('The core development team is proud to announce a pre-release of %s. Please help testing and report bugs!', ['%s' => $release->getNameI18n()]) . '</p>';
                break;
            case CoreReleaseEntity::STATE_DEVELOPMENT:
            case CoreReleaseEntity::STATE_OUTDATED:
            default:
                // Do not create news post.
                return;
        }

        $body = $this->getNewsText($release);

        $article = $this->entityFactory->createMessage();
        $article->setTitle($title);
        $article->setStartText($teaser);
        $article->setMainText($body);
        $article->setAuthor('Admin');

        $this->workflowHelper->executeAction($article, 'approve');

        $id = $article->getId();

        if (is_numeric($id) && $id > 0) {
            $release->setNewsId($id);
            $this->em->merge($release);
            $this->em->flush();
        }
    }

    /**
     * Updates download links of a news article.
     *
     * @param CoreReleaseEntity $release
     */
    public function updateNewsArticle(CoreReleaseEntity $release)
    {
        if (null === $release->getNewsId() || !$this->kernel->isBundle('MUNewsModule')) {
            return;
        }

        $article = $this->entityFactory->getRepository('message')->selectById($release->getNewsId());
        if (!$article) {
            return;
        }

        $body = $this->getNewsText($release);

        $body = preg_replace(
            '#' . preg_quote(CoreReleaseEntity::NEWS_DESCRIPTION_START) . '.*?' . preg_quote(CoreReleaseEntity::NEWS_DESCRIPTION_END) . '#',
            $body,
            $article->getMainText()
        );

        $article->setMainText($body);

        $this->workflowHelper->executeAction($article, 'updateapproved');
    }

    /**
     * Get a news text to use for this core release.
     *
     * @param CoreReleaseEntity $coreReleaseEntity
     * @return string
     */
    private function getNewsText(CoreReleaseEntity $coreReleaseEntity)
    {
        $downloadLinks = '';
        if (count($coreReleaseEntity->getAssets()) > 0) {
            $downloadLinkTpl = '<a href="%link%" class="btn btn-success btn-sm">%text%</a>';
            foreach ($coreReleaseEntity->getAssets() as $asset) {
                $downloadLinks .= str_replace('%link%', $asset['download_url'], str_replace('%text%', $asset['name'], $downloadLinkTpl));
            }
        } else {
            $downloadLinks .= '<p class="alert alert-warning">' .
                $this->__('Direct download links not yet available!') . '</p>';
        }

        return CoreReleaseEntity::NEWS_DESCRIPTION_START .
            $coreReleaseEntity->getDescriptionI18n() . $downloadLinks .
            CoreReleaseEntity::NEWS_DESCRIPTION_END;
    }
}
