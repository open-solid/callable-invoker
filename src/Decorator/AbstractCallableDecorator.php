<?php

declare(strict_types=1);

namespace OpenSolid\CallableInvoker\Decorator;

use OpenSolid\CallableInvoker\CallableMetadata;

abstract class AbstractCallableDecorator implements CallableDecoratorInterface
{
    final public function decorate(\Closure $closure, CallableMetadata $metadata): \Closure
    {
        return function (mixed ...$args) use ($closure, $metadata): mixed {
            return $this->call(new ClosureInvoker($closure, $args), $metadata);
        };
    }

    abstract protected function call(ClosureInvoker $invoker, CallableMetadata $metadata): mixed;
}
