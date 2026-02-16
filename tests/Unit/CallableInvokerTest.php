<?php

namespace OpenSolid\CallableInvoker\Tests\Unit;

use OpenSolid\CallableInvoker\CallableInvoker;
use OpenSolid\CallableInvoker\CallableInvokerInterface;
use OpenSolid\CallableInvoker\CallableMetadata;
use OpenSolid\CallableInvoker\Decorator\CallableDecoratorInterface;
use OpenSolid\CallableInvoker\Exception\ParameterNotSupportedException;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolverInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CallableInvokerTest extends TestCase
{
    #[Test]
    public function invokeCallableWithNoParameters(): void
    {
        $invoker = new CallableInvoker(
            $this->createPassthroughDecorator(),
            $this->createStub(ParameterValueResolverInterface::class),
        );

        $result = $invoker->invoke(static fn () => 'hello');

        self::assertSame('hello', $result);
    }

    #[Test]
    public function invokeCallableWithResolvedParameters(): void
    {
        $resolver = $this->createStub(ParameterValueResolverInterface::class);
        $resolver->method('resolve')->willReturn('World');

        $invoker = new CallableInvoker(
            $this->createPassthroughDecorator(),
            $resolver,
        );

        $result = $invoker->invoke(static fn (string $name) => "Hello, $name!");

        self::assertSame('Hello, World!', $result);
    }

    #[Test]
    public function invokeCallableWithContext(): void
    {
        $resolver = $this->createStub(ParameterValueResolverInterface::class);
        $resolver->method('resolve')->willReturnCallback(
            static fn (\ReflectionParameter $param, CallableMetadata $metadata) => $metadata->context[$param->getName()],
        );

        $invoker = new CallableInvoker(
            $this->createPassthroughDecorator(),
            $resolver,
        );

        $result = $invoker->invoke(static fn (string $name) => "Hello, $name!", ['name' => 'PHP']);

        self::assertSame('Hello, PHP!', $result);
    }

    #[Test]
    public function invokeAppliesDecorator(): void
    {
        $decorator = $this->createStub(CallableDecoratorInterface::class);
        $decorator->method('decorate')->willReturn(static fn () => 'decorated');

        $invoker = new CallableInvoker(
            $decorator,
            $this->createStub(ParameterValueResolverInterface::class),
        );

        $result = $invoker->invoke(static fn () => 'original');

        self::assertSame('decorated', $result);
    }

    #[Test]
    public function invokeClassCallable(): void
    {
        $invoker = new CallableInvoker(
            $this->createPassthroughDecorator(),
            $this->createStub(ParameterValueResolverInterface::class),
        );

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
        $resolver->method('resolve')->willReturnCallback(
            static fn (\ReflectionParameter $param) => match ($param->getName()) {
                'greeting' => 'Hello',
                'name' => 'World',
            },
        );

        $invoker = new CallableInvoker(
            $this->createPassthroughDecorator(),
            $resolver,
        );

        $result = $invoker->invoke(static fn (string $greeting, string $name) => "$greeting, $name!");

        self::assertSame('Hello, World!', $result);
    }

    #[Test]
    public function invokeThrowsWhenParameterCannotBeResolved(): void
    {
        $resolver = $this->createStub(ParameterValueResolverInterface::class);
        $resolver->method('resolve')->willReturnCallback(
            static fn (\ReflectionParameter $param) => throw ParameterNotSupportedException::create($param),
        );

        $invoker = new CallableInvoker(
            $this->createPassthroughDecorator(),
            $resolver,
        );

        $this->expectException(ParameterNotSupportedException::class);
        $invoker->invoke(static fn (string $name) => $name);
    }

    #[Test]
    public function invokePassesOriginalClosureAndContextToDecorator(): void
    {
        $capturedClosure = null;
        $capturedMetadata = null;
        $decorator = $this->createStub(CallableDecoratorInterface::class);
        $decorator->method('decorate')->willReturnCallback(
            static function (\Closure $fn, CallableMetadata $metadata) use (&$capturedClosure, &$capturedMetadata) {
                $capturedClosure = $fn;
                $capturedMetadata = $metadata;

                return $fn;
            },
        );

        $invoker = new CallableInvoker(
            $decorator,
            $this->createStub(ParameterValueResolverInterface::class),
        );

        $invoker->invoke(static fn () => 'original', ['key' => 'value']);

        self::assertNotNull($capturedClosure);
        self::assertSame('original', $capturedClosure());
        self::assertNotNull($capturedMetadata);
        self::assertSame(['key' => 'value'], $capturedMetadata->context);
        self::assertSame([CallableInvokerInterface::DEFAULT_GROUP], $capturedMetadata->groups);
        self::assertInstanceOf(\ReflectionFunction::class, $capturedMetadata->function);
    }

    #[Test]
    public function invokePassesGroupsToMetadata(): void
    {
        $capturedMetadata = null;
        $decorator = $this->createCapturingDecorator($capturedMetadata);

        $invoker = new CallableInvoker(
            $decorator,
            $this->createStub(ParameterValueResolverInterface::class),
        );

        $invoker->invoke(static fn () => null, [], ['group_a', 'group_b']);

        self::assertNotNull($capturedMetadata);
        self::assertSame(['group_a', 'group_b'], $capturedMetadata->groups);
    }

    private function createPassthroughDecorator(): CallableDecoratorInterface
    {
        $decorator = $this->createStub(CallableDecoratorInterface::class);
        $decorator->method('decorate')->willReturnArgument(0);

        return $decorator;
    }

    private function createCapturingDecorator(?CallableMetadata &$capturedMetadata): CallableDecoratorInterface
    {
        $decorator = $this->createStub(CallableDecoratorInterface::class);
        $decorator->method('decorate')->willReturnCallback(
            static function (\Closure $fn, CallableMetadata $metadata) use (&$capturedMetadata) {
                $capturedMetadata = $metadata;

                return $fn;
            },
        );

        return $decorator;
    }
}
