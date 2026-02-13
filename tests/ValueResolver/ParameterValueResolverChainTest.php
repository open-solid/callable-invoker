<?php

namespace OpenSolid\CallableInvoker\Tests\ValueResolver;

use OpenSolid\CallableInvoker\Exception\ParameterNotSupportedException;
use OpenSolid\CallableInvoker\Tests\TestHelper;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolverChain;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolverInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ParameterValueResolverChainTest extends TestCase
{
    use TestHelper;

    #[Test]
    public function resolveWithFirstResolver(): void
    {
        $resolver = $this->createStub(ParameterValueResolverInterface::class);
        $resolver->method('resolve')->willReturn('resolved');

        $chain = new ParameterValueResolverChain([$resolver]);
        $parameter = $this->getParameter(fn (string $name) => null, 'name');
        $metadata = $this->createMetadata();

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
        $metadata = $this->createMetadata();

        self::assertSame('fallback', $chain->resolve($parameter, $metadata));
    }

    #[Test]
    public function throwsWhenNoResolverSupports(): void
    {
        $unsupported = $this->createStub(ParameterValueResolverInterface::class);
        $unsupported->method('resolve')->willThrowException(new ParameterNotSupportedException());

        $chain = new ParameterValueResolverChain([$unsupported]);
        $parameter = $this->getParameter(fn (string $name) => null, 'name');
        $metadata = $this->createMetadata();

        $this->expectException(ParameterNotSupportedException::class);
        $this->expectExceptionMessage('Could not resolve value for parameter "name".');
        $chain->resolve($parameter, $metadata);
    }

    #[Test]
    public function throwsWhenEmpty(): void
    {
        $chain = new ParameterValueResolverChain([]);
        $parameter = $this->getParameter(fn (string $name) => null, 'name');
        $metadata = $this->createMetadata();

        $this->expectException(ParameterNotSupportedException::class);
        $chain->resolve($parameter, $metadata);
    }
}
