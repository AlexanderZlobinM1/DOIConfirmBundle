<?php

namespace MauticPlugin\DOIConfirmBundle\Event;

use Mautic\LeadBundle\Entity\Lead;

class DoiStarted extends \Symfony\Contracts\EventDispatcher\Event
{
    public function __construct(private Lead $lead, private array $config)
    {
    }

    public function getLead(): Lead
    {
        return $this->lead;
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}
