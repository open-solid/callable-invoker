<?php

namespace OpenSolid\CallableInvoker\Decorator;

use OpenSolid\CallableInvoker\CallableMetadata;
use OpenSolid\CallableInvoker\CallableServiceLocatorInterface;
use OpenSolid\CallableInvoker\InMemoryCallableServiceLocator;

final readonly class FunctionDecoratorChain implements FunctionDecoratorInterface
{
    /**
     * @param CallableServiceLocatorInterface<FunctionDecoratorInterface> $decorators
     */
    public function __construct(
        private CallableServiceLocatorInterface $decorators = new InMemoryCallableServiceLocator(),
    ) {
    }

    public function supports(CallableMetadata $metadata): bool
    {
        foreach ($this->decorators->get($metadata->groups) as $decorator) {
            if ($decorator->supports($metadata)) {
                return true;
            }
        }

        return false;
    }

    public function decorate(\Closure $function, CallableMetadata $metadata): \Closure
    {
        foreach ($this->decorators->get($metadata->groups) as $decorator) {
            if ($decorator->supports($metadata)) {
                $function = $decorator->decorate($function, $metadata);
            }
        }

        return $function;
    }
}
