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
            throw VariadicParameterNotSupportedException::create($parameter);
        }

        if (!$parameter->hasType()) {
            throw UntypedParameterNotSupportedException::create($parameter);
        }

        throw ParameterNotSupportedException::create($parameter);
    }
}
