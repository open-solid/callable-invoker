<?php

namespace OpenSolid\CallableInvoker\ValueResolver;

use OpenSolid\CallableInvoker\Exception\ParameterNotSupportedException;
use OpenSolid\CallableInvoker\Metadata;

final readonly class DefaultValueParameterValueResolver implements ParameterValueResolverInterface
{
    public function resolve(\ReflectionParameter $parameter, Metadata $metadata): mixed
    {
        if (!$parameter->isDefaultValueAvailable()) {
            throw new ParameterNotSupportedException();
        }

        return $parameter->getDefaultValue();
    }
}
