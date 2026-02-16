<?php

declare(strict_types=1);

namespace OpenSolid\CallableInvoker;

use Psr\Container\ContainerInterface;

/**
 * @template T of object
 *
 * @implements CallableServiceLocatorInterface<T>
 */
final readonly class CallableServiceLocator implements CallableServiceLocatorInterface
{
    public function __construct(
        private ContainerInterface $container,
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
            if (!$this->container->has($group)) {
                continue;
            }

            /** @var iterable<T> $services */
            $services = $this->container->get($group);
            foreach ($services as $service) {
                $id = spl_object_id($service);
                if (!isset($seen[$id])) {
                    $seen[$id] = true;
                    yield $service;
                }
            }
        }
    }
}
