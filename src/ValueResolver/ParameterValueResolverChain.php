<?php

namespace OpenSolid\CallableInvoker\ValueResolver;

use OpenSolid\CallableInvoker\CallableInvokerInterface;
use OpenSolid\CallableInvoker\CallableMetadata;
use OpenSolid\CallableInvoker\CallableServiceLocatorInterface;
use OpenSolid\CallableInvoker\Exception\ParameterNotSupportedException;
use OpenSolid\CallableInvoker\Exception\SkipParameterException;
use OpenSolid\CallableInvoker\InMemoryCallableServiceLocator;

final readonly class ParameterValueResolverChain implements ParameterValueResolverInterface
{
    /**
     * @param CallableServiceLocatorInterface<ParameterValueResolverInterface> $resolvers
     */
    public function __construct(
        private CallableServiceLocatorInterface $resolvers = new InMemoryCallableServiceLocator([
            CallableInvokerInterface::DEFAULT_GROUP => [
                new UnsupportedParameterValueResolver(),
                new ContextParameterValueResolver(),
                new DefaultValueParameterValueResolver(),
                new NullableParameterValueResolver(),
            ],
        ]),
    ) {
    }

    public function supports(\ReflectionParameter $parameter, CallableMetadata $metadata): bool
    {
        foreach ($this->resolvers->get($metadata->groups) as $resolver) {
            if ($resolver->supports($parameter, $metadata)) {
                return true;
            }
        }

        return false;
    }

    public function resolve(\ReflectionParameter $parameter, CallableMetadata $metadata): mixed
    {
        foreach ($this->resolvers->get($metadata->groups) as $resolver) {
            if ($resolver->supports($parameter, $metadata)) {
                try {
                    return $resolver->resolve($parameter, $metadata);
                } catch (SkipParameterException) {
                    continue;
                }
            }
        }

        throw new ParameterNotSupportedException($parameter->getName(), $metadata->identifier);
    }
}
