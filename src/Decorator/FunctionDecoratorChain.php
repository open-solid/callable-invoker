<?php

namespace OpenSolid\CallableInvoker\Decorator;

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

    public function supports(Metadata $metadata): bool
    {
        foreach ($this->decorators as $decorator) {
            if ($decorator->supports($metadata)) {
                return true;
            }
        }

        return false;
    }

    public function decorate(\Closure $function, Metadata $metadata): \Closure
    {
        foreach ($this->decorators as $decorator) {
            if ($decorator->supports($metadata)) {
                $function = $decorator->decorate($function, $metadata);
            }
        }

        return $function;
    }
}
