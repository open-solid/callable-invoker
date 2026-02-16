<?php

declare(strict_types=1);

namespace OpenSolid\CallableInvoker\Decorator;

use Psr\Container\ContainerInterface;

final readonly class FunctionDecoratorGroups implements FunctionDecoratorGroupsInterface
{
    public function __construct(
        private ContainerInterface $container,
    ) {
    }

    /**
     * @return iterable<FunctionDecoratorInterface>
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
