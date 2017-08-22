<?php
/**
 * Copyright Zikula Foundation 2014 - Zikula Application Framework
 *
 * This work is contributed to the Zikula Foundation under one or more
 * Contributor Agreements and licensed to You under the following license:
 *
 * @license GNU/LGPv3 (or at your option any later version).
 * @package Zikula
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
 */

namespace Zikula\Module\CoreManagerModule\Manager;

use CarlosIO\Jenkins\Job;

/**
 * This is class is required until the vendor adds support for it.
 *
 * @see https://github.com/carlosbuenosvinos/php-jenkins-api/issues/4
 */
class PipelineJob extends Job
{
    public function getSubJobs()
    {
        return $this->_getItems('jobs', 'Job');
    }
}
