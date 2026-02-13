<?php

namespace OpenSolid\CallableInvoker\ValueResolver;

use OpenSolid\CallableInvoker\Exception\UntypedParameterNotSupportedException;
use OpenSolid\CallableInvoker\Exception\VariadicParameterNotSupportedException;
use OpenSolid\CallableInvoker\FunctionMetadata;

final readonly class UnsupportedParameterValueResolver implements ParameterValueResolverInterface
{
    public function supports(\ReflectionParameter $parameter, FunctionMetadata $metadata): bool
    {
        return $parameter->isVariadic() || !$parameter->hasType();
    }

    public function resolve(\ReflectionParameter $parameter, FunctionMetadata $metadata): mixed
    {
        if ($parameter->isVariadic()) {
            throw new VariadicParameterNotSupportedException($parameter->getName(), $metadata->identifier);
        }

        throw new UntypedParameterNotSupportedException($parameter->getName(), $metadata->identifier);
    }
}
