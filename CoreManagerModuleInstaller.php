<?php

namespace Zikula\Module\CoreManagerModule;

use Zikula\Core\AbstractExtensionInstaller;
use Zikula\Module\CoreManagerModule\Entity\CoreReleaseEntity;

class CoreManagerModuleInstaller extends AbstractExtensionInstaller
{
    private $entities = [
        CoreReleaseEntity::class
    ];

    public function install()
    {
        $this->schemaTool->create($this->entities);

        return true;
    }

    public function upgrade($oldVersion)
    {
        return false;
    }

    public function uninstall()
    {
        $this->schemaTool->drop($this->entities);

        return true;
    }
}
