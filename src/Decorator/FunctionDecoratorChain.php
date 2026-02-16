<?php

namespace OpenSolid\CallableInvoker\Decorator;

use OpenSolid\CallableInvoker\CallableMetadata;

final readonly class FunctionDecoratorChain implements FunctionDecoratorInterface
{
    public function __construct(
        private FunctionDecoratorGroupsInterface $groups = new InMemoryFunctionDecoratorGroups(),
    ) {
    }

    public function supports(CallableMetadata $metadata): bool
    {
        foreach ($this->groups->get($metadata->group) as $decorator) {
            if ($decorator->supports($metadata)) {
                return true;
            }
        }

        return false;
    }

    public function decorate(\Closure $function, CallableMetadata $metadata): \Closure
    {
        foreach ($this->groups->get($metadata->group) as $decorator) {
            if ($decorator->supports($metadata)) {
                $function = $decorator->decorate($function, $metadata);
            }
        }

        return $function;
    }
}
