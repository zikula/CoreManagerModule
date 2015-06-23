<?php
/**
 * Copyright Zikula Foundation 2014 - Zikula Application Framework
 *
 * This work is contributed to the Zikula Foundation under one or more
 * Contributor Agreements and licensed to You under the following license:
 *
 * @license MIT
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
 */

namespace Zikula\Module\CoreManagerModule\Helper;

use Zikula\Module\CoreManagerModule\Entity\CoreReleaseEntity;
use Zikula\Core\RouteUrl;
use Zikula\Module\SearchModule\AbstractSearchable;
use SecurityUtil;

class SearchHelper extends AbstractSearchable
{
    /**
     * get the UI options for search form
     *
     * @param boolean $active
     * @param array|null $modVars
     * @return string
     */
    public function getOptions($active, $modVars = null)
    {
        if (SecurityUtil::checkPermission($this->name . '::', '::', ACCESS_READ)) {
            $this->view->assign('active', $active);
            return $this->view->fetch('Search/options.tpl');
        }

        return '';
    }

    /**
     * Get the search results
     *
     * @param array $words array of words to search for
     * @param string $searchType AND|OR|EXACT
     * @param array|null $modVars module form vars passed though
     * @return array
     */
    public function getResults(array $words, $searchType = 'AND', $modVars = null)
    {
        // this is an 'eager' search - it doesn't compensate for search type indicated in search UI
        $results = $this->entityManager->getRepository('ZikulaCoreManagerModule:CoreReleaseEntity')->getByFragment($words);

        $sessionId = session_id();
        $records = array();
        foreach ($results as $result) {
            /** @var $result CoreReleaseEntity */
            $records[] = array(
                'title' => $result->getName(),
                'text' => $result->getDescription(),
                'module' => $this->name,
                'sesid' => $sessionId,
                'url' => new RouteUrl('zikulacoremanagermodule_user_viewcorereleases'),
            );
        }

        return $records;
    }
} 
