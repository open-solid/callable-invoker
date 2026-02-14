<?php

declare(strict_types=1);

namespace OpenSolid\CallableInvoker\Decorator\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class AsFunctionDecorator
{
    /**
     * @var list<string>
     */
    public array $groups;

    /**
     * @param list<string>|string $groups
     */
    public function __construct(array|string $groups, public int $priority = 0)
    {
        $this->groups = (array) $groups;
    }
}
