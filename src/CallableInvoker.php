<?php

namespace OpenSolid\CallableInvoker;

use OpenSolid\CallableInvoker\Decorator\FunctionDecoratorInterface;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolverInterface;

final readonly class CallableInvoker implements CallableInvokerInterface
{
    public function __construct(
        private FunctionDecoratorInterface $decorator,
        private ParameterValueResolverInterface $valueResolver,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     *
     * @throws \ReflectionException
     */
    public function invoke(callable $callable, array $context = [], ?string $group = null): mixed
    {
        $closure = $callable(...);
        $function = new \ReflectionFunction($closure);
        $scope = $function->getClosureScopeClass()?->getName();
        $identifier = null !== $scope ? $scope.'::'.$function->getName() : $function->getName();
        $metadata = new FunctionMetadata($function, $identifier, $context);

        $parameters = [];
        foreach ($function->getParameters() as $parameter) {
            $parameters[] = $this->valueResolver->resolve($parameter, $metadata, $group);
        }

        $decorated = $this->decorator->decorate($closure, $metadata);

        return $decorated(...$parameters);
    }
}
