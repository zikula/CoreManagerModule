<?php

namespace Zikula\Module\CoreManagerModule\Manager;

use Github\HttpClient\Message\ResponseMediator;
use Zikula\Module\CoreManagerModule\Util;

class GitHubApiWrapper
{
    protected $githubClient;
    protected $coreRepository;
    protected $coreOrganization;

    public function __construct()
    {
        $this->githubClient = Util::getGitHubClient(false);
        $this->coreOrganization = 'zikula';
        $this->coreRepository = 'core';
    }

    public function getReleases()
    {
        $result = $this->githubClient->repository()->releases()->all($this->coreOrganization, $this->coreRepository);

        return $result;
    }
}
