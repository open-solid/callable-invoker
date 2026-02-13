<?php

namespace OpenSolid\CallableInvoker\ValueResolver;

use OpenSolid\CallableInvoker\FunctionMetadata;

final readonly class DefaultValueParameterValueResolver implements ParameterValueResolverInterface
{
    public function supports(\ReflectionParameter $parameter, FunctionMetadata $metadata, ?string $group = null): bool
    {
        return $parameter->isDefaultValueAvailable();
    }

    public function resolve(\ReflectionParameter $parameter, FunctionMetadata $metadata, ?string $group = null): mixed
    {
        return $parameter->getDefaultValue();
    }
}
