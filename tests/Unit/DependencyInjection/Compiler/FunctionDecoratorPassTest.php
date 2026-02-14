<?php

namespace OpenSolid\CallableInvoker\Tests\Unit\DependencyInjection\Compiler;

use OpenSolid\CallableInvoker\Decorator\FunctionDecoratorChain;
use OpenSolid\CallableInvoker\Decorator\FunctionDecoratorInterface;
use OpenSolid\CallableInvoker\DependecyInjection\Compiler\FunctionDecoratorPass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class FunctionDecoratorPassTest extends TestCase
{
    #[Test]
    public function decoratorsWithoutGroupsGoToNoneGroup(): void
    {
        $container = $this->createContainerWithChainDefinition();
        $this->registerDecorator($container, 'decorator_a');
        $this->registerDecorator($container, 'decorator_b');

        new FunctionDecoratorPass()->process($container);

        $locator = $this->getServiceLocatorArgument($container);
        $values = $locator->getValues();

        self::assertCount(1, $values);
        self::assertArrayHasKey('__NONE__', $values);
        self::assertReferencesEqual(['decorator_a', 'decorator_b'], $values['__NONE__']);
    }

    #[Test]
    public function decoratorsWithExplicitGroupsAreGrouped(): void
    {
        $container = $this->createContainerWithChainDefinition();
        $this->registerDecorator($container, 'decorator_a', [['groups' => ['foo', 'bar']]]);
        $this->registerDecorator($container, 'decorator_b', [['groups' => ['bar']]]);

        new FunctionDecoratorPass()->process($container);

        $locator = $this->getServiceLocatorArgument($container);
        $values = $locator->getValues();

        self::assertCount(2, $values);
        self::assertArrayHasKey('foo', $values);
        self::assertArrayHasKey('bar', $values);
        self::assertArrayNotHasKey('__NONE__', $values);
        self::assertReferencesEqual(['decorator_a'], $values['foo']);
        self::assertReferencesEqual(['decorator_a', 'decorator_b'], $values['bar']);
    }

    #[Test]
    public function decoratorWithExplicitGroupIsRemovedFromNoneGroup(): void
    {
        $container = $this->createContainerWithChainDefinition();
        $this->registerDecorator($container, 'decorator_a', [[], ['groups' => ['foo']]]);
        $this->registerDecorator($container, 'decorator_b');

        new FunctionDecoratorPass()->process($container);

        $locator = $this->getServiceLocatorArgument($container);
        $values = $locator->getValues();

        self::assertCount(2, $values);
        self::assertReferencesEqual(['decorator_a'], $values['foo']);
        self::assertReferencesEqual(['decorator_b'], $values['__NONE__']);
    }

    #[Test]
    public function noTaggedServicesProducesEmptyLocator(): void
    {
        $container = $this->createContainerWithChainDefinition();

        new FunctionDecoratorPass()->process($container);

        $locator = $this->getServiceLocatorArgument($container);

        self::assertCount(0, $locator->getValues());
    }

    #[Test]
    public function decoratorWithMultipleTagsAndMixedGroups(): void
    {
        $container = $this->createContainerWithChainDefinition();
        $this->registerDecorator($container, 'decorator_a', [
            [],
            ['groups' => ['foo']],
            ['groups' => ['bar']],
        ]);

        new FunctionDecoratorPass()->process($container);

        $locator = $this->getServiceLocatorArgument($container);
        $values = $locator->getValues();

        self::assertCount(2, $values);
        self::assertArrayNotHasKey('__NONE__', $values);
        self::assertReferencesEqual(['decorator_a'], $values['foo']);
        self::assertReferencesEqual(['decorator_a'], $values['bar']);
    }

    #[Test]
    public function decoratorWithOneTagWithGroupAndOneWithout(): void
    {
        $container = $this->createContainerWithChainDefinition();
        $this->registerDecorator($container, 'decorator_a', [
            ['groups' => ['foo']],
            [],
        ]);

        new FunctionDecoratorPass()->process($container);

        $locator = $this->getServiceLocatorArgument($container);
        $values = $locator->getValues();

        self::assertCount(1, $values);
        self::assertArrayHasKey('foo', $values);
        self::assertArrayNotHasKey('__NONE__', $values);
    }

    #[Test]
    public function decoratorsAreSortedByPriorityDescending(): void
    {
        $container = $this->createContainerWithChainDefinition();
        $this->registerDecorator($container, 'decorator_low', [['priority' => -100]]);
        $this->registerDecorator($container, 'decorator_high', [['priority' => 100]]);
        $this->registerDecorator($container, 'decorator_default');

        new FunctionDecoratorPass()->process($container);

        $locator = $this->getServiceLocatorArgument($container);
        $values = $locator->getValues();

        self::assertReferencesEqual(['decorator_high', 'decorator_default', 'decorator_low'], $values['__NONE__']);
    }

    #[Test]
    public function decoratorsAreSortedByPriorityWithinGroups(): void
    {
        $container = $this->createContainerWithChainDefinition();
        $this->registerDecorator($container, 'decorator_low', [['groups' => ['foo'], 'priority' => -10]]);
        $this->registerDecorator($container, 'decorator_high', [['groups' => ['foo'], 'priority' => 10]]);

        new FunctionDecoratorPass()->process($container);

        $locator = $this->getServiceLocatorArgument($container);
        $values = $locator->getValues();

        self::assertReferencesEqual(['decorator_high', 'decorator_low'], $values['foo']);
    }

    #[Test]
    public function decoratorWithoutPriorityDefaultsToZero(): void
    {
        $container = $this->createContainerWithChainDefinition();
        $this->registerDecorator($container, 'decorator_positive', [['priority' => 1]]);
        $this->registerDecorator($container, 'decorator_default');
        $this->registerDecorator($container, 'decorator_negative', [['priority' => -1]]);

        new FunctionDecoratorPass()->process($container);

        $locator = $this->getServiceLocatorArgument($container);
        $values = $locator->getValues();

        self::assertReferencesEqual(['decorator_positive', 'decorator_default', 'decorator_negative'], $values['__NONE__']);
    }

    #[Test]
    public function decoratorInNoneGroupUsesMaxPriorityFromAllTags(): void
    {
        $container = $this->createContainerWithChainDefinition();
        $this->registerDecorator($container, 'decorator_multi', [['priority' => 5], ['priority' => 20]]);
        $this->registerDecorator($container, 'decorator_single', [['priority' => 10]]);

        new FunctionDecoratorPass()->process($container);

        $locator = $this->getServiceLocatorArgument($container);
        $values = $locator->getValues();

        self::assertReferencesEqual(['decorator_multi', 'decorator_single'], $values['__NONE__']);
    }

    private function createContainerWithChainDefinition(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setDefinition('callable_invoker.decorator_chain', new Definition(FunctionDecoratorChain::class));

        return $container;
    }

    /**
     * @param list<array<string, mixed>> $tags
     */
    private function registerDecorator(ContainerBuilder $container, string $id, array $tags = [[]]): void
    {
        $definition = new Definition(FunctionDecoratorInterface::class);
        foreach ($tags as $attributes) {
            $definition->addTag('callable_invoker.decorator', $attributes);
        }
        $container->setDefinition($id, $definition);
    }

    private function getServiceLocatorArgument(ContainerBuilder $container): ServiceLocatorArgument
    {
        $definition = $container->getDefinition('callable_invoker.decorator_chain');
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
