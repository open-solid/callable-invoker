<?php

declare(strict_types=1);

namespace OpenSolid\CallableInvoker\ValueResolver;

use Psr\Container\ContainerInterface;

final readonly class ParameterValueResolverGroups implements ParameterValueResolverGroupsInterface
{
    public function __construct(
        private ContainerInterface $container,
    ) {
    }

    /**
     * @return iterable<ParameterValueResolverInterface>
     */
    public function get(string $group): iterable
    {
        if (!$this->container->has($group)) {
            return [];
        }

        /* @phpstan-ignore-next-line */
        return $this->container->get($group);
    }

    public function has(string $group): bool
    {
        return $this->container->has($group);
    }
}
