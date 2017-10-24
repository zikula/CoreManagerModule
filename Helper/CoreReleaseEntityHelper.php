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

use Zikula\Common\Translator\TranslatorInterface;
use Zikula\Common\Translator\TranslatorTrait;
use Zikula\Module\CoreManagerModule\Entity\CoreReleaseEntity;

class CoreReleaseEntityHelper
{
    use TranslatorTrait;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function setTranslator($translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param $state
     * @param string $singularPlural
     * @return mixed
     */
    public function stateToText($state, $singularPlural = 'singular')
    {
        if ($singularPlural == 'singular') {
            $translation = [
                CoreReleaseEntity::STATE_OUTDATED => $this->__('Outdated version'),
                CoreReleaseEntity::STATE_DEVELOPMENT => $this->__('Development version'),
                CoreReleaseEntity::STATE_PRERELEASE => $this->__('Prerelease'),
                CoreReleaseEntity::STATE_SUPPORTED => $this->__('Supported version')
            ];
        } else {
            $translation = [
                CoreReleaseEntity::STATE_OUTDATED => $this->__('Outdated versions'),
                CoreReleaseEntity::STATE_DEVELOPMENT => $this->__('Development versions'),
                CoreReleaseEntity::STATE_PRERELEASE => $this->__('Prereleases'),
                CoreReleaseEntity::STATE_SUPPORTED => $this->__('Supported versions')
            ];
        }

        return $translation[$state];
    }
}
