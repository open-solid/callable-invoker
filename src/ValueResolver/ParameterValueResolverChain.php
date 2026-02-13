<?php

namespace OpenSolid\CallableInvoker\ValueResolver;

use OpenSolid\CallableInvoker\Exception\ParameterNotSupportedException;
use OpenSolid\CallableInvoker\Metadata;

final readonly class ParameterValueResolverChain implements ParameterValueResolverInterface
{
    /**
     * @param iterable<ParameterValueResolverInterface> $resolvers
     */
    public function __construct(
        private iterable $resolvers,
    ) {
    }

    public function resolve(\ReflectionParameter $parameter, Metadata $metadata): mixed
    {
        foreach ($this->resolvers as $resolver) {
            try {
                return $resolver->resolve($parameter, $metadata);
            } catch (ParameterNotSupportedException) {
                // Try the next resolver
            }
        }

        throw new ParameterNotSupportedException(sprintf('Could not resolve value for parameter "%s".', $parameter->getName()));
    }
}
