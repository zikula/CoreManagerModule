<?php

namespace Zikula\Module\CoreManagerModule;

use Zikula\Core\AbstractExtensionInstaller;
use Zikula\Module\CoreManagerModule\Entity\CoreReleaseEntity;

class CoreManagerModuleInstaller extends AbstractExtensionInstaller
{
    private $entities = array(
        CoreReleaseEntity::class
    );

    public function install()
    {
        try {
            $this->schemaTool->create($this->entities);
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
            return false;
        }

        return true;
    }

    public function upgrade($oldversion)
    {
        return false;
    }

    public function uninstall()
    {
        try {
            $this->schemaTool->drop($this->entities);
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
            return false;
        }

        return true;
    }
}
