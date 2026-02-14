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

        /** @var array<string, array<string, array{ref: Reference, priority: int}>> $grouped */
        $grouped = [];
        foreach ($container->findTaggedServiceIds('callable_invoker.decorator') as $id => $tags) {
            if ('callable_invoker.decorator_chain' === $id) {
                continue;
            }

            $hasExplicitGroup = false;
            $maxPriority = null;
            /** @var array{priority?: int, groups?: list<string>} $tag */
            foreach ($tags as $tag) {
                $priority = $tag['priority'] ?? 0;
                $maxPriority = max($maxPriority ?? $priority, $priority);
                foreach ($tag['groups'] ?? [] as $group) {
                    $grouped[$group][$id] = ['ref' => new Reference($id), 'priority' => $priority];
                    $hasExplicitGroup = true;
                }
            }
            if (!$hasExplicitGroup) {
                $grouped['__NONE__'][$id] = ['ref' => new Reference($id), 'priority' => $maxPriority];
            }
        }

        $decorators = [];
        foreach ($grouped as $group => $entries) {
            uasort($entries, static fn (array $a, array $b) => $b['priority'] <=> $a['priority']);
            $decorators[$group] = new IteratorArgument(array_map(static fn (array $entry) => $entry['ref'], array_values($entries)));
        }

        $definition->setArgument(0, new ServiceLocatorArgument($decorators));
    }
}
