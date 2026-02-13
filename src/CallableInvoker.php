<?php

namespace OpenSolid\CallableInvoker;

use OpenSolid\CallableInvoker\Decorator\FunctionDecoratorInterface;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolverInterface;

final readonly class CallableInvoker
{
    public function __construct(
        private FunctionDecoratorInterface $functionDecorator,
        private ParameterValueResolverInterface $parameterValueResolver,
    ) {
    }

    public function invoke(callable $callable, array $context = []): mixed
    {
        $closure = $callable(...);
        $function = new \ReflectionFunction($closure);
        $identifier = $function->getClosureScopeClass()?->getName().'::'.$function->getName();
        $metadata = new Metadata($function, $identifier, $context);
        $parameters = [];
        foreach ($function->getParameters() as $parameter) {
            $parameters[] = $this->parameterValueResolver->resolve($parameter, $metadata);
        }

        $decorated = $this->functionDecorator->decorate($closure, $metadata);

        return $decorated(...$parameters);
    }
}
