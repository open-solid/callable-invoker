<?php

namespace OpenSolid\CallableInvoker\Tests\Unit\ValueResolver;

use OpenSolid\CallableInvoker\Tests\Unit\TestHelper;
use OpenSolid\CallableInvoker\ValueResolver\NullableParameterValueResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class NullableParameterValueResolverTest extends TestCase
{
    use TestHelper;

    #[Test]
    public function supportsNullableParameter(): void
    {
        $resolver = new NullableParameterValueResolver();
        $parameter = $this->getParameter(static fn (?string $name) => null, 'name');

        self::assertTrue($resolver->supports($parameter, $this->createMetadata()));
    }

    #[Test]
    public function doesNotSupportNonNullableParameter(): void
    {
        $resolver = new NullableParameterValueResolver();
        $parameter = $this->getParameter(static fn (string $name) => null, 'name');

        self::assertFalse($resolver->supports($parameter, $this->createMetadata()));
    }

    #[Test]
    public function resolveNullableParameter(): void
    {
        $resolver = new NullableParameterValueResolver();
        $parameter = $this->getParameter(static fn (?string $name) => null, 'name');

        self::assertNull($resolver->resolve($parameter, $this->createMetadata()));
    }
}
