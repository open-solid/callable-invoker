<?php

namespace OpenSolid\CallableInvoker;

use OpenSolid\CallableInvoker\Decorator\CallableDecoratorChain;
use OpenSolid\CallableInvoker\Decorator\CallableDecoratorInterface;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolverChain;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolverInterface;

final readonly class CallableInvoker implements CallableInvokerInterface
{
    public function __construct(
        private CallableDecoratorInterface $decorator = new CallableDecoratorChain(),
        private ParameterValueResolverInterface $valueResolver = new ParameterValueResolverChain(),
    ) {
    }

    /**
     * @param array<string, mixed> $context
     *
     * @throws \ReflectionException
     */
    public function invoke(callable $callable, array $context = [], array $groups = [self::DEFAULT_GROUP]): mixed
    {
        $closure = $callable(...);
        $function = new \ReflectionFunction($closure);
        $metadata = new CallableMetadata($function, $context, $groups);
        $parameters = [];
        foreach ($function->getParameters() as $parameter) {
            $parameters[] = $this->valueResolver->resolve($parameter, $metadata);
        }

        $decorated = $this->decorator->decorate($closure, $metadata);

        return $decorated(...$parameters);
    }
}
