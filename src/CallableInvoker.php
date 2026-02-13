<?php

namespace OpenSolid\CallableInvoker;

use OpenSolid\CallableInvoker\Decorator\FunctionDecoratorInterface;
use OpenSolid\CallableInvoker\Exception\VariadicParameterNotSupportedException;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolverInterface;

final readonly class CallableInvoker
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
    public function invoke(callable $callable, array $context = []): mixed
    {
        $closure = $callable(...);
        $function = new \ReflectionFunction($closure);
        $scope = $function->getClosureScopeClass()?->getName();
        $identifier = null !== $scope ? $scope.'::'.$function->getName() : $function->getName();
        $metadata = new FunctionMetadata($function, $identifier, $context);

        $parameters = [];
        foreach ($function->getParameters() as $parameter) {
            if ($parameter->isVariadic()) {
                throw new VariadicParameterNotSupportedException($parameter->getName(), $identifier);
            }

            $parameters[] = $this->valueResolver->resolve($parameter, $metadata);
        }

        $decorated = $this->decorator->decorate($closure, $metadata);

        return $decorated(...$parameters);
    }
}
