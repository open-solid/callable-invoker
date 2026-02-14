<?php

declare(strict_types=1);

namespace OpenSolid\CallableInvoker\ValueResolver\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class AsParameterValueResolver
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
