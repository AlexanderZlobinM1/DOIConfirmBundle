<?php

declare(strict_types=1);

namespace MauticPlugin\DOIConfirmBundle\Tests;

require_once dirname(__DIR__).'/Service/DocumentationLocaleResolver.php';
require_once dirname(__DIR__).'/Controller/DocumentationController.php';

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use MauticPlugin\DOIConfirmBundle\Controller\DocumentationController;
use MauticPlugin\DOIConfirmBundle\Service\DocumentationLocaleResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

final class DocumentationControllerTest extends TestCase
{
    public function testAdminFullRequestReturnsLocalizedDocumentation(): void
    {
        [$controller, $request, $security, $router, $twig, $rendered] = $this->createContext(true, false, 'ru_RU');

        $response = $controller->indexAction($request, $security, new DocumentationLocaleResolver(), $router, $twig);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('<main>documentation</main>', $response->getContent());
        self::assertSame('@DOIConfirm/Documentation/ru.html.twig', $rendered->parameters['documentationTemplate']);
    }

    public function testAdminAjaxRequestReturnsMauticJsonPayload(): void
    {
        [$controller, $request, $security, $router, $twig] = $this->createContext(true, true, 'sr_RS');

        $response = $controller->indexAction($request, $security, new DocumentationLocaleResolver(), $router, $twig);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('<main>documentation</main>', $payload['newContent']);
        self::assertSame('/s/doi-confirm/documentation', $payload['route']);
    }

    public function testNonAdminRequestIsForbidden(): void
    {
        [$controller, $request, $security, $router, $twig] = $this->createContext(false, false, 'en_US');

        $this->expectException(AccessDeniedHttpException::class);
        $controller->indexAction($request, $security, new DocumentationLocaleResolver(), $router, $twig);
    }

    /**
     * @return array{DocumentationController, Request, CorePermissions, RouterInterface, Environment, object{parameters?: array<string, mixed>}}
     */
    private function createContext(bool $isAdmin, bool $ajax, string $locale): array
    {
        $request = Request::create('/s/doi-confirm/documentation');
        $request->setLocale($locale);
        if ($ajax) {
            $request->headers->set('X-Requested-With', 'XMLHttpRequest');
            $request->query->set('tmpl', 'content');
        }

        $security = $this->createStub(CorePermissions::class);
        $security->method('isAdmin')->willReturn($isAdmin);

        $router = $this->createStub(RouterInterface::class);
        $router->method('generate')->willReturn('/s/doi-confirm/documentation');

        $rendered = new \stdClass();
        $twig = $this->createStub(Environment::class);
        $twig->method('render')->willReturnCallback(
            static function (string $template, array $parameters = []) use ($rendered): string {
                if ('@DOIConfirm/Documentation/index.html.twig' === $template) {
                    $rendered->parameters = $parameters;

                    return '<main>documentation</main>';
                }

                return '';
            }
        );

        return [new DocumentationController(), $request, $security, $router, $twig, $rendered];
    }
}
