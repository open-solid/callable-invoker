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
     * @return iterable<ParameterValueResolverInterface>
     */
    public function get(string $group): iterable
    {
        return $this->groups[$group] ?? [];
    }

    public function has(string $group): bool
    {
        return isset($this->groups[$group]);
    }
}
