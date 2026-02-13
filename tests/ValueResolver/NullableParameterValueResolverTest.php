<?php

namespace OpenSolid\CallableInvoker\Tests\ValueResolver;

use OpenSolid\CallableInvoker\Exception\ParameterNotSupportedException;
use OpenSolid\CallableInvoker\Tests\TestHelper;
use OpenSolid\CallableInvoker\ValueResolver\NullableParameterValueResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class NullableParameterValueResolverTest extends TestCase
{
    use TestHelper;

    #[Test]
    public function resolveNullableParameter(): void
    {
        $resolver = new NullableParameterValueResolver();
        $parameter = $this->getParameter(fn (?string $name) => null, 'name');
        $metadata = $this->createMetadata();

        self::assertNull($resolver->resolve($parameter, $metadata));
    }

    #[Test]
    public function throwsWhenParameterNotNullable(): void
    {
        $resolver = new NullableParameterValueResolver();
        $parameter = $this->getParameter(fn (string $name) => null, 'name');
        $metadata = $this->createMetadata();

        $this->expectException(ParameterNotSupportedException::class);
        $resolver->resolve($parameter, $metadata);
    }
}
