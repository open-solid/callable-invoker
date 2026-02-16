<?php

namespace OpenSolid\CallableInvoker\Tests\Unit\ValueResolver;

use OpenSolid\CallableInvoker\Exception\UntypedParameterNotSupportedException;
use OpenSolid\CallableInvoker\Exception\VariadicParameterNotSupportedException;
use OpenSolid\CallableInvoker\Tests\Unit\TestHelper;
use OpenSolid\CallableInvoker\ValueResolver\UnsupportedParameterValueResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UnsupportedParameterValueResolverTest extends TestCase
{
    use TestHelper;

    #[Test]
    public function supportsVariadicParameter(): void
    {
        $resolver = new UnsupportedParameterValueResolver();
        $parameter = $this->getParameter(static fn (string ...$names) => null, 'names');

        self::assertTrue($resolver->supports($parameter, $this->createMetadata()));
    }

    #[Test]
    public function supportsUntypedParameter(): void
    {
        $resolver = new UnsupportedParameterValueResolver();
        $parameter = $this->getParameter(static fn ($name) => null, 'name');

        self::assertTrue($resolver->supports($parameter, $this->createMetadata()));
    }

    #[Test]
    public function doesNotSupportTypedParameter(): void
    {
        $resolver = new UnsupportedParameterValueResolver();
        $parameter = $this->getParameter(static fn (string $name) => null, 'name');

        self::assertFalse($resolver->supports($parameter, $this->createMetadata()));
    }

    #[Test]
    public function throwsForVariadicParameter(): void
    {
        $resolver = new UnsupportedParameterValueResolver();
        $parameter = $this->getParameter(static fn (string ...$names) => null, 'names');

        $this->expectException(VariadicParameterNotSupportedException::class);
        $this->expectExceptionMessage('Variadic parameter "$names" is not supported');

        $resolver->resolve($parameter, $this->createMetadata());
    }

    #[Test]
    public function throwsForUntypedParameter(): void
    {
        $resolver = new UnsupportedParameterValueResolver();
        $parameter = $this->getParameter(static fn ($name) => null, 'name');

        $this->expectException(UntypedParameterNotSupportedException::class);
        $this->expectExceptionMessage('Untyped parameter "$name" is not supported');

        $resolver->resolve($parameter, $this->createMetadata());
    }
}
