<?php

namespace OpenSolid\CallableInvoker\ValueResolver;

use OpenSolid\CallableInvoker\Exception\ParameterNotSupportedException;
use OpenSolid\CallableInvoker\FunctionMetadata;

final readonly class ParameterValueResolverChain implements ParameterValueResolverInterface
{
    /**
     * @param iterable<ParameterValueResolverInterface> $resolvers
     */
    public function __construct(
        private iterable $resolvers,
    ) {
    }

    public function supports(\ReflectionParameter $parameter, FunctionMetadata $metadata): bool
    {
        foreach ($this->resolvers as $resolver) {
            if ($resolver->supports($parameter, $metadata)) {
                return true;
            }
        }

        return false;
    }

    public function resolve(\ReflectionParameter $parameter, FunctionMetadata $metadata): mixed
    {
        foreach ($this->resolvers as $resolver) {
            if ($resolver->supports($parameter, $metadata)) {
                return $resolver->resolve($parameter, $metadata);
            }
        }

        throw new ParameterNotSupportedException($parameter->getName(), $metadata->identifier);
    }
}
