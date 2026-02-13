<?php

namespace OpenSolid\CallableInvoker\Tests\ValueResolver;

use OpenSolid\CallableInvoker\Exception\ParameterNotSupportedException;
use OpenSolid\CallableInvoker\Metadata;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolverChain;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolverInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ParameterValueResolverChainTest extends TestCase
{
    #[Test]
    public function resolveWithFirstResolver(): void
    {
        $resolver = $this->createStub(ParameterValueResolverInterface::class);
        $resolver->method('resolve')->willReturn('resolved');

        $chain = new ParameterValueResolverChain([$resolver]);
        $parameter = $this->getParameter(fn (string $name) => null, 'name');
        $reflection = new \ReflectionFunction(fn () => null);
        $metadata = new Metadata($reflection, 'test', []);

        self::assertSame('resolved', $chain->resolve($parameter, $metadata));
    }

    #[Test]
    public function resolveSkipsUnsupportedResolvers(): void
    {
        $unsupported = $this->createStub(ParameterValueResolverInterface::class);
        $unsupported->method('resolve')->willThrowException(new ParameterNotSupportedException());

        $supported = $this->createStub(ParameterValueResolverInterface::class);
        $supported->method('resolve')->willReturn('fallback');

        $chain = new ParameterValueResolverChain([$unsupported, $supported]);
        $parameter = $this->getParameter(fn (string $name) => null, 'name');
        $reflection = new \ReflectionFunction(fn () => null);
        $metadata = new Metadata($reflection, 'test', []);

        self::assertSame('fallback', $chain->resolve($parameter, $metadata));
    }

    #[Test]
    public function throwsWhenNoResolverSupports(): void
    {
        $unsupported = $this->createStub(ParameterValueResolverInterface::class);
        $unsupported->method('resolve')->willThrowException(new ParameterNotSupportedException());

        $chain = new ParameterValueResolverChain([$unsupported]);
        $parameter = $this->getParameter(fn (string $name) => null, 'name');
        $reflection = new \ReflectionFunction(fn () => null);
        $metadata = new Metadata($reflection, 'test', []);

        $this->expectException(ParameterNotSupportedException::class);
        $this->expectExceptionMessage('Could not resolve value for parameter "name".');
        $chain->resolve($parameter, $metadata);
    }

    #[Test]
    public function throwsWhenEmpty(): void
    {
        $chain = new ParameterValueResolverChain([]);
        $parameter = $this->getParameter(fn (string $name) => null, 'name');
        $reflection = new \ReflectionFunction(fn () => null);
        $metadata = new Metadata($reflection, 'test', []);

        $this->expectException(ParameterNotSupportedException::class);
        $chain->resolve($parameter, $metadata);
    }

    private function getParameter(\Closure $fn, string $name): \ReflectionParameter
    {
        $reflection = new \ReflectionFunction($fn);
        foreach ($reflection->getParameters() as $parameter) {
            if ($parameter->getName() === $name) {
                return $parameter;
            }
        }

        throw new \LogicException(\sprintf('Parameter "%s" not found.', $name));
    }
}
