<?php

namespace OpenSolid\CallableInvoker\ValueResolver;

use OpenSolid\CallableInvoker\CallableMetadata;
use OpenSolid\CallableInvoker\Exception\ParameterNotSupportedException;
use OpenSolid\CallableInvoker\Exception\SkipParameterException;

final readonly class ParameterValueResolverChain implements ParameterValueResolverInterface
{
    public function __construct(
        private ParameterValueResolverGroupsInterface $groups = new InMemoryParameterValueResolverGroups(),
    ) {
    }

    public function supports(\ReflectionParameter $parameter, CallableMetadata $metadata): bool
    {
        foreach ($this->groups->get($metadata->group) as $resolver) {
            if ($resolver->supports($parameter, $metadata)) {
                return true;
            }
        }

        return false;
    }

    public function resolve(\ReflectionParameter $parameter, CallableMetadata $metadata): mixed
    {
        foreach ($this->groups->get($metadata->group) as $resolver) {
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
