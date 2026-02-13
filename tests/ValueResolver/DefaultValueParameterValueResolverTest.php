<?php

namespace OpenSolid\CallableInvoker\Tests\ValueResolver;

use OpenSolid\CallableInvoker\Exception\ParameterNotSupportedException;
use OpenSolid\CallableInvoker\Tests\TestHelper;
use OpenSolid\CallableInvoker\ValueResolver\DefaultValueParameterValueResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DefaultValueParameterValueResolverTest extends TestCase
{
    use TestHelper;

    #[Test]
    public function resolveDefaultValue(): void
    {
        $resolver = new DefaultValueParameterValueResolver();
        $parameter = $this->getParameter(fn (string $name = 'World') => null, 'name');
        $metadata = $this->createMetadata();

        self::assertSame('World', $resolver->resolve($parameter, $metadata));
    }

    #[Test]
    public function resolveNullDefaultValue(): void
    {
        $resolver = new DefaultValueParameterValueResolver();
        $parameter = $this->getParameter(fn (?string $name = null) => null, 'name');
        $metadata = $this->createMetadata();

        self::assertNull($resolver->resolve($parameter, $metadata));
    }

    #[Test]
    public function throwsWhenNoDefaultValue(): void
    {
        $resolver = new DefaultValueParameterValueResolver();
        $parameter = $this->getParameter(fn (string $name) => null, 'name');
        $metadata = $this->createMetadata();

        $this->expectException(ParameterNotSupportedException::class);
        $resolver->resolve($parameter, $metadata);
    }
}
