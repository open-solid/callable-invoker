<?php

declare(strict_types=1);

namespace OpenSolid\CallableInvoker\Decorator;

final readonly class ClosureInvoker
{
    /**
     * @param array<array-key, mixed> $args
     */
    public function __construct(
        public \Closure $closure,
        public array $args,
    ) {
    }

    public function invoke(): mixed
    {
        return ($this->closure)(...$this->args);
    }
}
