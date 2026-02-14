<?php

declare(strict_types=1);

namespace OpenSolid\CallableInvoker\Decorator\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class AsDecorator
{
    /**
     * @var list<string>
     */
    public array $groups;

    /**
     * @param list<string>|string $groups
     */
    public function __construct(array|string $groups)
    {
        $this->groups = (array) $groups;
    }
}
