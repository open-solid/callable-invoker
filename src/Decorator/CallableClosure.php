<?php

declare(strict_types=1);

namespace OpenSolid\CallableInvoker\Decorator;

final readonly class CallableClosure
{
    /**
     * @param array<array-key, mixed> $args
     */
    public function __construct(
        public \Closure $closure,
        public array $args,
    ) {
    }

    public function call(): mixed
    {
        return ($this->closure)(...$this->args);
    }
}
