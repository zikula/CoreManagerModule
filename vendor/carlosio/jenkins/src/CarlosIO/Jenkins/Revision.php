<?php
namespace CarlosIO\Jenkins;

use CarlosIO\Jenkins\BaseObject;

class Revision extends BaseObject
{
    public function getModule()
    {
        return $this->_json->module;
    }

    public function getRevision()
    {
        return $this->_json->revision;
    }
}
