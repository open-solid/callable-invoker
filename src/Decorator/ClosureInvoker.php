<?php

declare(strict_types=1);

namespace OpenSolid\CallableInvoker\Decorator;

final readonly class ClosureInvoker
{
    /**
     * @param array<array-key, mixed> $arguments
     */
    public function __construct(
        public \Closure $function,
        public array $arguments,
    ) {
    }

    public function invoke(): mixed
    {
        return ($this->function)(...$this->arguments);
    }
}
