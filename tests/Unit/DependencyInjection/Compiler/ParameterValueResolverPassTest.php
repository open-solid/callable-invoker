<?php

namespace OpenSolid\CallableInvoker\Tests\Unit\DependencyInjection\Compiler;

use OpenSolid\CallableInvoker\DependecyInjection\Compiler\ParameterValueResolverPass;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolverChain;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolverInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class ParameterValueResolverPassTest extends TestCase
{
    #[Test]
    public function resolversWithoutGroupsGoToNoneGroup(): void
    {
        $container = $this->createContainerWithChainDefinition();
        $this->registerResolver($container, 'resolver_a');
        $this->registerResolver($container, 'resolver_b');

        new ParameterValueResolverPass()->process($container);

        $locator = $this->getServiceLocatorArgument($container);
        $values = $locator->getValues();

        self::assertCount(1, $values);
        self::assertArrayHasKey('__NONE__', $values);
        self::assertReferencesEqual(['resolver_a', 'resolver_b'], $values['__NONE__']);
    }

    #[Test]
    public function resolversWithExplicitGroupsAreGrouped(): void
    {
        $container = $this->createContainerWithChainDefinition();
        $this->registerResolver($container, 'resolver_a', [['groups' => ['foo', 'bar']]]);
        $this->registerResolver($container, 'resolver_b', [['groups' => ['bar']]]);

        new ParameterValueResolverPass()->process($container);

        $locator = $this->getServiceLocatorArgument($container);
        $values = $locator->getValues();

        self::assertCount(2, $values);
        self::assertArrayHasKey('foo', $values);
        self::assertArrayHasKey('bar', $values);
        self::assertArrayNotHasKey('__NONE__', $values);
        self::assertReferencesEqual(['resolver_a'], $values['foo']);
        self::assertReferencesEqual(['resolver_a', 'resolver_b'], $values['bar']);
    }

    #[Test]
    public function resolverWithExplicitGroupIsRemovedFromNoneGroup(): void
    {
        $container = $this->createContainerWithChainDefinition();
        $this->registerResolver($container, 'resolver_a', [[], ['groups' => ['foo']]]);
        $this->registerResolver($container, 'resolver_b');

        new ParameterValueResolverPass()->process($container);

        $locator = $this->getServiceLocatorArgument($container);
        $values = $locator->getValues();

        self::assertCount(2, $values);
        self::assertReferencesEqual(['resolver_a'], $values['foo']);
        self::assertReferencesEqual(['resolver_b'], $values['__NONE__']);
    }

    #[Test]
    public function noTaggedServicesProducesEmptyLocator(): void
    {
        $container = $this->createContainerWithChainDefinition();

        new ParameterValueResolverPass()->process($container);

        $locator = $this->getServiceLocatorArgument($container);

        self::assertCount(0, $locator->getValues());
    }

    #[Test]
    public function resolverWithMultipleTagsAndMixedGroups(): void
    {
        $container = $this->createContainerWithChainDefinition();
        $this->registerResolver($container, 'resolver_a', [
            [],
            ['groups' => ['foo']],
            ['groups' => ['bar']],
        ]);

        new ParameterValueResolverPass()->process($container);

        $locator = $this->getServiceLocatorArgument($container);
        $values = $locator->getValues();

        self::assertCount(2, $values);
        self::assertArrayNotHasKey('__NONE__', $values);
        self::assertReferencesEqual(['resolver_a'], $values['foo']);
        self::assertReferencesEqual(['resolver_a'], $values['bar']);
    }

    #[Test]
    public function resolverWithOneTagWithGroupAndOneWithout(): void
    {
        $container = $this->createContainerWithChainDefinition();
        $this->registerResolver($container, 'resolver_a', [
            ['groups' => ['foo']],
            [],
        ]);

        new ParameterValueResolverPass()->process($container);

        $locator = $this->getServiceLocatorArgument($container);
        $values = $locator->getValues();

        self::assertCount(1, $values);
        self::assertArrayHasKey('foo', $values);
        self::assertArrayNotHasKey('__NONE__', $values);
    }

    private function createContainerWithChainDefinition(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setDefinition('callable_invoker.value_resolver_chain', new Definition(ParameterValueResolverChain::class));

        return $container;
    }

    /**
     * @param list<array<string, mixed>> $tags
     */
    private function registerResolver(ContainerBuilder $container, string $id, array $tags = [[]]): void
    {
        $definition = new Definition(ParameterValueResolverInterface::class);
        foreach ($tags as $attributes) {
            $definition->addTag('callable_invoker.value_resolver', $attributes);
        }
        $container->setDefinition($id, $definition);
    }

    private function getServiceLocatorArgument(ContainerBuilder $container): ServiceLocatorArgument
    {
        $definition = $container->getDefinition('callable_invoker.value_resolver_chain');
        $argument = $definition->getArgument(0);
        self::assertInstanceOf(ServiceLocatorArgument::class, $argument);

        return $argument;
    }

    /**
     * @param list<string> $expectedIds
     */
    private static function assertReferencesEqual(array $expectedIds, IteratorArgument $argument): void
    {
        $actualIds = array_map(fn (Reference $ref) => (string) $ref, $argument->getValues());

        self::assertSame($expectedIds, $actualIds);
    }
}
