<?php

namespace OpenSolid\CallableInvoker\ValueResolver;

use OpenSolid\CallableInvoker\Exception\ParameterNotSupportedException;
use OpenSolid\CallableInvoker\Metadata;

interface ParameterValueResolverInterface
{
    public function supports(\ReflectionParameter $parameter, Metadata $metadata): bool;

    /**
     * @throws ParameterNotSupportedException if the parameter cannot be resolved
     */
    public function resolve(\ReflectionParameter $parameter, Metadata $metadata): mixed;
}
