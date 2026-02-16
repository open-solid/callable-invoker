<?php

declare(strict_types=1);

namespace OpenSolid\CallableInvoker\Decorator;

final readonly class ClosureHandler
{
    /**
     * @param array<array-key, mixed> $arguments
     */
    public function __construct(
        public \Closure $closure,
        public array $arguments,
    ) {
    }

    public function handle(): mixed
    {
        return ($this->closure)(...$this->arguments);
    }
}
