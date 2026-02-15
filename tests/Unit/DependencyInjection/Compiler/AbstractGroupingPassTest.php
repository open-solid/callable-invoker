<?php

namespace OpenSolid\CallableInvoker\Tests\Unit\DependencyInjection\Compiler;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

abstract class AbstractGroupingPassTest extends TestCase
{
    abstract protected function createPass(): CompilerPassInterface;

    abstract protected function getChainServiceId(): string;

    abstract protected function getChainClass(): string;

    abstract protected function getTaggedClass(): string;

    abstract protected function getTagName(): string;

    #[Test]
    public function servicesWithoutGroupsGoToNoneGroup(): void
    {
        $container = $this->createContainer();
        $this->registerTaggedService($container, 'service_a');
        $this->registerTaggedService($container, 'service_b');

        $this->createPass()->process($container);

        $values = $this->getLocatorValues($container);

        self::assertCount(1, $values);
        self::assertArrayHasKey('__NONE__', $values);
        self::assertReferencesEqual(['service_a', 'service_b'], $values['__NONE__']);
    }

    #[Test]
    public function servicesWithExplicitGroupsAreGrouped(): void
    {
        $container = $this->createContainer();
        $this->registerTaggedService($container, 'service_a', [['groups' => ['foo', 'bar']]]);
        $this->registerTaggedService($container, 'service_b', [['groups' => ['bar']]]);

        $this->createPass()->process($container);

        $values = $this->getLocatorValues($container);

        self::assertCount(2, $values);
        self::assertArrayHasKey('foo', $values);
        self::assertArrayHasKey('bar', $values);
        self::assertArrayNotHasKey('__NONE__', $values);
        self::assertReferencesEqual(['service_a'], $values['foo']);
        self::assertReferencesEqual(['service_a', 'service_b'], $values['bar']);
    }

    #[Test]
    public function serviceWithExplicitGroupIsRemovedFromNoneGroup(): void
    {
        $container = $this->createContainer();
        $this->registerTaggedService($container, 'service_a', [[], ['groups' => ['foo']]]);
        $this->registerTaggedService($container, 'service_b');

        $this->createPass()->process($container);

        $values = $this->getLocatorValues($container);

        self::assertCount(2, $values);
        self::assertReferencesEqual(['service_a', 'service_b'], $values['foo']);
        self::assertReferencesEqual(['service_b'], $values['__NONE__']);
    }

    #[Test]
    public function noTaggedServicesProducesEmptyLocator(): void
    {
        $container = $this->createContainer();

        $this->createPass()->process($container);

        $values = $this->getLocatorValues($container);

        self::assertCount(0, $values);
    }

    #[Test]
    public function serviceWithMultipleTagsAndMixedGroups(): void
    {
        $container = $this->createContainer();
        $this->registerTaggedService($container, 'service_a', [
            [],
            ['groups' => ['foo']],
            ['groups' => ['bar']],
        ]);

        $this->createPass()->process($container);

        $values = $this->getLocatorValues($container);

        self::assertCount(2, $values);
        self::assertArrayNotHasKey('__NONE__', $values);
        self::assertReferencesEqual(['service_a'], $values['foo']);
        self::assertReferencesEqual(['service_a'], $values['bar']);
    }

    #[Test]
    public function serviceWithOneTagWithGroupAndOneWithout(): void
    {
        $container = $this->createContainer();
        $this->registerTaggedService($container, 'service_a', [
            ['groups' => ['foo']],
            [],
        ]);

        $this->createPass()->process($container);

        $values = $this->getLocatorValues($container);

        self::assertCount(1, $values);
        self::assertArrayHasKey('foo', $values);
        self::assertArrayNotHasKey('__NONE__', $values);
    }

    #[Test]
    public function servicesAreSortedByPriorityDescending(): void
    {
        $container = $this->createContainer();
        $this->registerTaggedService($container, 'service_low', [['priority' => -100]]);
        $this->registerTaggedService($container, 'service_high', [['priority' => 100]]);
        $this->registerTaggedService($container, 'service_default');

        $this->createPass()->process($container);

        $values = $this->getLocatorValues($container);

        self::assertReferencesEqual(['service_high', 'service_default', 'service_low'], $values['__NONE__']);
    }

