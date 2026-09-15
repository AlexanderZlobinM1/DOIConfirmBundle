<?php

declare(strict_types=1);

// php Tests/doi-state-transitions.php
if (!interface_exists('Psr\\Log\\LoggerInterface')) {
    eval(<<<'PHP'
namespace Psr\Log;

interface LoggerInterface
{
    public function emergency($message, array $context = []);
    public function alert($message, array $context = []);
    public function critical($message, array $context = []);
    public function error($message, array $context = []);
    public function warning($message, array $context = []);
    public function notice($message, array $context = []);
    public function info($message, array $context = []);
    public function debug($message, array $context = []);
    public function log($level, $message, array $context = []);
}
PHP);
}

require_once dirname(__DIR__).'/Helper/DoiStateTransitionHelper.php';

use MauticPlugin\DOIConfirmBundle\Helper\DoiStateTransitionHelper;
use Psr\Log\LoggerInterface;

$lead      = new stdClass();
$lead->id  = 7;
$leadModel = new class {
    /** @var string[] */
    public array $tags = [];

    /** @var int[] */
    public array $lists = [];

    /** @var array<int, array{add: array<int, string>, remove: array<int, string>}> */
    public array $tagCalls = [];

    /** @var array<int, int[]> */
    public array $addListCalls = [];

    /** @var array<int, int[]> */
    public array $removeListCalls = [];

    public function modifyTags($lead, array $addTags, array $removeTags): void
    {
        $this->tagCalls[] = ['add' => $addTags, 'remove' => $removeTags];
        $this->tags       = array_values(array_unique(array_merge($this->tags, $addTags)));
        $this->tags       = array_values(array_diff($this->tags, $removeTags));
        sort($this->tags);
    }

    public function addToLists($lead, array $lists): void
    {
        $this->addListCalls[] = $lists;
        $this->lists          = array_values(array_unique(array_merge($this->lists, $lists)));
        sort($this->lists);
    }

    public function removeFromLists($lead, array $lists): void
    {
        $this->removeListCalls[] = $lists;
        $this->lists             = array_values(array_diff($this->lists, $lists));
        sort($this->lists);
    }
};

$submitConfig = [
    'remove_tags_doi_success_tags'       => ['pending-doi'],
    'remove_campaign_doi_success_lists'  => [4],
    'add_campaign_doi_success_tags'      => ['confirmed-doi'],
    'add_campaign_doi_success_lists'     => [6],
];

DoiStateTransitionHelper::applyPendingState($leadModel, $lead, $submitConfig);
DoiStateTransitionHelper::applyPendingState($leadModel, $lead, $submitConfig);
assertSame(['pending-doi'], $leadModel->tags, 'Submit should assign pending tag idempotently.');
assertSame([4], $leadModel->lists, 'Submit should assign pending segment idempotently.');

$throwingLeadModel = new class {
    public function modifyTags($lead, array $addTags, array $removeTags): void
    {
        throw new RuntimeException('tag listener failed');
    }

    public function addToLists($lead, array $lists): void
    {
        throw new RuntimeException('segment listener failed');
    }
};
$logger = new class implements LoggerInterface {
    /** @var array<int, array{message: string, context: array<string, mixed>}> */
    public array $warnings = [];

    public function emergency($message, array $context = []) {}
    public function alert($message, array $context = []) {}
    public function critical($message, array $context = []) {}
    public function error($message, array $context = []) {}
    public function warning($message, array $context = [])
    {
        $this->warnings[] = ['message' => (string) $message, 'context' => $context];
    }
    public function notice($message, array $context = []) {}
    public function info($message, array $context = []) {}
    public function debug($message, array $context = []) {}
    public function log($level, $message, array $context = []) {}
};

$pendingApplied = DoiStateTransitionHelper::applyPendingStateBestEffort(
    $throwingLeadModel,
    $lead,
    $submitConfig,
    $logger,
    ['lead_id' => 7]
);
assertSame(false, $pendingApplied, 'Pending state errors should be reported as a non-fatal result.');
assertSame(1, count($logger->warnings), 'Pending state errors should emit exactly one warning.');

$confirmConfig = [
    'add_tags'        => $submitConfig['add_campaign_doi_success_tags'],
    'remove_tags'     => $submitConfig['remove_tags_doi_success_tags'],
    'addToLists'      => $submitConfig['add_campaign_doi_success_lists'],
    'removeFromLists' => $submitConfig['remove_campaign_doi_success_lists'],
];

DoiStateTransitionHelper::applyConfirmedState($leadModel, $lead, $confirmConfig);
DoiStateTransitionHelper::applyConfirmedState($leadModel, $lead, $confirmConfig);
assertSame(['confirmed-doi'], $leadModel->tags, 'Confirm should replace pending tag with confirmed tag idempotently.');
assertSame([6], $leadModel->lists, 'Confirm should replace pending segment with confirmed segment idempotently.');

echo 'PASS DOI state transitions'.PHP_EOL;

/**
 * @param mixed $expected
 * @param mixed $actual
 */
function assertSame($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(sprintf(
            "%s\nExpected: %s\nActual: %s",
            $message,
            json_encode($expected),
            json_encode($actual)
        ));
    }
}
