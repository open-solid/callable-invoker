<?php

namespace OpenSolid\CallableInvoker\Tests\ValueResolver;

use OpenSolid\CallableInvoker\Exception\ParameterNotSupportedException;
use OpenSolid\CallableInvoker\Metadata;
use OpenSolid\CallableInvoker\ValueResolver\ContextParameterValueResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ContextParameterValueResolverTest extends TestCase
{
    #[Test]
    public function resolveFromContext(): void
    {
        $resolver = new ContextParameterValueResolver();
        $parameter = $this->getParameter(fn (string $name) => null, 'name');
        $reflection = new \ReflectionFunction(fn () => null);
        $metadata = new Metadata($reflection, 'test', ['name' => 'Alice']);

        self::assertSame('Alice', $resolver->resolve($parameter, $metadata));
    }

    #[Test]
    public function resolveNullValueFromContext(): void
    {
        $resolver = new ContextParameterValueResolver();
        $parameter = $this->getParameter(fn (?string $name) => null, 'name');
        $reflection = new \ReflectionFunction(fn () => null);
        $metadata = new Metadata($reflection, 'test', ['name' => null]);

        self::assertNull($resolver->resolve($parameter, $metadata));
    }

    #[Test]
    public function throwsWhenParameterNotInContext(): void
    {
        $resolver = new ContextParameterValueResolver();
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
