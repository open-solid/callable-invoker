<?php

namespace OpenSolid\CallableInvoker\ValueResolver;

use OpenSolid\CallableInvoker\Exception\ParameterNotSupportedException;
use OpenSolid\CallableInvoker\Metadata;

final readonly class NullableParameterValueResolver implements ParameterValueResolverInterface
{
    public function resolve(\ReflectionParameter $parameter, Metadata $metadata): mixed
    {
        if (!$parameter->allowsNull()) {
            throw new ParameterNotSupportedException();
        }

        return null;
    }
}
