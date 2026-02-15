<?php

namespace OpenSolid\CallableInvoker\ValueResolver;

use OpenSolid\CallableInvoker\CallableMetadata;
use OpenSolid\CallableInvoker\Exception\ParameterNotSupportedException;
use OpenSolid\CallableInvoker\Exception\SkipParameterException;

interface ParameterValueResolverInterface
{
    public function supports(\ReflectionParameter $parameter, CallableMetadata $metadata): bool;

    /**
     * @throws ParameterNotSupportedException if the parameter cannot be resolved
     * @throws SkipParameterException         to signal the chain to try the next resolver
     */
    public function resolve(\ReflectionParameter $parameter, CallableMetadata $metadata): mixed;
}
