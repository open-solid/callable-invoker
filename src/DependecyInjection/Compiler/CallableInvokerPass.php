<?php

declare(strict_types=1);

namespace OpenSolid\CallableInvoker\DependecyInjection\Compiler;

use OpenSolid\CallableInvoker\CallableInvokerInterface;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final readonly class CallableInvokerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        [$decoratorGrouped, $decoratorUngrouped] = $this->collectTaggedServices($container, 'callable_invoker.decorator');
        [$resolverGrouped, $resolverUngrouped] = $this->collectTaggedServices($container, 'callable_invoker.value_resolver');

        // Unified group universe from both tags
        $allExplicitGroups = array_keys($decoratorGrouped + $resolverGrouped);

        $container->getDefinition('callable_invoker.decorator_groups')
            ->setArgument(0, new ServiceLocatorArgument($this->buildGroups($decoratorGrouped, $decoratorUngrouped, $allExplicitGroups)));
        $container->getDefinition('callable_invoker.value_resolver_groups')
            ->setArgument(0, new ServiceLocatorArgument($this->buildGroups($resolverGrouped, $resolverUngrouped, $allExplicitGroups)));
    }

    /**
     * @return array{
     *     array<string, array<string, array{ref: Reference, priority: int}>>,
     *     array<string, array{ref: Reference, priority: int}>
     * }
     */
    private function collectTaggedServices(ContainerBuilder $container, string $tagName): array
    {
        $grouped = [];
        $ungrouped = [];

        foreach ($container->findTaggedServiceIds($tagName) as $id => $tags) {
            $ref = new Reference($id);
            $hasExplicitGroup = false;
            /** @var int|null $maxPriority */
            $maxPriority = null;

            /** @var array{priority?: int, groups?: list<string>} $tag */
            foreach ($tags as $tag) {
                $priority = $tag['priority'] ?? 0;
                $maxPriority = null === $maxPriority ? $priority : max($maxPriority, $priority);
                foreach ($tag['groups'] ?? [] as $group) {
                    $grouped[$group][$id] = ['ref' => $ref, 'priority' => $priority];
                    $hasExplicitGroup = true;
                }
            }

            if (!$hasExplicitGroup) {
                $ungrouped[$id] = ['ref' => $ref, 'priority' => $maxPriority ?? 0];
            }
        }

        return [$grouped, $ungrouped];
    }

    /**
     * @param array<string, array<string, array{ref: Reference, priority: int}>> $grouped
     * @param array<string, array{ref: Reference, priority: int}>                $ungrouped
     * @param list<string>                                                       $allExplicitGroups
     *
     * @return array<string, IteratorArgument>
     */
    private function buildGroups(array $grouped, array $ungrouped, array $allExplicitGroups): array
    {
        if ($ungrouped) {
            $defaultGroup = CallableInvokerInterface::DEFAULT_GROUP;
            $grouped[$defaultGroup] = ($grouped[$defaultGroup] ?? []) + $ungrouped;
            foreach ($allExplicitGroups as $group) {
                if ($defaultGroup !== $group) {
                    $grouped[$group] = ($grouped[$group] ?? []) + $ungrouped;
                }
            }
        }

        $result = [];
        foreach ($grouped as $group => $entries) {
            uasort($entries, static fn (array $a, array $b) => $b['priority'] <=> $a['priority']);
            $result[$group] = new IteratorArgument(array_column($entries, 'ref'));
        }

        return $result;
    }
}
