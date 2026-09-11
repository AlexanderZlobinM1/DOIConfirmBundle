<?php

namespace MauticPlugin\DOIConfirmBundle\Helper;

class DoiStateTransitionHelper
{
    public static function applyPendingState($leadModel, $lead, array $config): void
    {
        $pendingTags  = (!empty($config['remove_tags_doi_success_tags'])) ? $config['remove_tags_doi_success_tags'] : [];
        $pendingLists = (!empty($config['remove_campaign_doi_success_lists'])) ? $config['remove_campaign_doi_success_lists'] : [];

        if (!empty($pendingTags)) {
            $leadModel->modifyTags($lead, $pendingTags, []);
        }

        if (!empty($pendingLists)) {
            $leadModel->addToLists($lead, $pendingLists);
        }
    }

    public static function applyConfirmedState($leadModel, $lead, array $config): void
    {
        $addTags    = (!empty($config['add_tags'])) ? $config['add_tags'] : [];
        $removeTags = (!empty($config['remove_tags'])) ? $config['remove_tags'] : [];
        $addTo      = (!empty($config['addToLists'])) ? $config['addToLists'] : [];
        $removeFrom = (!empty($config['removeFromLists'])) ? $config['removeFromLists'] : [];

        if (!empty($addTags) || !empty($removeTags)) {
            $leadModel->modifyTags($lead, $addTags, $removeTags);
        }

        if (!empty($addTo)) {
            $leadModel->addToLists($lead, $addTo);
        }

        if (!empty($removeFrom)) {
            $leadModel->removeFromLists($lead, $removeFrom);
        }
    }
}
