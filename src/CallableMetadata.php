<?php

namespace OpenSolid\CallableInvoker;

final class CallableMetadata
{
    /**
     * @var array<string, mixed>
     */
    private array $attributes = [];

    /**
     * @param array<string, mixed> $context
     * @param list<string>         $groups
     */
    public function __construct(
        public readonly \ReflectionFunction $function,
        public readonly array $context,
        public readonly array $groups,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function hasAttribute(string $name): bool
    {
        return \array_key_exists($name, $this->attributes);
    }

    public function setAttribute(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
    }

    public function getAttribute(string $name, ?\Closure $create = null): mixed
    {
        if (!$this->hasAttribute($name)) {
            if (null === $create) {
                throw new \InvalidArgumentException(\sprintf('Attribute "%s" does not exist.', $name));
            }

            return $this->attributes[$name] = $create();
        }

        return $this->attributes[$name];
    }
}
