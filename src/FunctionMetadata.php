<?php

namespace OpenSolid\CallableInvoker;

final readonly class FunctionMetadata
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        public \ReflectionFunction $function,
        public string $identifier,
        public array $context,
    ) {
    }
}
