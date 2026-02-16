<?php

declare(strict_types=1);

namespace OpenSolid\CallableInvoker\Decorator;

use OpenSolid\CallableInvoker\CallableMetadata;

abstract class AbstractCallableDecorator implements CallableDecoratorInterface
{
    final public function decorate(\Closure $closure, CallableMetadata $metadata): \Closure
    {
        return function (mixed ...$args) use ($closure, $metadata): mixed {
            return $this->invoke(new ClosureHandler($closure, $args), $metadata);
        };
    }

    abstract protected function invoke(ClosureHandler $handler, CallableMetadata $metadata): mixed;
}
