<?php

declare(strict_types=1);

namespace OpenSolid\CallableInvoker\ValueResolver;

use OpenSolid\CallableInvoker\CallableMetadata;

final readonly class ContextParameterValueResolver implements ParameterValueResolverInterface
{
    public function supports(\ReflectionParameter $parameter, CallableMetadata $metadata): bool
    {
        return \array_key_exists($parameter->getName(), $metadata->context);
    }

    public function resolve(\ReflectionParameter $parameter, CallableMetadata $metadata): mixed
    {
        return $metadata->context[$parameter->getName()];
    }
}
