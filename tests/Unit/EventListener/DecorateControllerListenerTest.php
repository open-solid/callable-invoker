<?php

namespace OpenSolid\CallableInvoker\Tests\Unit\EventListener;

use OpenSolid\CallableInvoker\CallableInvoker;
use OpenSolid\CallableInvoker\CallableMetadata;
use OpenSolid\CallableInvoker\Decorator\CallableClosure;
use OpenSolid\CallableInvoker\Decorator\CallableDecorator;
use OpenSolid\CallableInvoker\Decorator\CallableDecoratorInterface;
use OpenSolid\CallableInvoker\EventListener\DecorateControllerListener;
use OpenSolid\CallableInvoker\InMemoryCallableServiceLocator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class DecorateControllerListenerTest extends TestCase
{
    #[Test]
    public function decoratedControllerKeepsOriginalAttributes(): void
    {
        $controller = new AttributedController();
        $event = $this->createEvent([$controller, 'action']);

        $listener = new DecorateControllerListener(new CallableInvoker(new CallableDecorator(
            new InMemoryCallableServiceLocator(['kernel.controller' => [new PassThroughDecorator()]]),
        )));
        $listener($event);

        self::assertInstanceOf(\Closure::class, $event->getController());
        self::assertNotSame([$controller, 'action'], $event->getController());

        $classAttributes = $event->getAttributes(ClassMarker::class);
        $methodAttributes = $event->getAttributes(MethodMarker::class);

        self::assertCount(1, $classAttributes);
        self::assertCount(1, $methodAttributes);
        self::assertSame('limiter', $methodAttributes[0]->name);
    }

    #[Test]
    public function controllerWithoutDecoratorsKeepsOriginalAttributes(): void
    {
        $event = $this->createEvent([new AttributedController(), 'action']);

        $listener = new DecorateControllerListener(new CallableInvoker());
        $listener($event);

        self::assertCount(1, $event->getAttributes(ClassMarker::class));
        self::assertCount(1, $event->getAttributes(MethodMarker::class));
    }

    private function createEvent(callable $controller): ControllerArgumentsEvent
    {
        return new ControllerArgumentsEvent(
            $this->createStub(HttpKernelInterface::class),
            $controller,
            [],
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
        );
    }
}

#[\Attribute(\Attribute::TARGET_CLASS)]
final class ClassMarker
{
}

#[\Attribute(\Attribute::TARGET_METHOD)]
final class MethodMarker
{
    public function __construct(
        public string $name,
    ) {
    }
}

#[ClassMarker]
final class AttributedController
{
    #[MethodMarker('limiter')]
    public function action(): Response
    {
        return new Response('original');
    }
}

final class PassThroughDecorator implements CallableDecoratorInterface
{
    public function supports(CallableMetadata $metadata): bool
    {
        return true;
    }

    public function decorate(CallableClosure $callable, CallableMetadata $metadata): mixed
    {
        return $callable->call();
    }
}
