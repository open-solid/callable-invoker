<?php

namespace OpenSolid\CallableInvoker\Decorator;

use OpenSolid\CallableInvoker\Exception\FunctionNotSupportedException;
use OpenSolid\CallableInvoker\Metadata;

final readonly class FunctionDecoratorChain implements FunctionDecoratorInterface
{
    /**
     * @param iterable<FunctionDecoratorInterface> $decorators
     */
    public function __construct(
        private iterable $decorators,
    ) {
    }

    public function decorate(\Closure $function, Metadata $metadata): \Closure
    {
        foreach ($this->decorators as $decorator) {
            try {
                $function = $decorator->decorate($function, $metadata);
            } catch (FunctionNotSupportedException) {
                // Try the next decorator
            }
        }

        return $function;
    }
}
