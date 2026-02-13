<?php

namespace OpenSolid\CallableInvoker\ValueResolver;

use OpenSolid\CallableInvoker\FunctionMetadata;

final readonly class DefaultValueParameterValueResolver implements ParameterValueResolverInterface
{
    public function supports(\ReflectionParameter $parameter, FunctionMetadata $metadata): bool
    {
        return $parameter->isDefaultValueAvailable();
    }

    public function resolve(\ReflectionParameter $parameter, FunctionMetadata $metadata): mixed
    {
        return $parameter->getDefaultValue();
    }
}
