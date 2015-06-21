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

namespace Cmfcmf\Module\CoreManagerModule\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Zikula\Core\Response\PlainResponse;
use Cmfcmf\Module\CoreManagerModule\Exception\ClientException;
use Cmfcmf\Module\CoreManagerModule\Exception\ServerException;
use Cmfcmf\Module\CoreManagerModule\Manager\ReleaseManager;
use Cmfcmf\Module\CoreManagerModule\Manager\PayloadManager;

/**
 * Jenkins and GitHub Webhook access points.
 */
class WebHookController extends \Zikula_AbstractController
{
    /**
     * @Route("/webhook-core", options={"i18n"=false})
     * @Method("POST")
     */
    public function coreAction(Request $request)
    {
        try {
            $payloadManager = new PayloadManager($request, true);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }

        $securityToken = $this->getVar('github_webhook_token');
        if (!empty($securityToken)) {
            $signature = $request->headers->get('X-Hub-Signature');
            if (empty($signature)) {
                return new PlainResponse('Missing security token!', Response::HTTP_BAD_REQUEST);
            }
            $computedSignature = $this->computeSignature($payloadManager->getRawPayload(), $securityToken);

            if (!$this->secure_equals($computedSignature, $signature)) {
                return new PlainResponse('Signature did not match!', Response::HTTP_BAD_REQUEST);
            }
        }

        $event = $this->request->headers->get('X-Github-Event');
        if (empty($event)) {
            return new PlainResponse('"X-Github-Event" header is missing!', Response::HTTP_BAD_REQUEST);
        }
        $useragent = $request->headers->get('User-Agent');
        if (strpos($useragent, 'GitHub-Hookshot/') !== 0) {
            // User agent does not match "GitHub-Hookshot/*"
            return new PlainResponse('User-Agent not allowed!', Response::HTTP_BAD_REQUEST);
        }

        if ($event != 'release') {
            // We do not listen to that event.
            return new PlainResponse('Event ignored!', Response::HTTP_OK);
        }

        $jsonPayload = $payloadManager->getJsonPayload();
        // See https://developer.github.com/v3/activity/events/types/#releaseevent
        if ($jsonPayload['action'] != 'published') {
            return new PlainResponse('Release event ignored (action != "published")!', Response::HTTP_OK);
        }

        $repo = $this->getVar('github_core_repo', 'zikula/core');
        if ($jsonPayload['repository']['full_name'] != $repo) {
            return new PlainResponse('Release event ignored (repository != "' . $repo . '")!', Response::HTTP_BAD_REQUEST);
        }

        /** @var ReleaseManager $releaseManager */
        $releaseManager = $this->get('cmfcmfcoremanagermodule.releasemanager');
        $releaseManager->updateGitHubRelease($jsonPayload['release']);

        return new PlainResponse('Release list reloaded!', Response::HTTP_OK);
    }

    /**
     * @Route("/webhook-jenkins/{code}", options={"i18n"=false})
     * @Method("POST")
     */
    public function jenkinsAction($code)
    {
        if (!$this->secure_equals($code, $this->getVar('jenkins_token', ''))) {
            throw new AccessDeniedHttpException();
        }

        $releaseManager = $this->get('cmfcmfcoremanagermodule.releasemanager');
        $releaseManager->reloadReleases('jenkins');

        return new PlainResponse('Jenkins builds reloaded.', Response::HTTP_OK);
    }

    /**
     * Compute signature from payload using the security token.
     *
     * @param $payload
     * @param $securityToken
     *
     * @return string
     */
    private function computeSignature($payload, $securityToken)
    {
        return 'sha1=' . hash_hmac('sha1', $payload, $securityToken);
    }

    /**
     * Compares two strings $a and $b in length-constant time.
     *
     * @param $a
     * @param $b
     *
     * @return bool
     *
     * https://crackstation.net/hashing-security.htm#slowequals
     */
    private function secure_equals($a, $b)
    {
        $diff = strlen($a) ^ strlen($b);
        for($i = 0; $i < strlen($a) && $i < strlen($b); $i++) {
            $diff |= ord($a[$i]) ^ ord($b[$i]);
        }

        return $diff === 0;
    }

    private function handleException(\Exception $e)
    {
        switch (true) {
            case $e instanceof ClientException:
                $text = $e->getMessage();
                $code = $e->getCode();
                break;
            case $e instanceof ServerException:
                $text = "Something went wrong at our server. Please report this issue.\n\n{$e->getMessage()}\n{$e->getTraceAsString()}";
                $code = $e->getCode();
                break;
            default:
                $text = "Something unexpected happend. Please report this issue.\n\n{$e->getMessage()}\n{$e->getTraceAsString()}";
                $code = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        return new PlainResponse($text, $code);
    }
}
