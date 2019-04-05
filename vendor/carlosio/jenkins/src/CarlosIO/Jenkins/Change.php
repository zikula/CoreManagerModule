<?php
namespace CarlosIO\Jenkins;

use CarlosIO\Jenkins\BaseObject;
use CarlosIO\Jenkins\Author;

class Change extends BaseObject
{
    public function getAffectedPaths()
    {
        return $this->_json->affectedPaths;
    }

    public function getAuthor()
    {
        return new Author($this->_json->author);
    }
}
