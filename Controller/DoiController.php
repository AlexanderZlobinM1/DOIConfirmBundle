<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\DOIConfirmBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\DOIConfirmBundle\Helper\Base64Helper;
use MauticPlugin\DOIConfirmBundle\Helper\DoiActionHelper;
use MauticPlugin\DOIConfirmBundle\Helper\NotHumanClickHelper;
use MauticPlugin\DOIConfirmBundle\Message\DoiConfirmationMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

/**
 * Class DoiController.
 */
class DoiController extends FormController
{
    private const DOI_PROCESSING_DELAY_MS = 60000;

    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                'mautic.helper.encryption' => EncryptionHelper::class,
                'mautic.lead.model.lead' => LeadModel::class,
                'messenger.default_bus' => MessageBusInterface::class,
                'jw.doi.actionhelper' => DoiActionHelper::class,
                'jw.doi.nothumanclickhelper' => NotHumanClickHelper::class,
                'monolog.logger.mautic' => LoggerInterface::class,
            ]
        );
    }

    private function getLeadModel(): LeadModel
    {
        return $this->container->get('mautic.lead.model.lead');
    }

    private function getEncryptionHelper(): EncryptionHelper
    {
        return $this->container->get('mautic.helper.encryption');
    }

    private function getMessageBus(): MessageBusInterface
    {
        return $this->container->get('messenger.default_bus');
    }

    private function getDoiActionHelper(): DoiActionHelper
    {
        return $this->container->get('jw.doi.actionhelper');
    }

    private function getNotHumanClickHelper(): NotHumanClickHelper
    {
        return $this->container->get('jw.doi.nothumanclickhelper');
    }

    private function getMauticLogger(): LoggerInterface
    {
        return $this->container->get('monolog.logger.mautic');
    }

    /**
     * @param string|false $enc
     *
     * @return array<string, mixed>
     */
    protected function decryptDoiActions($enc): array
    {
        //Get doi parameters
        if (!$enc) {
            throw new BadRequestHttpException('Missing DOI payload.');
        }

        //get base64 string
        $base64 = Base64Helper::prepare_base64_url_decode($enc);
        if (!is_string($base64) || !str_contains($base64, '|')) {
            throw new AccessDeniedHttpException('Invalid DOI payload.');
        }

        //decrypt string
        $config = $this->getEncryptionHelper()->decrypt($base64, true);
        if (!$config || !is_array($config)) {
            throw new AccessDeniedHttpException('Invalid DOI payload.');
        }

        $leadId = $config['lead_id'] ?? null;
        if (!$leadId) {
            throw new BadRequestHttpException('Lead not found for DOI payload.');
        }

        $lead = $this->getLeadModel()->getEntity($leadId);
        if (!$lead) {
            throw new BadRequestHttpException('Lead not found for DOI payload.');
        }
        $leadEmail = method_exists($lead, 'getEmail') ? $lead->getEmail() : null;
        $config['leadEmail'] = $leadEmail;

        return $config;
    }

    /**
     * @return array<string, mixed>
     */
    private function extractRequestContext(Request $request): array
    {
        return [
            'query'   => $request->query->all(),
            'request' => $request->request->all(),
            'cookies' => $request->cookies->all(),
            'server'  => [
                'REMOTE_ADDR'     => (string) $request->server->get('REMOTE_ADDR', ''),
                'HTTP_USER_AGENT' => (string) $request->server->get('HTTP_USER_AGENT', ''),
                'HTTP_HOST'       => (string) $request->server->get('HTTP_HOST', ''),
                'HTTPS'           => (string) $request->server->get('HTTPS', ''),
                'SERVER_PORT'     => (string) $request->server->get('SERVER_PORT', ''),
                'REQUEST_URI'     => (string) $request->server->get('REQUEST_URI', ''),
                'REQUEST_METHOD'  => $request->getMethod(),
            ],
            'uri' => $request->getUri(),
        ];
    }

    /**
     * Doi confirmation action
     *
     * @param string $enc
     */
    public function indexAction($enc = false): Response
    {
        //try to decrypt doi action config
        $config = $this->decryptDoiActions($enc);
        $request = $this->getCurrentRequest();
        $requestContext = $request instanceof Request ? $this->extractRequestContext($request) : [];

        try {
            $this->getMessageBus()->dispatch(
                new DoiConfirmationMessage($config, $requestContext),
                [new DelayStamp(self::DOI_PROCESSING_DELAY_MS)]
            );
        } catch (HandlerFailedException $exception) {
            // Handler was already executed (likely sync transport), so do not run fallback to avoid duplicates.
            throw $exception;
        } catch (\Throwable $exception) {
            $this->getMauticLogger()->error(
                'Failed to dispatch DOI confirmation message: '.$exception->getMessage(),
                ['exception' => $exception]
            );

            $this->getDoiActionHelper()->setRequestContext($requestContext);
            $this->getDoiActionHelper()->applyDoiActions($config);
        }

        //redirect to doi success url
        return $this->redirect($config['url'], 302);
    }

    /**
     * Click bait action for email scanning bots
     *
     * @param string $hash
     */
    public function nothumanAction($hash = false): Response
    {
        $this->getNotHumanClickHelper()->setClick($hash);

        return $this->delegateView([
            'viewParameters'  => [],
            'contentTemplate' => '@DOIConfirm/Doi/nothuman.html.twig',
        ]);
    }
}
