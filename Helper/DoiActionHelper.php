<?php

namespace MauticPlugin\DOIConfirmBundle\Helper;

use Mautic\LeadBundle\Event\ContactIdentificationEvent;
use Mautic\LeadBundle\LeadEvents;
use MauticPlugin\DOIConfirmBundle\Event\DoiSuccessful;
use MauticPlugin\DOIConfirmBundle\Helper\LeadHelper;
use MauticPlugin\DOIConfirmBundle\DoiEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
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

    private PluginEnabledResolver $pluginEnabledResolver;


    public function __construct($eventDispatcher, $ipLookupHelper, $pageModel, $emailModel, $auditLogModel, $leadModel, RequestStack $requestStack, PluginEnabledResolver $pluginEnabledResolver)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->ipLookupHelper = $ipLookupHelper;
        $this->pageModel = $pageModel;
        $this->emailModel = $emailModel;
        $this->auditLogModel = $auditLogModel;
        $this->leadModel = $leadModel;
        $this->request = $requestStack->getCurrentRequest();
        $this->pluginEnabledResolver = $pluginEnabledResolver;
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

        $this->logDoiSuccess($config);
        $this->updateLead($config);
        $this->removeDNC($config['leadEmail'] ?? null);
        $this->identifyLead($config['lead_id']);
        $this->trackPageHit($config);
        $this->fireWebhook($config);
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

            $this->pageModel->hitPage(null, $this->request, $code = '200', $lead);
        }

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

        // Change Tags (if any)
        if(!empty($addTags)|| !empty($removeTags)){
            $this->leadModel->modifyTags($lead, $addTags, $removeTags);
        }

        // Add to Lists (if any)
        if (!empty($addTo)) {
            $this->leadModel->addToLists($lead, $addTo);
        }

        // Remove from Lists (if any)
        if (!empty($removeFrom)) {
            $this->leadModel->removeFromLists($lead, $removeFrom);
        }       

        //Update lead value (if any)
        if( !empty($leadFieldUpdate) )
        {
            $ip = $this->ipLookupHelper->getIpAddressFromRequest();            
            LeadHelper::leadFieldUpdate($leadFieldUpdate, $this->leadModel, $lead, $ip );               
        }
    }

}
