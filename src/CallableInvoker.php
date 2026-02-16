<?php

namespace OpenSolid\CallableInvoker;

use OpenSolid\CallableInvoker\Decorator\CallableDecorator;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolver;

final readonly class CallableInvoker implements CallableInvokerInterface
{
    public function __construct(
        private CallableDecorator $decorator = new CallableDecorator(),
        private ParameterValueResolver $valueResolver = new ParameterValueResolver(),
    ) {
    }

    /**
     * @param array<string, mixed> $context
     *
     * @throws \ReflectionException
     */
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
}
