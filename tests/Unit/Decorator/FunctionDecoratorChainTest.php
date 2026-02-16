<?php

namespace OpenSolid\CallableInvoker\Tests\Unit\Decorator;

use OpenSolid\CallableInvoker\Decorator\FunctionDecoratorChain;
use OpenSolid\CallableInvoker\Decorator\FunctionDecoratorInterface;
use OpenSolid\CallableInvoker\InMemoryCallableServiceLocator;
use OpenSolid\CallableInvoker\Tests\Unit\TestHelper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FunctionDecoratorChainTest extends TestCase
{
    use TestHelper;

    #[Test]
    public function doesNotSupportWhenEmpty(): void
    {
        $chain = new FunctionDecoratorChain();

        self::assertFalse($chain->supports($this->createMetadata()));
    }

    #[Test]
    public function supportsWhenAnyDecoratorSupports(): void
    {
        $unsupported = $this->createStub(FunctionDecoratorInterface::class);
        $unsupported->method('supports')->willReturn(false);

        $supported = $this->createStub(FunctionDecoratorInterface::class);
        $supported->method('supports')->willReturn(true);

        $chain = new FunctionDecoratorChain(new InMemoryCallableServiceLocator(['__NONE__' => [$unsupported, $supported]]));

        self::assertTrue($chain->supports($this->createMetadata()));
    }

    #[Test]
    public function doesNotSupportWhenNoDecoratorSupports(): void
    {
        $unsupported = $this->createStub(FunctionDecoratorInterface::class);
        $unsupported->method('supports')->willReturn(false);

        $chain = new FunctionDecoratorChain(new InMemoryCallableServiceLocator(['__NONE__' => [$unsupported]]));

        self::assertFalse($chain->supports($this->createMetadata()));
    }

    #[Test]
    public function decorateWithNoDecorators(): void
    {
        $chain = new FunctionDecoratorChain();
        $fn = static fn () => 'original';

        $result = $chain->decorate($fn, $this->createMetadata());

        self::assertSame($fn, $result);
    }

    #[Test]
    public function decorateAppliesAllSupportedDecorators(): void
    {
        $decorator1 = $this->createStub(FunctionDecoratorInterface::class);
        $decorator1->method('supports')->willReturn(true);
        $decorator1->method('decorate')->willReturn(static fn () => 'first');

        $decorator2 = $this->createStub(FunctionDecoratorInterface::class);
        $decorator2->method('supports')->willReturn(true);
        $decorator2->method('decorate')->willReturn(static fn () => 'second');

        $chain = new FunctionDecoratorChain(new InMemoryCallableServiceLocator(['__NONE__' => [$decorator1, $decorator2]]));

        $result = $chain->decorate(static fn () => 'original', $this->createMetadata());

        self::assertSame('second', $result());
    }

    #[Test]
    public function decorateSkipsUnsupportedDecorators(): void
    {
        $unsupported = $this->createStub(FunctionDecoratorInterface::class);
        $unsupported->method('supports')->willReturn(false);

        $supported = $this->createStub(FunctionDecoratorInterface::class);
        $supported->method('supports')->willReturn(true);
        $supported->method('decorate')->willReturn(static fn () => 'decorated');

        $chain = new FunctionDecoratorChain(new InMemoryCallableServiceLocator(['__NONE__' => [$unsupported, $supported]]));

        $result = $chain->decorate(static fn () => 'original', $this->createMetadata());

        self::assertSame('decorated', $result());
    }

    #[Test]
    public function decorateSkipsAllUnsupportedDecorators(): void
    {
        $unsupported = $this->createStub(FunctionDecoratorInterface::class);
        $unsupported->method('supports')->willReturn(false);

        $chain = new FunctionDecoratorChain(new InMemoryCallableServiceLocator(['__NONE__' => [$unsupported]]));
        $fn = static fn () => 'original';

        $result = $chain->decorate($fn, $this->createMetadata());

        self::assertSame('original', $result());
    }

    #[Test]
    public function supportsUsesGroupDecorators(): void
    {
        $decorator = $this->createStub(FunctionDecoratorInterface::class);
        $decorator->method('supports')->willReturn(true);

        $chain = new FunctionDecoratorChain(new InMemoryCallableServiceLocator(['my_group' => [$decorator]]));

        self::assertTrue($chain->supports($this->createMetadata(groups: ['my_group'])));
    }

    #[Test]
    public function supportsReturnsFalseWhenGroupHasNoDecorators(): void
    {
        $decorator = $this->createStub(FunctionDecoratorInterface::class);
        $decorator->method('supports')->willReturn(true);

        $chain = new FunctionDecoratorChain(new InMemoryCallableServiceLocator(['my_group' => [$decorator]]));

        self::assertFalse($chain->supports($this->createMetadata(groups: ['other_group'])));
    }

    #[Test]
    public function decorateUsesGroupDecorators(): void
    {
        $decorator = $this->createStub(FunctionDecoratorInterface::class);
        $decorator->method('supports')->willReturn(true);
        $decorator->method('decorate')->willReturn(static fn () => 'from_group');

        $chain = new FunctionDecoratorChain(new InMemoryCallableServiceLocator(['my_group' => [$decorator]]));

        $result = $chain->decorate(static fn () => 'original', $this->createMetadata(groups: ['my_group']));

        self::assertSame('from_group', $result());
    }

    #[Test]
    public function decorateReturnsOriginalWhenGroupHasNoDecorators(): void
    {
        $decorator = $this->createStub(FunctionDecoratorInterface::class);
        $decorator->method('supports')->willReturn(true);

        $chain = new FunctionDecoratorChain(new InMemoryCallableServiceLocator(['my_group' => [$decorator]]));
        $fn = static fn () => 'original';

        $result = $chain->decorate($fn, $this->createMetadata(groups: ['other_group']));

        self::assertSame($fn, $result);
    }

    #[Test]
    public function nullGroupFallsBackToNoneGroup(): void
    {
        $decorator = $this->createStub(FunctionDecoratorInterface::class);
        $decorator->method('supports')->willReturn(true);
        $decorator->method('decorate')->willReturn(static fn () => 'from_none');

        $chain = new FunctionDecoratorChain(new InMemoryCallableServiceLocator(['__NONE__' => [$decorator]]));

        $result = $chain->decorate(static fn () => 'original', $this->createMetadata());

        self::assertSame('from_none', $result());
    }

    #[Test]
    public function decorateAggregatesDecoratorsFromMultipleGroups(): void
    {
        $shared = $this->createStub(FunctionDecoratorInterface::class);
        $shared->method('supports')->willReturn(true);
        $shared->method('decorate')->willReturnCallback(static fn (\Closure $fn) => static fn () => $fn().'_shared');

        $decoratorA = $this->createStub(FunctionDecoratorInterface::class);
        $decoratorA->method('supports')->willReturn(true);
        $decoratorA->method('decorate')->willReturnCallback(static fn (\Closure $fn) => static fn () => $fn().'_A');

        $decoratorB = $this->createStub(FunctionDecoratorInterface::class);
        $decoratorB->method('supports')->willReturn(true);
        $decoratorB->method('decorate')->willReturnCallback(static fn (\Closure $fn) => static fn () => $fn().'_B');

        $chain = new FunctionDecoratorChain(new InMemoryCallableServiceLocator([
            'foo' => [$shared, $decoratorA],
            'bar' => [$shared, $decoratorB],
        ]));

        $result = $chain->decorate(static fn () => 'original', $this->createMetadata(groups: ['foo', 'bar']));

        // shared appears only once (dedup), then A from foo, then B from bar
        self::assertSame('original_shared_A_B', $result());
    }

    #[Test]
    public function supportsAggregatesAcrossMultipleGroups(): void
    {
        $decorator = $this->createStub(FunctionDecoratorInterface::class);
        $decorator->method('supports')->willReturn(true);

        $chain = new FunctionDecoratorChain(new InMemoryCallableServiceLocator(['bar' => [$decorator]]));

        // 'foo' has nothing, but 'bar' has a supporting decorator
        self::assertTrue($chain->supports($this->createMetadata(groups: ['foo', 'bar'])));
    }
}
