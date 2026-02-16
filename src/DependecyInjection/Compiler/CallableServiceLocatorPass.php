<?php

declare(strict_types=1);

namespace OpenSolid\CallableInvoker\DependecyInjection\Compiler;

use OpenSolid\CallableInvoker\CallableInvokerInterface;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final readonly class CallableServiceLocatorPass implements CompilerPassInterface
{
    public function __construct(
        private string $serviceId,
        private string $tagName,
    ) {
    }

    public function process(ContainerBuilder $container): void
    {
        $groups = [];
        foreach ($this->resolveGroups($container) as $group => $entries) {
            uasort($entries, static fn (array $a, array $b) => $b['priority'] <=> $a['priority']);
            $groups[$group] = new IteratorArgument(array_column($entries, 'ref'));
        }

        $container->getDefinition($this->serviceId)
            ->setArgument(0, new ServiceLocatorArgument($groups));
    }

    /**
     * @return array<string, array<string, array{ref: Reference, priority: int}>>
     */
    private function resolveGroups(ContainerBuilder $container): array
    {
        $grouped = [];
        $ungrouped = [];

        foreach ($container->findTaggedServiceIds($this->tagName) as $id => $tags) {
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
                \assert(null !== $maxPriority);
                $ungrouped[$id] = ['ref' => $ref, 'priority' => $maxPriority];
            }
        }

        if ($ungrouped) {
            $defaultGroup = CallableInvokerInterface::DEFAULT_GROUP;
            $grouped[$defaultGroup] = ($grouped[$defaultGroup] ?? []) + $ungrouped;
            foreach ($grouped as $group => &$entries) {
                if ($defaultGroup !== $group) {
                    $entries += $ungrouped;
                }
            }
            unset($entries);
        }

        return $grouped;
    }
}
