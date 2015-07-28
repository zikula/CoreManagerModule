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

namespace Zikula\Module\CoreManagerModule;

use CarlosIO\Jenkins\Exception\SourceNotAvailableException;
use Doctrine\Common\Collections\ArrayCollection;
use Github\Client as GitHubClient;
use Github\HttpClient\Cache\FilesystemCache;
use Github\HttpClient\CachedHttpClient;
use Github\HttpClient\Message\ResponseMediator;
use vierbergenlars\SemVer\expression;
use vierbergenlars\SemVer\version;
use Zikula\Module\CoreManagerModule\Entity\CoreReleaseEntity;
use Zikula\Module\CoreManagerModule\Entity\ExtensionEntity;
use Zikula\Module\CoreManagerModule\Entity\ExtensionVersionEntity;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use CarlosIO\Jenkins\Dashboard;
use CarlosIO\Jenkins\Source;

class Util
{
    /**
     * Get all available core versions.
     *
     * @return array An array of arrays providing "outdated", "supported" and "dev" core versions.
     *
     * @todo Fetch from GitHub.
     */
    public static function getAvailableCoreVersions($indexBy = 'stateText')
    {
        $releaseManager = \ServiceUtil::get('zikulacoremanagermodule.releasemanager');
        $dbReleases = $releaseManager->getSignificantReleases(false);

        $releases = array();
        foreach ($dbReleases as $dbRelease) {
            if ($indexBy == 'stateText') {
                $key = CoreReleaseEntity::stateToText($dbRelease->getState(), 'plural');
            } else {
                $key = $dbRelease->getState();
            }
            $releases[$key][$dbRelease->getSemver()] = '';
        }

        return $releases;
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
    public static function getGitHubClient($fallBackToNonAuthenticatedClient = true)
    {
        $cacheDir = \CacheUtil::getLocalDir('el/github-api');

        $httpClient = new CachedHttpClient();
        $httpClient->setCache(new FilesystemCache($cacheDir));
        $client = new GitHubClient($httpClient);

        $token = \ModUtil::getVar('ZikulaCoreManagerModule', 'github_token', null);
        if (!empty($token)) {
            $client->authenticate($token, null, GitHubClient::AUTH_HTTP_TOKEN);
            try {
                $client->getHttpClient()->get('rate_limit');
            } catch (\RuntimeException $e) {
                // Authentication failed!
                if ($fallBackToNonAuthenticatedClient) {
                    // Replace client with one not using authentication.
                    $httpClient = new CachedHttpClient();
                    $httpClient->setCache(new FilesystemCache($cacheDir));
                    $client = new GitHubClient($httpClient);
                } else {
                    $client = false;
                }
            }
        }

        return $client;
    }

    /**
     * Determines if the GitHub client has push access to a specifc repository.
     *
     * @param GitHubClient $client
     *
     * @return bool
     */
    public static function hasGitHubClientPushAccess(GitHubClient $client)
    {
        $repo = \ModUtil::getVar('ZikulaCoreManagerModule', 'github_core_repo');
        if (empty($repo)) {
            return false;
        }
        try {
            // One can only show collaborators if one has push access.
            ResponseMediator::getContent($client->getHttpClient()->get('repos/' . $repo . "/collaborators"));
            return true;
        } catch (\Github\Exception\RuntimeException $e) {
            return false;
        }

    }

    /**
     * Returns a Jenkins API client or false if the jenkins server is not available.
     *
     * @return bool|Dashboard
     */
    public static function getJenkinsClient()
    {
        $jenkinsServer = self::getJenkinsURL();
        if ($jenkinsServer === false) {
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
     * @return bool|mixed|string
     */
    public static function getJenkinsURL()
    {
        $jenkinsServer = trim(\ModUtil::getVar('ZikulaCoreManagerModule', 'jenkins_server', ''), '/');
        if (empty($jenkinsServer)) {
            return false;
        }
        $jenkinsUser = \ModUtil::getVar('ZikulaCoreManagerModule', 'jenkins_user', '');
        $jenkinsPassword = \ModUtil::getVar('ZikulaCoreManagerModule', 'jenkins_password', '');
        if (!empty($jenkinsUser) && !empty($jenkinsPassword)) {
            $jenkinsServer = str_replace('://', "://" . urlencode($jenkinsUser) . ":" . urlencode($jenkinsPassword) . '@', $jenkinsServer);
        }
        return $jenkinsServer;
    }
} 
