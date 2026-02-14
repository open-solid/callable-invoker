<?php

declare(strict_types=1);

namespace OpenSolid\CallableInvoker\DependecyInjection\Compiler;

use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final readonly class FunctionDecoratorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $definition = $container->getDefinition('callable_invoker.decorator_chain');

        $decorators = [];
        /** @var list<array<string, list<string>>> $tags */
        foreach ($container->findTaggedServiceIds('callable_invoker.decorator') as $id => $tags) {
            if ('callable_invoker.decorator_chain' === $id) {
                continue;
            }

            $hasExplicitGroup = false;
            foreach ($tags as $tag) {
                foreach ($tag['groups'] ?? [] as $group) {
                    $decorators[$group][$id] = new Reference($id);
                    $hasExplicitGroup = true;
                }
            }
            if (!$hasExplicitGroup) {
                $decorators['__NONE__'][$id] = new Reference($id);
            }
        }

        foreach ($decorators as $group => $refs) {
            $decorators[$group] = new IteratorArgument(array_values($refs));
        }

        $definition->setArgument(0, new ServiceLocatorArgument($decorators));
    }
}
