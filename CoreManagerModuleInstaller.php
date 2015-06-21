<?php

namespace Cmfcmf\Module\CoreManagerModule;

use DoctrineHelper;

class CoreManagerModuleInstaller extends \Zikula_AbstractInstaller
{
    private $entities = array(
        'Cmfcmf\Module\CoreManagerModule\Entity\CoreReleaseEntity'
    );

    public function install()
    {
        try {
            DoctrineHelper::createSchema($this->entityManager, $this->entities);
        } catch (\Exception $e) {
            $this->request->getSession()->getFlashBag()->add('error', $e->getMessage());
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
            DoctrineHelper::dropSchema($this->entityManager, $this->entities);
        } catch (\Exception $e) {
            $this->request->getSession()->getFlashBag()->add('error', $e->getMessage());
            return false;
        }

        return true;
    }
}
