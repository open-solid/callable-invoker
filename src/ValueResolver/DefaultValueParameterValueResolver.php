<?php

namespace OpenSolid\CallableInvoker\ValueResolver;

use OpenSolid\CallableInvoker\Metadata;

final readonly class DefaultValueParameterValueResolver implements ParameterValueResolverInterface
{
    public function supports(\ReflectionParameter $parameter, Metadata $metadata): bool
    {
        return $parameter->isDefaultValueAvailable();
    }

    public function resolve(\ReflectionParameter $parameter, Metadata $metadata): mixed
    {
        return $parameter->getDefaultValue();
    }
}
