<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\DOIConfirmBundle\Integration;

use Mautic\PluginBundle\Integration\AbstractIntegration;

class DoiReportIntegration extends AbstractIntegration
{
    public const INTEGRATION_NAME = 'DoiReport';

    public function getName()
    {
        return self::INTEGRATION_NAME;
    }

    public function getDisplayName()
    {
        return 'Doi Report';
    }

    public function getAuthenticationType()
    {
        return 'none';
    }

    public function getRequiredKeyFields()
    {
        return [
        ];
    }

    public function getIcon()
    {
        return 'plugins/DOIConfirmBundle/Assets/img/icon.png';
    }
}
