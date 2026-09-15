<?php

declare(strict_types=1);

namespace MauticPlugin\DOIConfirmBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\DOIConfirmBundle\Service\DocumentationLocaleResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class DocumentationController extends CommonController
{
    public function indexAction(Request $request, DocumentationLocaleResolver $localeResolver): Response
    {
        if (null === $this->security || !$this->security->isAdmin()) {
            throw new AccessDeniedHttpException();
        }

        $locale = $localeResolver->resolve($request->getLocale());
        $route  = $this->generateUrl('doiconfirm_documentation');
        $tmpl   = $request->isXmlHttpRequest() ? $request->query->getString('tmpl', 'index') : 'index';

        return $this->delegateView([
            'viewParameters' => [
                'tmpl'                  => $tmpl,
                'documentationTemplate' => sprintf('@DOIConfirm/Documentation/%s.html.twig', $locale),
            ],
            'contentTemplate' => '@DOIConfirm/Documentation/index.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_plugin_index',
                'mauticContent' => 'doiConfirmDocumentation',
                'route'         => $route,
            ],
        ]);
    }
}
