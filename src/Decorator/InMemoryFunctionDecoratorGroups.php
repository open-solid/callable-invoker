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
     * @param list<string> $groups
     *
     * @return iterable<FunctionDecoratorInterface>
     */
    public function get(array $groups): iterable
    {
        $seen = [];
        foreach ($groups as $group) {
            foreach ($this->groups[$group] ?? [] as $decorator) {
                $id = spl_object_id($decorator);
                if (!isset($seen[$id])) {
                    $seen[$id] = true;
                    yield $decorator;
                }
            }
        }
    }
}
