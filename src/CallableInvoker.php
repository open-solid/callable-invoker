<?php

namespace OpenSolid\CallableInvoker;

use OpenSolid\CallableInvoker\Decorator\CallableDecorator;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolver;

final readonly class CallableInvoker implements CallableInvokerInterface, CallableParameterValueResolverInterface, CallableDecoratorProviderInterface
{
    public function __construct(
        private CallableDecorator $decorator = new CallableDecorator(),
        private ParameterValueResolver $valueResolver = new ParameterValueResolver(),
    ) {
    }

    public function invoke(callable $callable, array $context = [], array $groups = [self::DEFAULT_GROUP]): mixed
    {
        $function = new \ReflectionFunction($closure = $callable(...));
        $metadata = new CallableMetadata($function, $context, $groups);

        $parameters = [];
        foreach ($function->getParameters() as $parameter) {
            $parameters[] = $this->valueResolver->resolve($parameter, $metadata);
        }

        $decorated = $this->decorator->decorate($closure, $metadata);

        return $decorated(...$parameters);
    }

    public function resolve(callable $callable, array $context = [], array $groups = [CallableInvokerInterface::DEFAULT_GROUP]): array
    {
        $function = new \ReflectionFunction($callable(...));
        $metadata = new CallableMetadata($function, $context, $groups);

        $parameters = [];
        foreach ($function->getParameters() as $parameter) {
            $parameters[] = $this->valueResolver->resolve($parameter, $metadata);
        }

        return $parameters;
    }

    public function decorate(callable $callable, array $context = [], array $groups = [CallableInvokerInterface::DEFAULT_GROUP]): \Closure
    {
        $function = new \ReflectionFunction($closure = $callable(...));
        $metadata = new CallableMetadata($function, $context, $groups);

        return $this->decorator->decorate($closure, $metadata);
    }
}
