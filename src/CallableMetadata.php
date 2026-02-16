<?php

namespace OpenSolid\CallableInvoker;

final readonly class CallableMetadata
{
    /**
     * @param array<string, mixed> $context
     * @param list<string>         $groups
     */
    public function __construct(
        public \ReflectionFunction $function,
        public array $context,
        public array $groups,
    ) {
    }
}
