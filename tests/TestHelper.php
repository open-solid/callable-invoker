<?php

namespace OpenSolid\CallableInvoker\Tests;

use OpenSolid\CallableInvoker\FunctionMetadata;

trait TestHelper
{
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

    /**
     * @param array<string, mixed> $context
     */
    private function createMetadata(array $context = []): FunctionMetadata
    {
        return new FunctionMetadata(new \ReflectionFunction(fn () => null), 'test', $context);
    }
}
