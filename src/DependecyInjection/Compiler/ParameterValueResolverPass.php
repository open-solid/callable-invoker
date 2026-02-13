<?php

declare(strict_types=1);

namespace OpenSolid\CallableInvoker\DependecyInjection\Compiler;

use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final readonly class ParameterValueResolverPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public function process(ContainerBuilder $container): void
    {
        $definition = $container->getDefinition('callable_invoker.value_resolver_chain');

        $valueResolvers = [];
        /** @var list<array<string, list<string>>> $tags */
        foreach ($container->findTaggedServiceIds('callable_invoker.value_resolver') as $id => $tags) {
            $hasExplicitGroup = false;
            foreach ($tags as $tag) {
                foreach ($tag['groups'] ?? [] as $group) {
                    $valueResolvers[$group][$id] = new Reference($id);
                    $hasExplicitGroup = true;
                }
            }
            if (!$hasExplicitGroup) {
                $valueResolvers['__NONE__'][$id] = new Reference($id);
            }
        }

        foreach ($valueResolvers as $group => $resolvers) {
            $valueResolvers[$group] = new IteratorArgument(array_values($resolvers));
        }

        $definition->setArgument(0, new ServiceLocatorArgument($valueResolvers));
    }
}
