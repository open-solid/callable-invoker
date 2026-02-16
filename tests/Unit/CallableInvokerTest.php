<?php

namespace OpenSolid\CallableInvoker\Tests\Unit;

use OpenSolid\CallableInvoker\CallableInvoker;
use OpenSolid\CallableInvoker\CallableInvokerInterface;
use OpenSolid\CallableInvoker\CallableMetadata;
use OpenSolid\CallableInvoker\Decorator\CallableDecorator;
use OpenSolid\CallableInvoker\Decorator\CallableDecoratorInterface;
use OpenSolid\CallableInvoker\Decorator\ClosureInvoker;
use OpenSolid\CallableInvoker\Exception\ParameterNotSupportedException;
use OpenSolid\CallableInvoker\InMemoryCallableServiceLocator;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolver;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolverInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CallableInvokerTest extends TestCase
{
    #[Test]
    public function invokeCallableWithNoParameters(): void
    {
        $invoker = new CallableInvoker();

        $result = $invoker->invoke(static fn () => 'hello');

        self::assertSame('hello', $result);
    }

    #[Test]
    public function invokeCallableWithResolvedParameters(): void
    {
        $resolver = $this->createStub(ParameterValueResolverInterface::class);
        $resolver->method('supports')->willReturn(true);
        $resolver->method('resolve')->willReturn('World');

        $invoker = new CallableInvoker(
            valueResolver: new ParameterValueResolver(new InMemoryCallableServiceLocator([
                CallableInvokerInterface::DEFAULT_GROUP => [$resolver],
            ])),
        );

        $result = $invoker->invoke(static fn (string $name) => "Hello, $name!");

        self::assertSame('Hello, World!', $result);
    }

    #[Test]
    public function invokeCallableWithContext(): void
    {
        $resolver = $this->createStub(ParameterValueResolverInterface::class);
        $resolver->method('supports')->willReturn(true);
        $resolver->method('resolve')->willReturnCallback(
            static fn (\ReflectionParameter $param, CallableMetadata $metadata) => $metadata->context[$param->getName()],
        );

        $invoker = new CallableInvoker(
            valueResolver: new ParameterValueResolver(new InMemoryCallableServiceLocator([
                CallableInvokerInterface::DEFAULT_GROUP => [$resolver],
            ])),
        );

        $result = $invoker->invoke(static fn (string $name) => "Hello, $name!", ['name' => 'PHP']);

        self::assertSame('Hello, PHP!', $result);
    }

    #[Test]
    public function invokeAppliesDecorator(): void
    {
        $decorator = $this->createStub(CallableDecoratorInterface::class);
        $decorator->method('supports')->willReturn(true);
        $decorator->method('decorate')->willReturn('decorated');

        $invoker = new CallableInvoker(
            decorator: new CallableDecorator(new InMemoryCallableServiceLocator([
                CallableInvokerInterface::DEFAULT_GROUP => [$decorator],
            ])),
        );

        $result = $invoker->invoke(static fn () => 'original');

        self::assertSame('decorated', $result);
    }

    #[Test]
    public function invokeClassCallable(): void
    {
        $invoker = new CallableInvoker();

        $callable = new class {
            public function __invoke(): string
            {
                return 'invoked';
            }
        };

        $result = $invoker->invoke($callable);

        self::assertSame('invoked', $result);
    }

    #[Test]
    public function invokeCallableWithMultipleParameters(): void
    {
        $resolver = $this->createStub(ParameterValueResolverInterface::class);
        $resolver->method('supports')->willReturn(true);
        $resolver->method('resolve')->willReturnCallback(
            static fn (\ReflectionParameter $param) => match ($param->getName()) {
                'greeting' => 'Hello',
                'name' => 'World',
            },
        );

        $invoker = new CallableInvoker(
            valueResolver: new ParameterValueResolver(new InMemoryCallableServiceLocator([
                CallableInvokerInterface::DEFAULT_GROUP => [$resolver],
            ])),
        );

        $result = $invoker->invoke(static fn (string $greeting, string $name) => "$greeting, $name!");

        self::assertSame('Hello, World!', $result);
    }

    #[Test]
    public function invokeThrowsWhenParameterCannotBeResolved(): void
    {
        $invoker = new CallableInvoker(
            valueResolver: new ParameterValueResolver(new InMemoryCallableServiceLocator()),
        );

        $this->expectException(ParameterNotSupportedException::class);
        $invoker->invoke(static fn (string $name) => $name);
    }

    #[Test]
    public function invokePassesMetadataToDecorator(): void
    {
        $capturedInvoker = null;
        $capturedMetadata = null;
        $decorator = $this->createStub(CallableDecoratorInterface::class);
        $decorator->method('supports')->willReturn(true);
        $decorator->method('decorate')->willReturnCallback(
            static function (ClosureInvoker $invoker, CallableMetadata $metadata) use (&$capturedInvoker, &$capturedMetadata) {
                $capturedInvoker = $invoker;
                $capturedMetadata = $metadata;

                return $invoker->invoke();
            },
        );

        $invoker = new CallableInvoker(
            decorator: new CallableDecorator(new InMemoryCallableServiceLocator([
                CallableInvokerInterface::DEFAULT_GROUP => [$decorator],
            ])),
        );

        $invoker->invoke(static fn () => 'original', ['key' => 'value']);

        self::assertNotNull($capturedInvoker);
        self::assertSame('original', ($capturedInvoker->closure)());
        self::assertNotNull($capturedMetadata);
        self::assertSame(['key' => 'value'], $capturedMetadata->context);
        self::assertSame([CallableInvokerInterface::DEFAULT_GROUP], $capturedMetadata->groups);
        self::assertInstanceOf(\ReflectionFunction::class, $capturedMetadata->function);
    }

    #[Test]
    public function invokePassesGroupsToMetadata(): void
    {
        $capturedMetadata = null;
        $decorator = $this->createStub(CallableDecoratorInterface::class);
        $decorator->method('supports')->willReturn(true);
        $decorator->method('decorate')->willReturnCallback(
            static function (ClosureInvoker $invoker, CallableMetadata $metadata) use (&$capturedMetadata) {
                $capturedMetadata = $metadata;

                return $invoker->invoke();
            },
        );

        $invoker = new CallableInvoker(
            decorator: new CallableDecorator(new InMemoryCallableServiceLocator([
                'group_a' => [$decorator],
            ])),
        );

        $invoker->invoke(static fn () => null, [], ['group_a', 'group_b']);

        self::assertNotNull($capturedMetadata);
        self::assertSame(['group_a', 'group_b'], $capturedMetadata->groups);
    }
}
