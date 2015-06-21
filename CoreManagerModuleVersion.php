<?php

namespace Cmfcmf\Module\CoreManagerModule;

use Zikula\Module\SearchModule\AbstractSearchable;

class CoreManagerModuleVersion extends \Zikula_AbstractVersion
{
    public function getMetaData()
    {
        $meta = array();
        $meta['displayname']    = $this->__('Core Manager');
        $meta['description']    = $this->__('Manages Core Releases');
        $meta['url']            = $this->__('releases');
        $meta['version']        = '1.0.0';
        $meta['core_min']       = '1.4.0';
        $meta['core_max']       = '1.4.99';
        $meta['securityschema'] = array('CmfcmfCoreManagerModule::' => '::');
        $meta['capabilities']   = array(
            AbstractSearchable::SEARCHABLE => array('class' => 'Cmfcmf\Module\CoreManagerModule\Helper\SearchHelper'),
        );
        return $meta;
    }
}