    #[Test]
    public function servicesAreSortedByPriorityWithinGroups(): void
    {
        $container = $this->createContainer();
        $this->registerTaggedService($container, 'service_low', [['groups' => ['foo'], 'priority' => -10]]);
        $this->registerTaggedService($container, 'service_high', [['groups' => ['foo'], 'priority' => 10]]);

        $this->createPass()->process($container);

        $values = $this->getLocatorValues($container);

        self::assertReferencesEqual(['service_high', 'service_low'], $values['foo']);
    }

    #[Test]
    public function serviceWithoutPriorityDefaultsToZero(): void
    {
        $container = $this->createContainer();
        $this->registerTaggedService($container, 'service_positive', [['priority' => 1]]);
        $this->registerTaggedService($container, 'service_default');
        $this->registerTaggedService($container, 'service_negative', [['priority' => -1]]);

        $this->createPass()->process($container);

        $values = $this->getLocatorValues($container);

        self::assertReferencesEqual(['service_positive', 'service_default', 'service_negative'], $values['__NONE__']);
    }

    #[Test]
    public function ungroupedServicesAreIncludedInAllExplicitGroups(): void
    {
        $container = $this->createContainer();
        $this->registerTaggedService($container, 'grouped_a', [['groups' => ['foo']]]);
        $this->registerTaggedService($container, 'grouped_b', [['groups' => ['bar']]]);
        $this->registerTaggedService($container, 'ungrouped');

        $this->createPass()->process($container);

        $values = $this->getLocatorValues($container);

        self::assertCount(3, $values);
        self::assertReferencesEqual(['grouped_a', 'ungrouped'], $values['foo']);
        self::assertReferencesEqual(['grouped_b', 'ungrouped'], $values['bar']);
        self::assertReferencesEqual(['ungrouped'], $values['__NONE__']);
    }

    #[Test]
    public function ungroupedServicesAreSortedByPriorityWithinExplicitGroups(): void
    {
        $container = $this->createContainer();
        $this->registerTaggedService($container, 'grouped_high', [['groups' => ['foo'], 'priority' => 10]]);
        $this->registerTaggedService($container, 'ungrouped_mid', [['priority' => 5]]);
        $this->registerTaggedService($container, 'grouped_low', [['groups' => ['foo'], 'priority' => -10]]);

        $this->createPass()->process($container);

        $values = $this->getLocatorValues($container);

        self::assertReferencesEqual(['grouped_high', 'ungrouped_mid', 'grouped_low'], $values['foo']);
        self::assertReferencesEqual(['ungrouped_mid'], $values['__NONE__']);
    }

    #[Test]
    public function serviceInNoneGroupUsesMaxPriorityFromAllTags(): void
    {
        $container = $this->createContainer();
        $this->registerTaggedService($container, 'service_multi', [['priority' => 5], ['priority' => 20]]);
        $this->registerTaggedService($container, 'service_single', [['priority' => 10]]);

        $this->createPass()->process($container);

        $values = $this->getLocatorValues($container);

        self::assertReferencesEqual(['service_multi', 'service_single'], $values['__NONE__']);
    }

    private function createContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setDefinition($this->getChainServiceId(), new Definition($this->getChainClass()));

        return $container;
    }

    /**
     * @param list<array<string, mixed>> $tags
     */
    private function registerTaggedService(ContainerBuilder $container, string $id, array $tags = [[]]): void
    {
        $definition = new Definition($this->getTaggedClass());
        foreach ($tags as $attributes) {
            $definition->addTag($this->getTagName(), $attributes);
        }
        $container->setDefinition($id, $definition);
    }

    /**
     * @return array<string, IteratorArgument>
     */
    private function getLocatorValues(ContainerBuilder $container): array
    {
        $argument = $container->getDefinition($this->getChainServiceId())->getArgument(0);
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
