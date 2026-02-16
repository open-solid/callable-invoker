<?php

namespace OpenSolid\CallableInvoker\Tests\Unit;

use OpenSolid\CallableInvoker\InMemoryCallableServiceLocator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InMemoryCallableServiceLocatorTest extends TestCase
{
    #[Test]
    public function getReturnsEmptyWhenNoGroups(): void
    {
        $locator = new InMemoryCallableServiceLocator();

        self::assertSame([], iterator_to_array($locator->get(['any'])));
    }

    #[Test]
    public function getReturnsServicesFromSingleGroup(): void
    {
        $a = new \stdClass();
        $b = new \stdClass();
        $locator = new InMemoryCallableServiceLocator(['foo' => [$a, $b]]);

        self::assertSame([$a, $b], iterator_to_array($locator->get(['foo'])));
    }

    #[Test]
    public function getSkipsUnknownGroups(): void
    {
        $a = new \stdClass();
        $locator = new InMemoryCallableServiceLocator(['foo' => [$a]]);

        self::assertSame([$a], iterator_to_array($locator->get(['missing', 'foo'])));
    }

    #[Test]
    public function getAggregatesServicesFromMultipleGroups(): void
    {
        $a = new \stdClass();
        $b = new \stdClass();
        $locator = new InMemoryCallableServiceLocator([
            'foo' => [$a],
            'bar' => [$b],
        ]);

        self::assertSame([$a, $b], iterator_to_array($locator->get(['foo', 'bar'])));
    }

    #[Test]
    public function getDeduplicatesSharedServicesAcrossGroups(): void
    {
        $shared = new \stdClass();
        $unique = new \stdClass();
        $locator = new InMemoryCallableServiceLocator([
            'foo' => [$shared],
            'bar' => [$shared, $unique],
        ]);

        self::assertSame([$shared, $unique], iterator_to_array($locator->get(['foo', 'bar'])));
    }

    #[Test]
    public function getPreservesOrderFirstSeenWins(): void
    {
        $a = new \stdClass();
        $b = new \stdClass();
        $c = new \stdClass();
        $locator = new InMemoryCallableServiceLocator([
            'foo' => [$a, $b],
            'bar' => [$b, $c],
        ]);

        $result = iterator_to_array($locator->get(['foo', 'bar']));

        // a from foo, b from foo (deduped from bar), c from bar
        self::assertSame([$a, $b, $c], $result);
    }

    #[Test]
    public function getReturnsEmptyForEmptyGroupsList(): void
    {
        $locator = new InMemoryCallableServiceLocator(['foo' => [new \stdClass()]]);

        self::assertSame([], iterator_to_array($locator->get([])));
    }
}
