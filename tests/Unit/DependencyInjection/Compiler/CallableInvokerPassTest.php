<?php

declare(strict_types=1);

namespace OpenSolid\CallableInvoker\Tests\Unit\DependencyInjection\Compiler;

use OpenSolid\CallableInvoker\CallableServiceLocator;
use OpenSolid\CallableInvoker\Decorator\CallableDecoratorInterface;
use OpenSolid\CallableInvoker\DependecyInjection\Compiler\CallableInvokerPass;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolverInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class CallableInvokerPassTest extends TestCase
{
    // --- single-tag edge cases (decorators as the exercised side) ---

    #[Test]
    public function noTaggedServicesAtAllProducesEmptyLocators(): void
    {
        $container = $this->createContainer();

        $this->process($container);

        self::assertCount(0, $this->getDecoratorValues($container));
        self::assertCount(0, $this->getResolverValues($container));
    }

    #[Test]
    public function ungroupedServicesGoToDefaultGroup(): void
    {
        $container = $this->createContainer();
        $this->registerDecorator($container, 'decorator_a');
        $this->registerDecorator($container, 'decorator_b');

        $this->process($container);

        $values = $this->getDecoratorValues($container);

        self::assertCount(1, $values);
        self::assertArrayHasKey('__NONE__', $values);
        self::assertReferencesEqual(['decorator_a', 'decorator_b'], $values['__NONE__']);
    }

    #[Test]
    public function servicesWithExplicitGroupsAreGrouped(): void
    {
        $container = $this->createContainer();
        $this->registerDecorator($container, 'decorator_a', [['groups' => ['foo', 'bar']]]);
        $this->registerDecorator($container, 'decorator_b', [['groups' => ['bar']]]);

        $this->process($container);

        $values = $this->getDecoratorValues($container);

        self::assertCount(2, $values);
        self::assertArrayHasKey('foo', $values);
        self::assertArrayHasKey('bar', $values);
        self::assertArrayNotHasKey('__NONE__', $values);
        self::assertReferencesEqual(['decorator_a'], $values['foo']);
        self::assertReferencesEqual(['decorator_a', 'decorator_b'], $values['bar']);
    }

    #[Test]
    public function serviceWithExplicitGroupIsExcludedFromDefaultGroup(): void
    {
        $container = $this->createContainer();
        // decorator_a has one ungrouped tag and one grouped tag → treated as explicitly grouped
        $this->registerDecorator($container, 'decorator_a', [[], ['groups' => ['foo']]]);
        $this->registerDecorator($container, 'decorator_b');

        $this->process($container);

        $values = $this->getDecoratorValues($container);

        self::assertCount(2, $values);
        self::assertReferencesEqual(['decorator_a', 'decorator_b'], $values['foo']);
        self::assertReferencesEqual(['decorator_b'], $values['__NONE__']);
    }

    #[Test]
    public function serviceWithMultipleTagsMixedGroupsIsExcludedFromDefaultGroup(): void
    {
        $container = $this->createContainer();
        $this->registerDecorator($container, 'decorator_a', [
            [],
            ['groups' => ['foo']],
            ['groups' => ['bar']],
        ]);

        $this->process($container);

        $values = $this->getDecoratorValues($container);

        self::assertCount(2, $values);
        self::assertArrayNotHasKey('__NONE__', $values);
        self::assertReferencesEqual(['decorator_a'], $values['foo']);
        self::assertReferencesEqual(['decorator_a'], $values['bar']);
    }

    #[Test]
    public function servicesAreSortedByPriorityDescending(): void
    {
        $container = $this->createContainer();
        $this->registerDecorator($container, 'decorator_low', [['priority' => -100]]);
        $this->registerDecorator($container, 'decorator_high', [['priority' => 100]]);
        $this->registerDecorator($container, 'decorator_default');

        $this->process($container);

        $values = $this->getDecoratorValues($container);

        self::assertReferencesEqual(['decorator_high', 'decorator_default', 'decorator_low'], $values['__NONE__']);
    }

    #[Test]
    public function ungroupedServicesAreSortedByPriorityWithinExplicitGroups(): void
    {
        $container = $this->createContainer();
        $this->registerDecorator($container, 'grouped_high', [['groups' => ['foo'], 'priority' => 10]]);
        $this->registerDecorator($container, 'ungrouped_mid', [['priority' => 5]]);
        $this->registerDecorator($container, 'grouped_low', [['groups' => ['foo'], 'priority' => -10]]);

        $this->process($container);

        $values = $this->getDecoratorValues($container);

        self::assertReferencesEqual(['grouped_high', 'ungrouped_mid', 'grouped_low'], $values['foo']);
        self::assertReferencesEqual(['ungrouped_mid'], $values['__NONE__']);
    }

    #[Test]
    public function ungroupedServiceUsesMaxPriorityAcrossAllItsTagsForSorting(): void
    {
        $container = $this->createContainer();
        $this->registerDecorator($container, 'decorator_multi', [['priority' => 5], ['priority' => 20]]);
        $this->registerDecorator($container, 'decorator_single', [['priority' => 10]]);

        $this->process($container);

        $values = $this->getDecoratorValues($container);

        self::assertReferencesEqual(['decorator_multi', 'decorator_single'], $values['__NONE__']);
    }

    // --- cross-tag scenarios (the core of CallableInvokerPass) ---

    #[Test]
    public function ungroupedResolversAreSpreadIntoDecoratorOnlyGroup(): void
    {
        $container = $this->createContainer();
        $this->registerDecorator($container, 'decorator_a', [['groups' => ['api']]]);
        $this->registerResolver($container, 'resolver_ungrouped');

        $this->process($container);

        $decoratorValues = $this->getDecoratorValues($container);
        $resolverValues = $this->getResolverValues($container);

        self::assertReferencesEqual(['decorator_a'], $decoratorValues['api']);
        self::assertReferencesEqual(['resolver_ungrouped'], $resolverValues['api']);
        self::assertReferencesEqual(['resolver_ungrouped'], $resolverValues['__NONE__']);
    }

    #[Test]
    public function ungroupedDecoratorsAreSpreadIntoResolverOnlyGroup(): void
    {
        $container = $this->createContainer();
        $this->registerDecorator($container, 'decorator_ungrouped');
        $this->registerResolver($container, 'resolver_a', [['groups' => ['web']]]);

        $this->process($container);

        $decoratorValues = $this->getDecoratorValues($container);
        $resolverValues = $this->getResolverValues($container);

        self::assertReferencesEqual(['resolver_a'], $resolverValues['web']);
        self::assertReferencesEqual(['decorator_ungrouped'], $decoratorValues['web']);
        self::assertReferencesEqual(['decorator_ungrouped'], $decoratorValues['__NONE__']);
    }

    #[Test]
    public function ungroupedServicesFromBothTagsAreSpreadIntoUnifiedGroupUniverse(): void
    {
        $container = $this->createContainer();
        $this->registerDecorator($container, 'decorator_grouped', [['groups' => ['foo']]]);
        $this->registerDecorator($container, 'decorator_ungrouped');
        $this->registerResolver($container, 'resolver_grouped', [['groups' => ['bar']]]);
        $this->registerResolver($container, 'resolver_ungrouped');

        $this->process($container);

        $decoratorValues = $this->getDecoratorValues($container);
        $resolverValues = $this->getResolverValues($container);

        self::assertReferencesEqual(['decorator_grouped', 'decorator_ungrouped'], $decoratorValues['foo']);
        self::assertReferencesEqual(['decorator_ungrouped'], $decoratorValues['bar']);
        self::assertReferencesEqual(['decorator_ungrouped'], $decoratorValues['__NONE__']);

        self::assertReferencesEqual(['resolver_grouped', 'resolver_ungrouped'], $resolverValues['bar']);
        self::assertReferencesEqual(['resolver_ungrouped'], $resolverValues['foo']);
        self::assertReferencesEqual(['resolver_ungrouped'], $resolverValues['__NONE__']);
    }

    #[Test]
    public function sharedExplicitGroupContainsServicesFromOwnTypeOnly(): void
    {
        $container = $this->createContainer();
        $this->registerDecorator($container, 'decorator_a', [['groups' => ['shared']]]);
        $this->registerResolver($container, 'resolver_a', [['groups' => ['shared']]]);

        $this->process($container);

        $decoratorValues = $this->getDecoratorValues($container);
        $resolverValues = $this->getResolverValues($container);

        self::assertReferencesEqual(['decorator_a'], $decoratorValues['shared']);
        self::assertReferencesEqual(['resolver_a'], $resolverValues['shared']);
        self::assertArrayNotHasKey('__NONE__', $decoratorValues);
        self::assertArrayNotHasKey('__NONE__', $resolverValues);
    }

    #[Test]
    public function priorityIsRespectedWithinUnifiedGroups(): void
    {
        $container = $this->createContainer();
        $this->registerDecorator($container, 'decorator_a', [['groups' => ['api']]]);
        $this->registerResolver($container, 'resolver_high', [['priority' => 100]]);
        $this->registerResolver($container, 'resolver_low', [['priority' => -10]]);

        $this->process($container);

        $resolverValues = $this->getResolverValues($container);

        self::assertReferencesEqual(['resolver_high', 'resolver_low'], $resolverValues['api']);
        self::assertReferencesEqual(['resolver_high', 'resolver_low'], $resolverValues['__NONE__']);
    }

    #[Test]
    public function multipleGroupsAcrossBothTagsAllReceiveUngroupedServices(): void
    {
        $container = $this->createContainer();
        $this->registerDecorator($container, 'decorator_foo', [['groups' => ['foo']]]);
        $this->registerDecorator($container, 'decorator_bar', [['groups' => ['bar']]]);
        $this->registerDecorator($container, 'decorator_ungrouped');
        $this->registerResolver($container, 'resolver_baz', [['groups' => ['baz']]]);
        $this->registerResolver($container, 'resolver_ungrouped');

        $this->process($container);

        $decoratorValues = $this->getDecoratorValues($container);
        $resolverValues = $this->getResolverValues($container);

        self::assertReferencesEqual(['decorator_foo', 'decorator_ungrouped'], $decoratorValues['foo']);
        self::assertReferencesEqual(['decorator_bar', 'decorator_ungrouped'], $decoratorValues['bar']);
        self::assertReferencesEqual(['decorator_ungrouped'], $decoratorValues['baz']);
        self::assertReferencesEqual(['decorator_ungrouped'], $decoratorValues['__NONE__']);

        self::assertReferencesEqual(['resolver_ungrouped'], $resolverValues['foo']);
        self::assertReferencesEqual(['resolver_ungrouped'], $resolverValues['bar']);
        self::assertReferencesEqual(['resolver_baz', 'resolver_ungrouped'], $resolverValues['baz']);
        self::assertReferencesEqual(['resolver_ungrouped'], $resolverValues['__NONE__']);
    }

    private function createContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setDefinition('callable_invoker.decorator_groups', new Definition(CallableServiceLocator::class));
        $container->setDefinition('callable_invoker.value_resolver_groups', new Definition(CallableServiceLocator::class));

        return $container;
    }

    /**
     * @param list<array<string, mixed>> $tags
     */
    private function registerDecorator(ContainerBuilder $container, string $id, array $tags = [[]]): void
    {
        $definition = new Definition(CallableDecoratorInterface::class);
        foreach ($tags as $attributes) {
            $definition->addTag('callable_invoker.decorator', $attributes);
        }
        $container->setDefinition($id, $definition);
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

    private function process(ContainerBuilder $container): void
    {
        new CallableInvokerPass()->process($container);
    }

    /**
     * @return array<string, IteratorArgument>
     */
    private function getDecoratorValues(ContainerBuilder $container): array
    {
        return $this->getLocatorValues($container, 'callable_invoker.decorator_groups');
    }

    /**
     * @return array<string, IteratorArgument>
     */
    private function getResolverValues(ContainerBuilder $container): array
    {
        return $this->getLocatorValues($container, 'callable_invoker.value_resolver_groups');
    }

    /**
     * @return array<string, IteratorArgument>
     */
    private function getLocatorValues(ContainerBuilder $container, string $serviceId): array
    {
        $argument = $container->getDefinition($serviceId)->getArgument(0);
        self::assertInstanceOf(ServiceLocatorArgument::class, $argument);

        return $argument->getValues();
    }

    /**
     * @param list<string> $expectedIds
     */
    private static function assertReferencesEqual(array $expectedIds, IteratorArgument $argument): void
    {
        $actualIds = array_map(static fn (Reference $ref) => (string) $ref, $argument->getValues());

        self::assertSame($expectedIds, $actualIds);
    }
}
