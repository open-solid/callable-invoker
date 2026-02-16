<?php

namespace OpenSolid\CallableInvoker\Decorator;

use OpenSolid\CallableInvoker\CallableMetadata;
use OpenSolid\CallableInvoker\CallableServiceLocatorInterface;
use OpenSolid\CallableInvoker\InMemoryCallableServiceLocator;

final readonly class CallableDecorator
{
    /** @var CallableServiceLocatorInterface<CallableDecoratorInterface> */
    private CallableServiceLocatorInterface $decorators;

    /**
     * @param CallableServiceLocatorInterface<CallableDecoratorInterface>|null $decorators
     */
    public function __construct(
        ?CallableServiceLocatorInterface $decorators = null,
    ) {
        $this->decorators = $decorators ?? new InMemoryCallableServiceLocator();
    }

    public function decorate(\Closure $closure, CallableMetadata $metadata): \Closure
    {
        foreach ($this->decorators->get($metadata->groups) as $decorator) {
            if ($decorator->supports($metadata)) {
                $closure = static fn (mixed ...$args) => $decorator->decorate(new CallableClosure($closure, $args), $metadata);
            }
        }

        return $closure;
    }
}
