<?php

namespace OpenSolid\CallableInvoker\Tests\Unit;

use OpenSolid\CallableInvoker\CallableServiceLocator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class CallableServiceLocatorTest extends TestCase
{
    #[Test]
    public function getReturnsEmptyWhenContainerHasNoGroups(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturn(false);

        $locator = new CallableServiceLocator($container);

        self::assertSame([], iterator_to_array($locator->get(['any'])));
    }

    #[Test]
    public function getReturnsServicesFromSingleGroup(): void
    {
        $a = new \stdClass();
        $b = new \stdClass();

        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturnMap([['foo', true]]);
        $container->method('get')->willReturnMap([['foo', [$a, $b]]]);

        $locator = new CallableServiceLocator($container);

        self::assertSame([$a, $b], iterator_to_array($locator->get(['foo'])));
    }

    #[Test]
    public function getSkipsUnknownGroups(): void
    {
        $a = new \stdClass();

        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturnCallback(static fn (string $id) => 'foo' === $id);
        $container->method('get')->willReturnMap([['foo', [$a]]]);

        $locator = new CallableServiceLocator($container);

        self::assertSame([$a], iterator_to_array($locator->get(['missing', 'foo'])));
    }

    #[Test]
    public function getAggregatesServicesFromMultipleGroups(): void
    {
        $a = new \stdClass();
        $b = new \stdClass();

        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturnCallback(static fn (string $id) => match ($id) {
            'foo' => [$a],
            'bar' => [$b],
        });

        $locator = new CallableServiceLocator($container);

        self::assertSame([$a, $b], iterator_to_array($locator->get(['foo', 'bar'])));
    }

    #[Test]
    public function getDeduplicatesSharedServicesAcrossGroups(): void
    {
        $shared = new \stdClass();
        $unique = new \stdClass();

        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturnCallback(static fn (string $id) => match ($id) {
            'foo' => [$shared],
            'bar' => [$shared, $unique],
        });

        $locator = new CallableServiceLocator($container);

        self::assertSame([$shared, $unique], iterator_to_array($locator->get(['foo', 'bar'])));
    }

    #[Test]
    public function getReturnsEmptyForEmptyGroupsList(): void
    {
        $container = $this->createStub(ContainerInterface::class);

        $locator = new CallableServiceLocator($container);

        self::assertSame([], iterator_to_array($locator->get([])));
    }
}
