<?php

namespace MauticPlugin\DOIConfirmBundle\MessageHandler;

use MauticPlugin\DOIConfirmBundle\Helper\DoiActionHelper;
use MauticPlugin\DOIConfirmBundle\Helper\NotHumanClickHelper;
use MauticPlugin\DOIConfirmBundle\Message\DoiConfirmationMessage;
use Psr\Log\LoggerInterface;

class DoiConfirmationMessageHandler
{
    public function __construct(
        private DoiActionHelper $doiActionHelper,
        private NotHumanClickHelper $notHumanClickHelper,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(DoiConfirmationMessage $message): void
    {
        $config = $message->getConfig();

        if ($this->checkIfDoiCancel($config)) {
            return;
        }

        try {
            $this->doiActionHelper->setRequestContext($message->getRequestContext());
            $this->doiActionHelper->applyDoiActions($config);
        } catch (\Throwable $exception) {
            $this->logger->error(
                'Failed to process DOI confirmation message: '.$exception->getMessage(),
                [
                    'payload'   => $config,
                    'exception' => $exception,
                ]
            );

            throw $exception;
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function checkIfDoiCancel(array $config): bool
    {
        $hash = $config['hash'] ?? null;
        if (!$hash || !is_string($hash)) {
            return false;
        }

        if ($this->notHumanClickHelper->isRunning($hash)) {
            $this->notHumanClickHelper->reset($hash);

            return true;
        }

        return false;
    }
}
