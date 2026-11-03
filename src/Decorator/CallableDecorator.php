<?php

namespace OpenSolid\CallableInvoker\Decorator;

use OpenSolid\CallableInvoker\CallableMetadata;
use OpenSolid\CallableInvoker\CallableServiceLocatorInterface;
use OpenSolid\CallableInvoker\InMemoryCallableServiceLocator;

/**
 * @internal
 */
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
        $this->decorators = $decorators ?? self::noDecorators();
    }

    /**
     * @return CallableServiceLocatorInterface<CallableDecoratorInterface>
     */
    private static function noDecorators(): CallableServiceLocatorInterface
    {
        /** @var array<string, iterable<CallableDecoratorInterface>> $groups */
        $groups = [];

        return new InMemoryCallableServiceLocator($groups);
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
