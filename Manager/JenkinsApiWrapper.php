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

    public function __construct()
    {
        $this->jenkinsClient = Util::getJenkinsClient();
        $this->core = $core = \ModUtil::getVar('ZikulaCoreManagerModule', 'github_core_repo');
        $core = explode('/', $core);
        $this->coreOrganization = $core[0];
        $this->coreRepository = $core[1];
    }

    public function getBuildsOfCommit($version, $commit)
    {
        $jobs = $dashboard->getJobs();
        foreach ($jobs as $job) {
            
        }
    }
}
