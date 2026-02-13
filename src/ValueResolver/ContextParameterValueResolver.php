<?php

namespace OpenSolid\CallableInvoker\ValueResolver;

use OpenSolid\CallableInvoker\Exception\ParameterNotSupportedException;
use OpenSolid\CallableInvoker\Metadata;

final readonly class ContextParameterValueResolver implements ParameterValueResolverInterface
{
    public function resolve(\ReflectionParameter $parameter, Metadata $metadata): mixed
    {
        if (!array_key_exists($parameter->getName(), $metadata->context)) {
            throw new ParameterNotSupportedException();
        }

        return $metadata->context[$parameter->getName()];
    }
}
