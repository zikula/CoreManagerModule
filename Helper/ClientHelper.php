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

use CarlosIO\Jenkins\Exception\SourceNotAvailableException;
use Github\Client as GitHubClient;
use Github\HttpClient\Cache\FilesystemCache;
use Github\HttpClient\CachedHttpClient;
use Github\HttpClient\Message\ResponseMediator;
use Github\ResultPager;
use Zikula\ExtensionsModule\Api\ApiInterface\VariableApiInterface;
use CarlosIO\Jenkins\Dashboard;
use CarlosIO\Jenkins\Source;

class ClientHelper
{
    /**
     * @var VariableApiInterface
     */
    private $variableApi;

    /**
     * @var string
     */
    private $cacheDir;

    /**
     * ClientHelper constructor.
     * @param VariableApiInterface $variableApi
     * @param string $cacheDir
     */
    public function __construct(VariableApiInterface $variableApi, $cacheDir)
    {
        $this->variableApi = $variableApi;
        $this->cacheDir = $cacheDir;
    }

    /**
     * Get an instance of the GitHub Client, authenticated with the admin's authentication token.
     *
     * @param bool $fallBackToNonAuthenticatedClient Whether or not to fall back to a non-authenticated client if
     *                                               authentication fails, default true.
     *
     * @return GitHubClient|bool The authenticated GitHub client, or false if $fallBackToNonAuthenticatedClient
     * is false and the client could not be authenticated.
     */
    public function getGitHubClient($fallBackToNonAuthenticatedClient = true)
    {
        $httpClient = new CachedHttpClient();
        $httpClient->setCache(new FilesystemCache($this->cacheDir . 'el/github-api'));
        $client = new GitHubClient($httpClient);

        $token = $this->variableApi->get('ZikulaCoreManagerModule', 'github_token', null);
        if (!empty($token)) {
            $client->authenticate($token, null, GitHubClient::AUTH_HTTP_TOKEN);
            try {
                $client->getHttpClient()->get('rate_limit');
            } catch (\RuntimeException $exception) {
                // Authentication failed!
                if ($fallBackToNonAuthenticatedClient) {
                    // Replace client with one not using authentication.
                    $httpClient = new CachedHttpClient();
                    $httpClient->setCache(new FilesystemCache($this->cacheDir . 'el/github-api'));
                    $client = new GitHubClient($httpClient);
                } else {
                    die('Error: ' . $exception->getMessage());
                    $client = false;
                }
            }
        }

        return $client;
    }

    /**
     * Determines if the GitHub client has push access to a specifc repository.
     *
     * @param $client
     *
     * @return bool
     */
    public function hasGitHubClientPushAccess($client)
    {
        if (!($client instanceof GitHubClient)) {
            return false;
        }
        $repo = $this->variableApi->get('ZikulaCoreManagerModule', 'github_core_repo');
        if (empty($repo)) {
            return false;
        }

        try {
            // One can only show collaborators if one has push access to an organization
            ResponseMediator::getContent($client->getHttpClient()->get('repos/' . $repo . "/collaborators"));

            return true;
        } catch (\Github\Exception\RuntimeException $e) { }

        // See if maybe we only are a member of a non-organization repository
        $paginator = new ResultPager($client);
        $reposWhereMember = $paginator->fetchAll($client->api('me'), 'repositories', ['member']);

        return in_array($repo, array_map(function ($repoWhereMember) {
            return $repoWhereMember['full_name'];
        }, $reposWhereMember));
    }

    /**
     * Returns a Jenkins API client or false if the jenkins server is not available.
     *
     * @return bool|Dashboard
     */
    public function getJenkinsClient()
    {
        $jenkinsServer = $this->getJenkinsURL(false);
        if (false === $jenkinsServer) {
            return false;
        }

        $dashboard = new Dashboard();
        $dashboard->addSource(new Source($jenkinsServer . '/view/All/api/json/?depth=2'));
        try {
            // Dummy call to getJobs to test if Jenkins is available.
            $dashboard->getJobs();
        } catch (SourceNotAvailableException $e) {
            return false;
        }

        return $dashboard;
    }

    /**
     * @param boolean $needsAuthentication
     * @return bool|mixed|string
     */
    public function getJenkinsURL($needsAuthentication = true)
    {
        $jenkinsServer = trim($this->variableApi->get('ZikulaCoreManagerModule', 'jenkins_server', ''), '/');
        if (empty($jenkinsServer)) {
            return false;
        }
        if (true === $needsAuthentication) {
            $jenkinsUser = $this->variableApi->get('ZikulaCoreManagerModule', 'jenkins_user', '');
            $jenkinsPassword = $this->variableApi->get('ZikulaCoreManagerModule', 'jenkins_password', '');
            if (!empty($jenkinsUser) && !empty($jenkinsPassword)) {
                $jenkinsServer = str_replace('://', '://' . urlencode($jenkinsUser) . ':' . urlencode($jenkinsPassword) . '@', $jenkinsServer);
            }
        }

        return $jenkinsServer;
    }
}
