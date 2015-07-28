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
    
    private $OK_STATI = [200, 302];

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
        list ($status, ) = $this->doGet("/job/" . urlencode($job) . "/$build/promote/", ['level' => $level]);
        if (!in_array($status, $this->OK_STATI)) {
            return false;
        }
        return true;
    }

    public function lockBuild($job, $build)
    {
        list ($status, $response) = $this->doGet("/job/" . urlencode($job) . "/$build/api/json", []);
        if (!in_array($status, $this->OK_STATI)) {
            return false;
        }
        $buildArr = json_decode($response, true);
        if (!$buildArr['keepLog']) {
            list ($status, ) = $this->doPost("/job/" . urlencode($job) . "/$build/toggleLogKeep", []);
            if (!in_array($status, $this->OK_STATI)) {
                return false;
            }
            return true;
        }
        return true;
    }

    public function getBuildDescription($job, $build)
    {
        list ($status, $response) = $this->doGet("/job/" . urlencode($job) . "/$build/api/json", []);
        if (!in_array($status, $this->OK_STATI)) {
            return false;
        }
        $buildArr = json_decode($response, true);

        return $buildArr['description'];
    }

    public function setBuildDescription($job, $build, $description)
    {
        list ($status, ) = $this->doGet("/job/" . urlencode($job) . "/$build/submitDescription", ['description' => $description]);
        if (!in_array($status, $this->OK_STATI)) {
            return false;
        }
        return true;
    }

    public function copyJob($job, $newName)
    {
        list ($status, ) = $this->doPost("/createItem", ['name' => $newName, 'mode' => 'copy', 'from' => $job]);
        if (!in_array($status, $this->OK_STATI)) {
            return false;
        }
        return true;
    }

    public function enableJob($job)
    {
        list ($status, ) = $this->doPost("/job/" . urlencode($job) . "/enable", []);
        if (!in_array($status, $this->OK_STATI)) {
            return false;
        }
        return true;
    }

    public function disableJob($job)
    {
        list ($status, ) = $this->doPost("/job/" . urlencode($job) . "/disable", []);
        if (!in_array($status, $this->OK_STATI)) {
            return false;
        }
        return true;
    }

    public function getAssets($job, $build)
    {
        list($status, $response) = $this->doGet("/job/" . urlencode($job) . "/$build/api/json", []);
        if (!in_array($status, $this->OK_STATI)) {
            return false;
        }
        $artifacts = json_decode($response);
        $artifacts = $artifacts->artifacts;
        $assets = array();
        foreach ($artifacts as $artifact) {
            $downloadUrl = $this->jenkinsURL . '/job/' . urlencode($job) . '/' . $build . '/artifact/' . $artifact->relativePath;
            $fileExtension = pathinfo($artifact->fileName, PATHINFO_EXTENSION);
            $contentType = null;
            switch ($fileExtension) {
                case 'zip':
                    $contentType = 'application/zip';
                    break;
                case 'gz':
                    $contentType = 'application/gzip';
                    break;
                case 'txt':
                    $contentType = 'text/plain';
                    break;
                default:
                    $contentType = null;
            }
            $assets[] = array (
                'name' => $artifact->fileName,
                'download_url' => $downloadUrl,
                'content_type' => $contentType
            );
        }

        return $assets;
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
