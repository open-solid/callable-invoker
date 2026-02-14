<?php

namespace OpenSolid\CallableInvoker\ValueResolver;

use OpenSolid\CallableInvoker\Exception\ParameterNotSupportedException;
use OpenSolid\CallableInvoker\Exception\SkipParameterException;
use OpenSolid\CallableInvoker\FunctionMetadata;
use Psr\Container\ContainerInterface;

final readonly class ParameterValueResolverChain implements ParameterValueResolverInterface
{
    public function __construct(
        private ContainerInterface $container,
    ) {
    }

    public function supports(\ReflectionParameter $parameter, FunctionMetadata $metadata, ?string $group = null): bool
    {
        foreach ($this->getResolvers($group) as $resolver) {
            if ($resolver->supports($parameter, $metadata)) {
                return true;
            }
        }

        return false;
    }

    public function resolve(\ReflectionParameter $parameter, FunctionMetadata $metadata, ?string $group = null): mixed
    {
        foreach ($this->getResolvers($group) as $resolver) {
            if ($resolver->supports($parameter, $metadata, $group)) {
                try {
                    return $resolver->resolve($parameter, $metadata, $group);
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
    private function getResolvers(?string $group): iterable
    {
        $key = $group ?? '__NONE__';

        if (!$this->container->has($key)) {
            return [];
        }

        /* @phpstan-ignore-next-line */
        return $this->container->get($key);
    }
}
