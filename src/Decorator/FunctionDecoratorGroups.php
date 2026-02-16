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
     * @param list<string> $groups
     *
     * @return iterable<FunctionDecoratorInterface>
     */
    public function get(array $groups): iterable
    {
        $seen = [];
        foreach ($groups as $group) {
            if (!$this->container->has($group)) {
                continue;
            }

            /** @var iterable<FunctionDecoratorInterface> $decorators */
            $decorators = $this->container->get($group);
            foreach ($decorators as $decorator) {
                $id = spl_object_id($decorator);
                if (!isset($seen[$id])) {
                    $seen[$id] = true;
                    yield $decorator;
                }
            }
        }
    }
}
