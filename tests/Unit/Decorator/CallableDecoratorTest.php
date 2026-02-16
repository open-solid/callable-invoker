<?php

namespace OpenSolid\CallableInvoker\Tests\Unit\Decorator;

use OpenSolid\CallableInvoker\Decorator\CallableClosure;
use OpenSolid\CallableInvoker\Decorator\CallableDecorator;
use OpenSolid\CallableInvoker\Decorator\CallableDecoratorInterface;
use OpenSolid\CallableInvoker\InMemoryCallableServiceLocator;
use OpenSolid\CallableInvoker\Tests\Unit\TestHelper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CallableDecoratorTest extends TestCase
{
    use TestHelper;

    #[Test]
    public function decorateWithNoDecorators(): void
    {
        $callableDecorator = new CallableDecorator();
        $fn = static fn () => 'original';

        $result = $callableDecorator->decorate($fn, $this->createMetadata());

        self::assertSame($fn, $result);
    }

    #[Test]
    public function decorateAppliesAllSupportedDecorators(): void
    {
        $decorator1 = $this->createStub(CallableDecoratorInterface::class);
        $decorator1->method('supports')->willReturn(true);
        $decorator1->method('decorate')->willReturn('first');

        $decorator2 = $this->createStub(CallableDecoratorInterface::class);
        $decorator2->method('supports')->willReturn(true);
        $decorator2->method('decorate')->willReturn('second');

        $callableDecorator = new CallableDecorator(new InMemoryCallableServiceLocator(['__NONE__' => [$decorator1, $decorator2]]));

        $result = $callableDecorator->decorate(static fn () => 'original', $this->createMetadata());

        self::assertSame('second', $result());
    }

    #[Test]
    public function decorateSkipsUnsupportedDecorators(): void
    {
        $unsupported = $this->createStub(CallableDecoratorInterface::class);
        $unsupported->method('supports')->willReturn(false);

        $supported = $this->createStub(CallableDecoratorInterface::class);
        $supported->method('supports')->willReturn(true);
        $supported->method('decorate')->willReturn('decorated');

        $callableDecorator = new CallableDecorator(new InMemoryCallableServiceLocator(['__NONE__' => [$unsupported, $supported]]));

        $result = $callableDecorator->decorate(static fn () => 'original', $this->createMetadata());

        self::assertSame('decorated', $result());
    }

    #[Test]
    public function decorateSkipsAllUnsupportedDecorators(): void
    {
        $unsupported = $this->createStub(CallableDecoratorInterface::class);
        $unsupported->method('supports')->willReturn(false);

        $callableDecorator = new CallableDecorator(new InMemoryCallableServiceLocator(['__NONE__' => [$unsupported]]));
        $fn = static fn () => 'original';

        $result = $callableDecorator->decorate($fn, $this->createMetadata());

        self::assertSame('original', $result());
    }

    #[Test]
    public function decorateUsesGroupDecorators(): void
    {
        $decorator = $this->createStub(CallableDecoratorInterface::class);
        $decorator->method('supports')->willReturn(true);
        $decorator->method('decorate')->willReturn('from_group');

        $callableDecorator = new CallableDecorator(new InMemoryCallableServiceLocator(['my_group' => [$decorator]]));

        $result = $callableDecorator->decorate(static fn () => 'original', $this->createMetadata(groups: ['my_group']));

        self::assertSame('from_group', $result());
    }

    #[Test]
    public function decorateReturnsOriginalWhenGroupHasNoDecorators(): void
    {
        $decorator = $this->createStub(CallableDecoratorInterface::class);
        $decorator->method('supports')->willReturn(true);

        $callableDecorator = new CallableDecorator(new InMemoryCallableServiceLocator(['my_group' => [$decorator]]));
        $fn = static fn () => 'original';

        $result = $callableDecorator->decorate($fn, $this->createMetadata(groups: ['other_group']));

        self::assertSame($fn, $result);
    }

    #[Test]
    public function nullGroupFallsBackToNoneGroup(): void
    {
        $decorator = $this->createStub(CallableDecoratorInterface::class);
        $decorator->method('supports')->willReturn(true);
        $decorator->method('decorate')->willReturn('from_none');

        $callableDecorator = new CallableDecorator(new InMemoryCallableServiceLocator(['__NONE__' => [$decorator]]));

        $result = $callableDecorator->decorate(static fn () => 'original', $this->createMetadata());

        self::assertSame('from_none', $result());
    }

    #[Test]
    public function decorateAggregatesDecoratorsFromMultipleGroups(): void
    {
        $shared = $this->createStub(CallableDecoratorInterface::class);
        $shared->method('supports')->willReturn(true);
        $shared->method('decorate')->willReturnCallback(static fn (CallableClosure $invoker) => $invoker->call().'_shared');

        $decoratorA = $this->createStub(CallableDecoratorInterface::class);
        $decoratorA->method('supports')->willReturn(true);
        $decoratorA->method('decorate')->willReturnCallback(static fn (CallableClosure $invoker) => $invoker->call().'_A');

        $decoratorB = $this->createStub(CallableDecoratorInterface::class);
        $decoratorB->method('supports')->willReturn(true);
        $decoratorB->method('decorate')->willReturnCallback(static fn (CallableClosure $invoker) => $invoker->call().'_B');

        $callableDecorator = new CallableDecorator(new InMemoryCallableServiceLocator([
            'foo' => [$shared, $decoratorA],
            'bar' => [$shared, $decoratorB],
        ]));

        $result = $callableDecorator->decorate(static fn () => 'original', $this->createMetadata(groups: ['foo', 'bar']));

        // shared appears only once (dedup), then A from foo, then B from bar
        self::assertSame('original_shared_A_B', $result());
    }
}
