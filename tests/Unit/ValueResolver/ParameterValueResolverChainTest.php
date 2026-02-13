<?php

namespace OpenSolid\CallableInvoker\Tests\Unit\ValueResolver;

use OpenSolid\CallableInvoker\Exception\ParameterNotSupportedException;
use OpenSolid\CallableInvoker\Tests\Unit\TestHelper;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolverChain;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolverInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ParameterValueResolverChainTest extends TestCase
{
    use TestHelper;

    #[Test]
    public function supportsWhenAnyResolverSupports(): void
    {
        $unsupported = $this->createStub(ParameterValueResolverInterface::class);
        $unsupported->method('supports')->willReturn(false);

        $supported = $this->createStub(ParameterValueResolverInterface::class);
        $supported->method('supports')->willReturn(true);

        $chain = new ParameterValueResolverChain([$unsupported, $supported]);
        $parameter = $this->getParameter(fn (string $name) => null, 'name');

        self::assertTrue($chain->supports($parameter, $this->createMetadata()));
    }

    #[Test]
    public function doesNotSupportWhenNoResolverSupports(): void
    {
        $unsupported = $this->createStub(ParameterValueResolverInterface::class);
        $unsupported->method('supports')->willReturn(false);

        $chain = new ParameterValueResolverChain([$unsupported]);
        $parameter = $this->getParameter(fn (string $name) => null, 'name');

        self::assertFalse($chain->supports($parameter, $this->createMetadata()));
    }

    #[Test]
    public function doesNotSupportWhenEmpty(): void
    {
        $chain = new ParameterValueResolverChain([]);
        $parameter = $this->getParameter(fn (string $name) => null, 'name');

        self::assertFalse($chain->supports($parameter, $this->createMetadata()));
    }

    #[Test]
    public function resolveWithFirstSupportingResolver(): void
    {
        $resolver = $this->createStub(ParameterValueResolverInterface::class);
        $resolver->method('supports')->willReturn(true);
        $resolver->method('resolve')->willReturn('resolved');

        $chain = new ParameterValueResolverChain([$resolver]);
        $parameter = $this->getParameter(fn (string $name) => null, 'name');

        self::assertSame('resolved', $chain->resolve($parameter, $this->createMetadata()));
    }

    #[Test]
    public function resolveSkipsUnsupportedResolvers(): void
    {
        $unsupported = $this->createStub(ParameterValueResolverInterface::class);
        $unsupported->method('supports')->willReturn(false);

        $supported = $this->createStub(ParameterValueResolverInterface::class);
        $supported->method('supports')->willReturn(true);
        $supported->method('resolve')->willReturn('fallback');

        $chain = new ParameterValueResolverChain([$unsupported, $supported]);
        $parameter = $this->getParameter(fn (string $name) => null, 'name');

        self::assertSame('fallback', $chain->resolve($parameter, $this->createMetadata()));
    }

    #[Test]
    public function throwsWhenNoResolverSupports(): void
    {
        $unsupported = $this->createStub(ParameterValueResolverInterface::class);
        $unsupported->method('supports')->willReturn(false);

        $chain = new ParameterValueResolverChain([$unsupported]);
        $parameter = $this->getParameter(fn (string $name) => null, 'name');

        $this->expectException(ParameterNotSupportedException::class);
        $this->expectExceptionMessage('Could not resolve value for parameter "$name" in "test".');
        $chain->resolve($parameter, $this->createMetadata());
    }

    #[Test]
    public function throwsWhenEmpty(): void
    {
        $chain = new ParameterValueResolverChain([]);
        $parameter = $this->getParameter(fn (string $name) => null, 'name');

        $this->expectException(ParameterNotSupportedException::class);
        $chain->resolve($parameter, $this->createMetadata());
    }
}
