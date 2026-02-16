<?php

namespace OpenSolid\CallableInvoker\ValueResolver;

use OpenSolid\CallableInvoker\CallableInvokerInterface;
use OpenSolid\CallableInvoker\CallableMetadata;
use OpenSolid\CallableInvoker\CallableServiceLocatorInterface;
use OpenSolid\CallableInvoker\Exception\ParameterNotSupportedException;
use OpenSolid\CallableInvoker\Exception\SkipParameterException;
use OpenSolid\CallableInvoker\InMemoryCallableServiceLocator;

final readonly class ParameterValueResolver
{
    /** @var CallableServiceLocatorInterface<ParameterValueResolverInterface> */
    private CallableServiceLocatorInterface $resolvers;

    /**
     * @param CallableServiceLocatorInterface<ParameterValueResolverInterface>|null $resolvers
     */
    public function __construct(?CallableServiceLocatorInterface $resolvers = null)
    {
        $this->resolvers = $resolvers ?? new InMemoryCallableServiceLocator([
            CallableInvokerInterface::DEFAULT_GROUP => [
                new UnsupportedParameterValueResolver(),
                new ContextParameterValueResolver(),
                new DefaultValueParameterValueResolver(),
                new NullableParameterValueResolver(),
            ],
        ]);
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

        throw ParameterNotSupportedException::create($parameter);
    }
}
