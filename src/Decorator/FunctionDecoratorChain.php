<?php

namespace OpenSolid\CallableInvoker\Decorator;

use OpenSolid\CallableInvoker\FunctionMetadata;
use Psr\Container\ContainerInterface;

final readonly class FunctionDecoratorChain implements FunctionDecoratorInterface
{
    public function __construct(
        private ContainerInterface $container,
    ) {
    }

    public function supports(FunctionMetadata $metadata, ?string $group = null): bool
    {
        foreach ($this->getDecorators($group) as $decorator) {
            if ($decorator->supports($metadata, $group)) {
                return true;
            }
        }

        return false;
    }

    public function decorate(\Closure $function, FunctionMetadata $metadata, ?string $group = null): \Closure
    {
        foreach ($this->getDecorators($group) as $decorator) {
            if ($decorator->supports($metadata, $group)) {
                $function = $decorator->decorate($function, $metadata, $group);
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
