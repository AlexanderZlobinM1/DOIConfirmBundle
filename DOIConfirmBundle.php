<?php

namespace MauticPlugin\DOIConfirmBundle;

use Mautic\PluginBundle\Bundle\PluginBundleBase;

class DOIConfirmBundle extends PluginBundleBase
{
    public function build(\Symfony\Component\DependencyInjection\ContainerBuilder $container): void
    {
        parent::build($container);
        $container->setAlias('doiconfirmbundle.helper.encryption', \Mautic\CoreBundle\Helper\EncryptionHelper::class);
    }

}
