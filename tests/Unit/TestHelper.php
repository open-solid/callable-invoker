<?php

namespace OpenSolid\CallableInvoker\Tests\Unit;

use OpenSolid\CallableInvoker\CallableInvokerInterface;
use OpenSolid\CallableInvoker\CallableMetadata;

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
    private function createMetadata(array $context = [], string $group = CallableInvokerInterface::DEFAULT_GROUP): CallableMetadata
    {
        return new CallableMetadata(new \ReflectionFunction(fn () => null), 'test', $context, $group);
    }
}
