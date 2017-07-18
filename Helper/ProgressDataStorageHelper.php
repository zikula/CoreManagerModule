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

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ProgressDataStorageHelper
{
    const SESSION_VAR = 'ZikulaCoreManagerModule_release';

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @param SessionInterface $session
     */
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    public function getData()
    {
        $data = $this->session->get(self::SESSION_VAR);
        if (empty($data) || $data === "null" || $data === "false" || $data === "Array") {
            return [];
        }
        $result = json_decode($data, true);

        return (json_last_error() == JSON_ERROR_NONE) ? $result : [];
    }

    public function addData($data)
    {
        return $this->session->set(self::SESSION_VAR, json_encode(array_merge($this->getData(), $data)));
    }

    public function setData($data)
    {
        $this->session->set(self::SESSION_VAR, json_encode($data));
    }
}