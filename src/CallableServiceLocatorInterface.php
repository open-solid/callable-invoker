<?php

declare(strict_types=1);

namespace OpenSolid\CallableInvoker;

/**
 * @template-covariant T of object
 */
interface CallableServiceLocatorInterface
{
    /**
     * @param list<string> $groups
     *
     * @return iterable<T>
     */
    public function get(array $groups): iterable;
}
