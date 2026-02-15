<?php

namespace OpenSolid\CallableInvoker\ValueResolver;

use OpenSolid\CallableInvoker\CallableMetadata;
use OpenSolid\CallableInvoker\Exception\ParameterNotSupportedException;
use OpenSolid\CallableInvoker\Exception\UntypedParameterNotSupportedException;
use OpenSolid\CallableInvoker\Exception\VariadicParameterNotSupportedException;

final readonly class UnsupportedParameterValueResolver implements ParameterValueResolverInterface
{
    public function supports(\ReflectionParameter $parameter, CallableMetadata $metadata, ?string $group = null): bool
    {
        return $parameter->isVariadic() || !$parameter->hasType();
    }

    public function resolve(\ReflectionParameter $parameter, CallableMetadata $metadata, ?string $group = null): never
    {
        if ($parameter->isVariadic()) {
            throw new VariadicParameterNotSupportedException($parameter->getName(), $metadata->identifier);
        }

        if (!$parameter->hasType()) {
            throw new UntypedParameterNotSupportedException($parameter->getName(), $metadata->identifier);
        }

        throw new ParameterNotSupportedException($parameter->getName(), $metadata->identifier);
    }
}
