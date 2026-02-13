<?php

namespace OpenSolid\CallableInvoker\Tests;

use OpenSolid\CallableInvoker\CallableInvoker;
use OpenSolid\CallableInvoker\Decorator\FunctionDecoratorInterface;
use OpenSolid\CallableInvoker\Metadata;
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

        $result = $invoker->invoke(fn () => 'hello');

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

        $result = $invoker->invoke(fn (string $name) => "Hello, $name!");

        self::assertSame('Hello, World!', $result);
    }

    #[Test]
    public function invokeCallableWithContext(): void
    {
        $resolver = $this->createStub(ParameterValueResolverInterface::class);
        $resolver->method('resolve')->willReturnCallback(
            fn (\ReflectionParameter $param, Metadata $metadata) => $metadata->context[$param->getName()],
        );

        $invoker = new CallableInvoker(
            $this->createPassthroughDecorator(),
            $resolver,
        );

        $result = $invoker->invoke(fn (string $name) => "Hello, $name!", ['name' => 'PHP']);

        self::assertSame('Hello, PHP!', $result);
    }

    #[Test]
    public function invokeAppliesDecorator(): void
    {
        $decorator = $this->createStub(FunctionDecoratorInterface::class);
        $decorator->method('decorate')->willReturn(fn () => 'decorated');

        $invoker = new CallableInvoker(
            $decorator,
            $this->createStub(ParameterValueResolverInterface::class),
        );

        $result = $invoker->invoke(fn () => 'original');

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

    private function createPassthroughDecorator(): FunctionDecoratorInterface
    {
        $decorator = $this->createStub(FunctionDecoratorInterface::class);
        $decorator->method('decorate')->willReturnArgument(0);

        return $decorator;
    }
}
