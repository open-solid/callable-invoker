<?php

namespace OpenSolid\CallableInvoker;

final readonly class CallableMetadata
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        public \ReflectionFunction $function,
        public string $identifier,
        public array $context,
        public string $group,
    ) {
    }
}
