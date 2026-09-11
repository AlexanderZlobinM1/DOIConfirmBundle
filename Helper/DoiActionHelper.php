<?php

namespace MauticPlugin\DOIConfirmBundle\Helper;

use Mautic\LeadBundle\Event\ContactIdentificationEvent;
use Mautic\LeadBundle\LeadEvents;
use MauticPlugin\DOIConfirmBundle\Event\DoiSuccessful;
use MauticPlugin\DOIConfirmBundle\Helper\LeadHelper;
use MauticPlugin\DOIConfirmBundle\DoiEvents;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use MauticPlugin\DOIConfirmBundle\Service\PluginEnabledResolver;


class DoiActionHelper {

    protected $eventDispatcher;

    protected $ipLookupHelper;

    protected $pageModel;

    protected $emailModel;

    protected $auditLogModel;

    protected $leadModel;

    /**
     * @var Request|null
     */
    protected $request;

    private RequestStack $requestStack;

    private PluginEnabledResolver $pluginEnabledResolver;

    private LoggerInterface $logger;

    public function __construct($eventDispatcher, $ipLookupHelper, $pageModel, $emailModel, $auditLogModel, $leadModel, RequestStack $requestStack, PluginEnabledResolver $pluginEnabledResolver, LoggerInterface $logger)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->ipLookupHelper = $ipLookupHelper;
        $this->pageModel = $pageModel;
        $this->emailModel = $emailModel;
        $this->auditLogModel = $auditLogModel;
        $this->leadModel = $leadModel;
        $this->requestStack = $requestStack;
        $this->request = $requestStack->getCurrentRequest();
        $this->pluginEnabledResolver = $pluginEnabledResolver;
        $this->logger = $logger;
    }

    public function setRequest(?Request $request): void
    {
        $this->request = $request;
    }

    /**
     * @param array<string, mixed>|null $requestContext
     */
    public function setRequestContext(?array $requestContext): void
    {
        if (empty($requestContext)) {
            $this->request = null;

            return;
        }

        $query   = isset($requestContext['query']) && is_array($requestContext['query']) ? $requestContext['query'] : [];
        $request = isset($requestContext['request']) && is_array($requestContext['request']) ? $requestContext['request'] : [];
        $cookies = isset($requestContext['cookies']) && is_array($requestContext['cookies']) ? $requestContext['cookies'] : [];
        $server  = isset($requestContext['server']) && is_array($requestContext['server']) ? $requestContext['server'] : [];

        $this->request = new Request($query, $request, [], $cookies, [], $server);
    }

    public function applyDoiActions($config)
    {
        if (!$this->pluginEnabledResolver->isEnabled()) {
            return;
        }

        $pushedRequest = false;
        if ($this->request instanceof Request) {
            $this->ensureRequestHasSession($this->request);

            if ($this->requestStack->getCurrentRequest() !== $this->request) {
                $this->requestStack->push($this->request);
                $pushedRequest = true;
            }
        }

        try {
            $this->logDoiSuccess($config);
            $this->updateLead($config);
            $this->removeDNC($config['leadEmail'] ?? null);
            $this->identifyLead($config['lead_id']);
            $this->trackPageHit($config);
            $this->fireWebhook($config);
        } finally {
            if ($pushedRequest) {
                $this->requestStack->pop();
            }
        }
    }

    public function fireWebhook($config) 
    {        
        $lead = $this->leadModel->getEntity($config['lead_id']);
        if(!$lead)
        {
            return;
        }
                
        $doiEvent = new DoiSuccessful($lead, $config);
        $this->eventDispatcher->dispatch($doiEvent, DoiEvents::DOI_SUCCESSFUL);
    }

    public function trackPageHit($config)
    {

        if ($this->request instanceof Request) {
            $lead = $this->leadModel->getEntity($config['lead_id']);
            if (!$lead) {
                return;
            }

            $this->request->request->set('page_url', $config['url']);
            $this->request->query->set('page_url', $config['url']);

            try {
                $this->pageModel->hitPage(null, $this->request, $code = '200', $lead);
            } catch (\Throwable $exception) {
                $this->logger->warning(
                    'DOI confirmation page-hit tracking failed; confirmation actions will continue.',
                    [
                        'lead_id'   => $config['lead_id'] ?? null,
                        'hash'      => $config['hash'] ?? null,
                        'page_url'  => $config['url'] ?? null,
                        'exception' => $exception,
                    ]
                );
            }
        }

    }

    private function ensureRequestHasSession(Request $request): void
    {
        if ($request->hasSession()) {
            return;
        }

        $request->setSession(new Session(new MockArraySessionStorage()));
    }

    public function identifyLead($leadId) 
    {
        $clickthrough = ['leadId' => $leadId ];
    
        $event = new ContactIdentificationEvent($clickthrough);
        $this->eventDispatcher->dispatch($event, LeadEvents::ON_CLICKTHROUGH_IDENTIFICATION);
    }

    public function removeDNC($email)
    {
        if (empty($email)) {
            return;
        }

        $this->emailModel->removeDoNotContact($email);
    }

    public function logDoiSuccess($config)
    {
        $ip = $this->ipLookupHelper->getIpAddressFromRequest();
        $log = [
            'bundle'    => 'lead',
            'object'    => 'doi',
            'objectId'  => $config['lead_id'],
            'action'    => 'confirm_doi',
            'details'   => $config,
            'ipAddress' => $ip,
        ];
        $this->auditLogModel->writeToLog($log); 
    }

    public function updateLead($config) {

        $addTags    = (!empty($config['add_tags'])) ? $config['add_tags'] : [];
        $removeTags = (!empty($config['remove_tags'])) ? $config['remove_tags'] : [];            
        $addTo      = (!empty($config['addToLists'])) ? $config['addToLists']: [];
        $removeFrom = (!empty($config['removeFromLists'])) ? $config['removeFromLists']: [];
        $leadFieldUpdate = (!empty($config['leadFieldUpdate'])) ? $config['leadFieldUpdate']: [];

        $lead = $this->leadModel->getEntity($config['lead_id']);
        if(!$lead)
        {
            return;
        }

        DoiStateTransitionHelper::applyConfirmedState($this->leadModel, $lead, [
            'add_tags'        => $addTags,
            'remove_tags'     => $removeTags,
            'addToLists'      => $addTo,
            'removeFromLists' => $removeFrom,
        ]);

        //Update lead value (if any)
        if( !empty($leadFieldUpdate) )
        {
            $ip = $this->ipLookupHelper->getIpAddressFromRequest();            
            LeadHelper::leadFieldUpdate($leadFieldUpdate, $this->leadModel, $lead, $ip );               
        }
    }

}
