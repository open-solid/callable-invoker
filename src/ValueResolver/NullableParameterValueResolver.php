<?php

namespace OpenSolid\CallableInvoker\ValueResolver;

use OpenSolid\CallableInvoker\CallableMetadata;

final readonly class NullableParameterValueResolver implements ParameterValueResolverInterface
{
    public function supports(\ReflectionParameter $parameter, CallableMetadata $metadata): bool
    {
        return $parameter->allowsNull();
    }

    public function resolve(\ReflectionParameter $parameter, CallableMetadata $metadata): null
    {
        return null;
    }
}
