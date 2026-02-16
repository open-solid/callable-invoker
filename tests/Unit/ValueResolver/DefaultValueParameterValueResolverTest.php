<?php

namespace OpenSolid\CallableInvoker\Tests\Unit\ValueResolver;

use OpenSolid\CallableInvoker\Tests\Unit\TestHelper;
use OpenSolid\CallableInvoker\ValueResolver\DefaultValueParameterValueResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DefaultValueParameterValueResolverTest extends TestCase
{
    use TestHelper;

    #[Test]
    public function supportsParameterWithDefaultValue(): void
    {
        $resolver = new DefaultValueParameterValueResolver();
        $parameter = $this->getParameter(static fn (string $name = 'World') => null, 'name');

        self::assertTrue($resolver->supports($parameter, $this->createMetadata()));
    }

    #[Test]
    public function doesNotSupportParameterWithoutDefaultValue(): void
    {
        $resolver = new DefaultValueParameterValueResolver();
        $parameter = $this->getParameter(static fn (string $name) => null, 'name');

        self::assertFalse($resolver->supports($parameter, $this->createMetadata()));
    }

    #[Test]
    public function resolveDefaultValue(): void
    {
        $resolver = new DefaultValueParameterValueResolver();
        $parameter = $this->getParameter(static fn (string $name = 'World') => null, 'name');

        self::assertSame('World', $resolver->resolve($parameter, $this->createMetadata()));
    }

    #[Test]
    public function resolveNullDefaultValue(): void
    {
        $resolver = new DefaultValueParameterValueResolver();
        $parameter = $this->getParameter(static fn (?string $name = null) => null, 'name');

        self::assertNull($resolver->resolve($parameter, $this->createMetadata()));
    }
}
