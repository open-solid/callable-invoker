<?php

declare(strict_types=1);

namespace OpenSolid\CallableInvoker\Decorator;

final readonly class InMemoryFunctionDecoratorGroups implements FunctionDecoratorGroupsInterface
{
    /**
     * @param array<string, iterable<FunctionDecoratorInterface>> $groups
     */
    public function __construct(
        private array $groups = [],
    ) {
    }

    /**
     * @return iterable<FunctionDecoratorInterface>
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
