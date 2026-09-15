<?php

declare(strict_types=1);

namespace MauticPlugin\DOIConfirmBundle\Tests;

require_once dirname(__DIR__).'/Service/DocumentationLocaleResolver.php';
require_once dirname(__DIR__).'/Controller/DocumentationController.php';

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\PageBundle\Model\PageModel;
use MauticPlugin\DOIConfirmBundle\Controller\DocumentationController;
use MauticPlugin\DOIConfirmBundle\Service\DocumentationLocaleResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

final class DocumentationControllerTest extends TestCase
{
    public function testAdminFullRequestReturnsLocalizedDocumentation(): void
    {
        [$controller, $request, $rendered] = $this->createController(true, false, 'ru_RU');

        $response = $controller->indexAction($request, new DocumentationLocaleResolver());

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('<main>documentation</main>', $response->getContent());
        self::assertSame('@DOIConfirm/Documentation/ru.html.twig', $rendered->parameters['documentationTemplate']);
    }

    public function testAdminAjaxRequestReturnsMauticJsonPayload(): void
    {
        defined('MAUTIC_INSTALLER') || define('MAUTIC_INSTALLER', true);
        [$controller, $request] = $this->createController(true, true, 'sr_RS');

        $response = $controller->indexAction($request, new DocumentationLocaleResolver());

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('<main>documentation</main>', $payload['newContent']);
        self::assertSame('/s/doi-confirm/documentation', $payload['route']);
    }

    public function testNonAdminRequestIsForbidden(): void
    {
        [$controller, $request] = $this->createController(false, false, 'en_US');

        $this->expectException(AccessDeniedHttpException::class);
        $controller->indexAction($request, new DocumentationLocaleResolver());
    }

    /**
     * @return array{DocumentationController, Request, object{parameters?: array<string, mixed>}}
     */
    private function createController(bool $isAdmin, bool $ajax, string $locale): array
    {
        $request = Request::create('/s/doi-confirm/documentation');
        $request->setLocale($locale);
        if ($ajax) {
            $request->headers->set('X-Requested-With', 'XMLHttpRequest');
            $request->query->set('tmpl', 'content');
        }

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $userHelper = $this->createStub(UserHelper::class);
        $userHelper->method('getUser')->willReturn(null);
        $dispatcher = $this->createStub(EventDispatcherInterface::class);
        $dispatcher->method('hasListeners')->willReturn(false);
        $security = $this->createStub(CorePermissions::class);
        $security->method('isAdmin')->willReturn($isAdmin);

        $controller = new DocumentationController(
            $this->createStub(ManagerRegistry::class),
            $this->createStub(ModelFactory::class),
            $userHelper,
            $this->createStub(CoreParametersHelper::class),
            $dispatcher,
            $this->createStub(Translator::class),
            $this->createStub(FlashBag::class),
            $requestStack,
            $security
        );

        $router = $this->createStub(RouterInterface::class);
        $router->method('generate')->willReturn('/s/doi-confirm/documentation');
        $router->method('match')->willReturn(['_route' => 'doiconfirm_documentation']);

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

        $container = new Container();
        $container->set('router', $router);
        $container->set('twig', $twig);
        $controller->setContainer($container);

        if (method_exists($controller, 'autowireCommonController')) {
            $controller->autowireCommonController(
                $this->createStub(PageModel::class),
                $this->createStub(NotificationModel::class),
                $router,
                $this->createStub(HttpKernelInterface::class),
                $twig
            );
        }

        return [$controller, $request, $rendered];
    }
}
