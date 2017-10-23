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
    protected $kernel;

    /**
     * @var EntityManagerInterface
     */
    private $em;

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
     * Creates a news article about a new release.
     *
     * @param CoreReleaseEntity $newRelease
     */
    public function createNewsArticle(CoreReleaseEntity $newRelease)
    {
        if (!$this->kernel->isBundle('MUNewsModule')) {
            return;
        }

        $title = $teaser = '';
        switch ($newRelease->getState()) {
            case CoreReleaseEntity::STATE_SUPPORTED:
                $title = $this->__f('%s released!', ['%s' => $newRelease->getNameI18n()]);
                $teaser = '<p>' . $this->__f('The core development team is proud to announce the availabilty of %s.', ['%s' => $newRelease->getNameI18n()]) . '</p>';
                break;
            case CoreReleaseEntity::STATE_PRERELEASE:
                $title = $this->__f('%s ready for testing!', ['%s' => $newRelease->getNameI18n()]);
                $teaser = '<p>' . $this->__f('The core development team is proud to announce a pre-release of %s. Please help testing and report bugs!', ['%s' => $newRelease->getNameI18n()]) . '</p>';
                break;
            case CoreReleaseEntity::STATE_DEVELOPMENT:
            case CoreReleaseEntity::STATE_OUTDATED:
            default:
                // Do not create news post.
                return;
        }

        // TODO disabled
        /**
        $args = [];
        $args['title'] = $title;
        $args['hometext'] = $teaser;
        $args['bodytext'] = $newRelease->getNewsText(); change to \Zikula\Module\CoreManagerModule\Helper\CoreReleaseEntityHelper::getNewsText
        $args['published_status'] = 1; //\News_Api_User::STATUS_PENDING;

        $id = \ModUtil::apiFunc('News', 'user', 'create', $args);

        if (is_numeric($id) && $id > 0) {
            $newRelease->setNewsId($id);
            $this->em->merge($newRelease);
            $this->em->flush();
        }
        */
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

        // TODO disabled
        /**
        $article = \ModUtil::apiFunc('News', 'user', 'get', array ('sid' => $release->getNewsId()));
        if (!$article) {
            return;
        }

        $article['bodytext'] = preg_replace('#' . preg_quote(CoreReleaseEntity::NEWS_DESCRIPTION_START) . '.*?' . preg_quote(CoreReleaseEntity::NEWS_DESCRIPTION_END) . '#',
            $release->getNewsText(), change to \Zikula\Module\CoreManagerModule\Helper\CoreReleaseEntityHelper::getNewsText
            $article['bodytext']
        );

        \ModUtil::apiFunc('News', 'admin', 'update', $article);
        */
    }
}
