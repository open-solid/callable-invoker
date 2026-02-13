<?php

namespace OpenSolid\CallableInvoker;

final readonly class Metadata
{
    public function __construct(
        public \ReflectionFunction $function,
        public string $identifier,
        public array $context,
    ) {
    }
}
