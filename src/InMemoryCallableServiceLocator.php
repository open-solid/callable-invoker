<?php

declare(strict_types=1);

namespace OpenSolid\CallableInvoker;

/**
 * @template T of object
 *
 * @implements CallableServiceLocatorInterface<T>
 */
final readonly class InMemoryCallableServiceLocator implements CallableServiceLocatorInterface
{
    /**
     * @param array<string, iterable<T>> $groups
     */
    public function __construct(
        private array $groups = [],
    ) {
    }

    /**
     * @param list<string> $groups
     *
     * @return iterable<T>
     */
    public function get(array $groups): iterable
    {
        $seen = [];
        foreach ($groups as $group) {
            foreach ($this->groups[$group] ?? [] as $service) {
                $id = spl_object_id($service);
                if (!isset($seen[$id])) {
                    $seen[$id] = true;
                    yield $service;
                }
            }
        }
    }
}
