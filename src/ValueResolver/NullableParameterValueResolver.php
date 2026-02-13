<?php

namespace OpenSolid\CallableInvoker\ValueResolver;

use OpenSolid\CallableInvoker\Metadata;

final readonly class NullableParameterValueResolver implements ParameterValueResolverInterface
{
    public function supports(\ReflectionParameter $parameter, Metadata $metadata): bool
    {
        return $parameter->allowsNull();
    }

    public function resolve(\ReflectionParameter $parameter, Metadata $metadata): mixed
    {
        return null;
    }
}
