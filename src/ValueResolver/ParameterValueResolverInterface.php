<?php

namespace OpenSolid\CallableInvoker\ValueResolver;

use OpenSolid\CallableInvoker\Exception\ParameterNotSupportedException;
use OpenSolid\CallableInvoker\Exception\SkipParameterException;
use OpenSolid\CallableInvoker\FunctionMetadata;

interface ParameterValueResolverInterface
{
    public function supports(\ReflectionParameter $parameter, FunctionMetadata $metadata): bool;

    /**
     * @throws ParameterNotSupportedException if the parameter cannot be resolved
     * @throws SkipParameterException         to signal the chain to try the next resolver
     */
    public function resolve(\ReflectionParameter $parameter, FunctionMetadata $metadata): mixed;
}
