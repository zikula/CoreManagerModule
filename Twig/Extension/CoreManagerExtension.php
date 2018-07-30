<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\Module\CoreManagerModule\Twig\Extension;

use Zikula\Common\Translator\TranslatorInterface;
use Zikula\Common\Translator\TranslatorTrait;
use Zikula\Module\CoreManagerModule\Helper\CoreReleaseEntityHelper;
use Zikula\Module\CoreManagerModule\Entity\CoreReleaseEntity;

class CoreManagerExtension extends \Twig_Extension
{
    use TranslatorTrait;

    /**
     * @var CoreReleaseEntityHelper
     */
    private $entityHelper;

    public function __construct(
        TranslatorInterface $translator,
        CoreReleaseEntityHelper $entityHelper
    ) {
        $this->setTranslator($translator);
        $this->entityHelper = $entityHelper;
    }

    public function setTranslator($translator)
    {
        $this->translator = $translator;
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('elReleaseStateToAlert', [$this, 'elReleaseStateToAlert'], ['is_safe' => ['html']]),
            new \Twig_SimpleFilter('elReleaseStateToText', [$this, 'elReleaseStateToText'], ['is_safe' => ['html']])
        ];
    }

    public function elReleaseStateToAlert($state)
    {
        switch ($state) {
            case CoreReleaseEntity::STATE_OUTDATED:
                return "<div class=\"alert alert-warning\"><i class='fa fa-ban fa-3x pull-left'></i> " . $this->__("You are about to download an OUTDATED and no longer supported core version. It does not receive bug fixes or maintenance any longer. Please use one of the supported versions instead.") . "</div>";
            case CoreReleaseEntity::STATE_PRERELEASE:
                return "<div class=\"alert alert-danger\"><i class='fa fa-warning fa-3x pull-left'></i> " . $this->__("This core version is a pre-release only. NEVER use it on production sites. If you like to help, we invite you to test this version and report bugs.") . "</div>";
            case CoreReleaseEntity::STATE_DEVELOPMENT:
                return "<div class=\"alert alert-danger\"><i class='fa fa-warning fa-3x pull-left'></i> " . $this->__("DANGER: This is an in-development build. NEVER use it on production sites. It can likely be broken and absolutely not working. Really.") . "</div>";
            case CoreReleaseEntity::STATE_SUPPORTED:
            default:
                return "";
        }
    }

    public function elReleaseStateToText($state, $singularPlural = 'singular')
    {
        return $this->entityHelper->stateToText($state, $singularPlural);
    }
}
