<?php

namespace OpenSolid\CallableInvoker\Tests\ValueResolver;

use OpenSolid\CallableInvoker\Exception\ParameterNotSupportedException;
use OpenSolid\CallableInvoker\Metadata;
use OpenSolid\CallableInvoker\ValueResolver\NullableParameterValueResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class NullableParameterValueResolverTest extends TestCase
{
    #[Test]
    public function resolveNullableParameter(): void
    {
        $resolver = new NullableParameterValueResolver();
        $parameter = $this->getParameter(fn (?string $name) => null, 'name');
        $reflection = new \ReflectionFunction(fn () => null);
        $metadata = new Metadata($reflection, 'test', []);

        self::assertNull($resolver->resolve($parameter, $metadata));
    }

    #[Test]
    public function throwsWhenParameterNotNullable(): void
    {
        $resolver = new NullableParameterValueResolver();
        $parameter = $this->getParameter(fn (string $name) => null, 'name');
        $reflection = new \ReflectionFunction(fn () => null);
        $metadata = new Metadata($reflection, 'test', []);

        $this->expectException(ParameterNotSupportedException::class);
        $resolver->resolve($parameter, $metadata);
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
