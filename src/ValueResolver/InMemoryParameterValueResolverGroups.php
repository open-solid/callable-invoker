<?php

declare(strict_types=1);

namespace OpenSolid\CallableInvoker\ValueResolver;

use OpenSolid\CallableInvoker\CallableInvokerInterface;

final readonly class InMemoryParameterValueResolverGroups implements ParameterValueResolverGroupsInterface
{
    /**
     * @param array<string, iterable<ParameterValueResolverInterface>> $groups
     */
    public function __construct(
        private array $groups = [
            CallableInvokerInterface::DEFAULT_GROUP => [
                new UnsupportedParameterValueResolver(),
                new DefaultValueParameterValueResolver(),
                new NullableParameterValueResolver(),
            ],
        ],
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
            foreach ($this->groups[$group] ?? [] as $resolver) {
                $id = spl_object_id($resolver);
                if (!isset($seen[$id])) {
                    $seen[$id] = true;
                    yield $resolver;
                }
            }
        }
    }
}
