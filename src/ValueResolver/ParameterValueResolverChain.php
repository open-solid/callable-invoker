<?php

namespace OpenSolid\CallableInvoker\ValueResolver;

use OpenSolid\CallableInvoker\CallableMetadata;
use OpenSolid\CallableInvoker\Exception\ParameterNotSupportedException;
use OpenSolid\CallableInvoker\Exception\SkipParameterException;
use Psr\Container\ContainerInterface;

final readonly class ParameterValueResolverChain implements ParameterValueResolverInterface
{
    public function __construct(
        private ContainerInterface $container,
    ) {
    }

    public function supports(\ReflectionParameter $parameter, CallableMetadata $metadata): bool
    {
        foreach ($this->getResolvers($metadata->group) as $resolver) {
            if ($resolver->supports($parameter, $metadata)) {
                return true;
            }
        }

        return false;
    }

    public function resolve(\ReflectionParameter $parameter, CallableMetadata $metadata): mixed
    {
        foreach ($this->getResolvers($metadata->group) as $resolver) {
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

    /**
     * @return iterable<ParameterValueResolverInterface>
     */
    private function getResolvers(string $group): iterable
    {
        if (!$this->container->has($group)) {
            return [];
        }

        /* @phpstan-ignore-next-line */
        return $this->container->get($group);
    }
}
