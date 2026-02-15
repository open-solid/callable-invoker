<?php

namespace OpenSolid\CallableInvoker\Decorator;

use OpenSolid\CallableInvoker\CallableMetadata;
use Psr\Container\ContainerInterface;

final readonly class FunctionDecoratorChain implements FunctionDecoratorInterface
{
    public function __construct(
        private ContainerInterface $container,
    ) {
    }

    public function supports(CallableMetadata $metadata): bool
    {
        foreach ($this->getDecorators($metadata->group) as $decorator) {
            if ($decorator->supports($metadata)) {
                return true;
            }
        }

        return false;
    }

    public function decorate(\Closure $function, CallableMetadata $metadata): \Closure
    {
        foreach ($this->getDecorators($metadata->group) as $decorator) {
            if ($decorator->supports($metadata)) {
                $function = $decorator->decorate($function, $metadata);
            }
        }

        return $function;
    }

    /**
     * @return iterable<FunctionDecoratorInterface>
     */
    private function getDecorators(?string $group): iterable
    {
        $key = $group ?? '__NONE__';

        if (!$this->container->has($key)) {
            return [];
        }

        /* @phpstan-ignore-next-line */
        return $this->container->get($key);
    }
}
