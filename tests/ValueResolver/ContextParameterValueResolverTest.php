<?php

namespace OpenSolid\CallableInvoker\Tests\ValueResolver;

use OpenSolid\CallableInvoker\Exception\ParameterNotSupportedException;
use OpenSolid\CallableInvoker\Tests\TestHelper;
use OpenSolid\CallableInvoker\ValueResolver\ContextParameterValueResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ContextParameterValueResolverTest extends TestCase
{
    use TestHelper;

    #[Test]
    public function resolveFromContext(): void
    {
        $resolver = new ContextParameterValueResolver();
        $parameter = $this->getParameter(fn (string $name) => null, 'name');
        $metadata = $this->createMetadata(['name' => 'Alice']);

        self::assertSame('Alice', $resolver->resolve($parameter, $metadata));
    }

    #[Test]
    public function resolveNullValueFromContext(): void
    {
        $resolver = new ContextParameterValueResolver();
        $parameter = $this->getParameter(fn (?string $name) => null, 'name');
        $metadata = $this->createMetadata(['name' => null]);

        self::assertNull($resolver->resolve($parameter, $metadata));
    }

    #[Test]
    public function throwsWhenParameterNotInContext(): void
    {
        $resolver = new ContextParameterValueResolver();
        $parameter = $this->getParameter(fn (string $name) => null, 'name');
        $metadata = $this->createMetadata();

        $this->expectException(ParameterNotSupportedException::class);
        $resolver->resolve($parameter, $metadata);
    }
}
