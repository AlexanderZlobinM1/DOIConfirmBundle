<?php

declare(strict_types=1);

namespace MauticPlugin\DOIConfirmBundle\Controller;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use MauticPlugin\DOIConfirmBundle\Service\DocumentationLocaleResolver;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

final class DocumentationController
{
    public function indexAction(
        Request $request,
        CorePermissions $security,
        DocumentationLocaleResolver $localeResolver,
        RouterInterface $router,
        Environment $twig,
    ): Response {
        if (!$security->isAdmin()) {
            throw new AccessDeniedHttpException();
        }

        $locale = $localeResolver->resolve($request->getLocale());
        $route  = $router->generate('doiconfirm_documentation');
        $tmpl   = $request->isXmlHttpRequest() ? $request->query->getString('tmpl', 'index') : 'index';
        $template = '@DOIConfirm/Documentation/index.html.twig';
        $parameters = [
            'tmpl'                  => $tmpl,
            'documentationTemplate' => sprintf('@DOIConfirm/Documentation/%s.html.twig', $locale),
            'currentRoute'          => $route,
            'mauticContent'         => 'doiConfirmDocumentation',
        ];
        $content = $twig->render($template, $parameters);

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'activeLink'    => '#mautic_plugin_index',
                'mauticContent' => 'doiConfirmDocumentation',
                'route'         => $route,
                'newContent'    => $content,
            ]);
        }

        return new Response($content);
    }
}
