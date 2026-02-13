<?php

namespace OpenSolid\CallableInvoker\ValueResolver;

use OpenSolid\CallableInvoker\FunctionMetadata;

final readonly class NullableParameterValueResolver implements ParameterValueResolverInterface
{
    public function supports(\ReflectionParameter $parameter, FunctionMetadata $metadata, ?string $group = null): bool
    {
        return $parameter->allowsNull();
    }

    public function resolve(\ReflectionParameter $parameter, FunctionMetadata $metadata, ?string $group = null): null
    {
        return null;
    }
}
