<?php

namespace Zikula\Module\CoreManagerModule\Manager;

use Github\HttpClient\Message\ResponseMediator;
use Zikula\Module\CoreManagerModule\Util;
use vierbergenlars\SemVer\version;

class JenkinsApiWrapper
{
    protected $jenkinsClient;
    protected $core;
    protected $coreRepository;
    protected $coreOrganization;
    protected $jenkinsURL;

    public function __construct()
    {
        $this->jenkinsClient = Util::getJenkinsClient();
        $this->jenkinsURL = Util::getJenkinsURL();
        $this->core = $core = \ModUtil::getVar('ZikulaCoreManagerModule', 'github_core_repo');
        $core = explode('/', $core);
        $this->coreOrganization = $core[0];
        $this->coreRepository = $core[1];
    }

    public function promoteBuild($job, $build, $level)
    {
        list ($status, ) = $this->doGet("/job/$job/$build/promote", ['level' => $level]);
        if ($status != 200) {
            return false;
        }
        return true;
    }

    public function lockBuild($job, $build)
    {
        list ($status, $response) = $this->doGet("/job/$job/$build/api/json", []);
        if ($status != 200) {
            return false;
        }
        $build = json_decode($response, true);
        if (!$build['keepLog']) {
            list ($status, ) = $this->doPost("/job/$job/$build/toggleLogKeep", []);
            if ($status != 200) {
                return false;
            }
            return true;
        }
        return true;
    }

    public function setBuildDescription($job, $build, $description)
    {
        list ($status, ) = $this->doGet("/job/$job/$build/submitDescription", ['description' => $description]);
        if ($status != 200) {
            return false;
        }
        return true;
    }

    public function copyJob($job, $newName)
    {
        list ($status, ) = $this->doPost("/api", ['name' => $newName, 'mode' => 'copy', 'from' => $job]);
        if ($status != 200) {
            return false;
        }
        return true;
    }

    public function setConfigXML($job)
    {
        return false;
    }

    public function enableJob($job)
    {
        list ($status, ) = $this->doPost("/job/$job/enable", []);
        if ($status != 200) {
            return false;
        }
        return true;
    }

    public function disableJob($job)
    {
        list ($status, ) = $this->doPost("/job/$job/disable", []);
        if ($status != 200) {
            return false;
        }
        return true;
    }

    private function doPost($api, $data)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->jenkinsURL . $api);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$status, $response];
    }

    private function doGet($api, $data)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->jenkinsURL . $api . "?" . http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$status, $response];
    }
}
