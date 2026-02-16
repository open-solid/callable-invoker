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
     * @param list<string> $groups
     *
     * @return iterable<ParameterValueResolverInterface>
     */
    public function get(array $groups): iterable
    {
        $seen = [];
        foreach ($groups as $group) {
            if (!$this->container->has($group)) {
                continue;
            }

            /** @var iterable<ParameterValueResolverInterface> $resolvers */
            $resolvers = $this->container->get($group);
            foreach ($resolvers as $resolver) {
                $id = spl_object_id($resolver);
                if (!isset($seen[$id])) {
                    $seen[$id] = true;
                    yield $resolver;
                }
            }
        }
    }
}
