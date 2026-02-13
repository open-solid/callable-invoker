<?php

namespace OpenSolid\CallableInvoker\Tests\Decorator;

use OpenSolid\CallableInvoker\Decorator\FunctionDecoratorChain;
use OpenSolid\CallableInvoker\Decorator\FunctionDecoratorInterface;
use OpenSolid\CallableInvoker\Tests\TestHelper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FunctionDecoratorChainTest extends TestCase
{
    use TestHelper;

    #[Test]
    public function doesNotSupportWhenEmpty(): void
    {
        $chain = new FunctionDecoratorChain([]);

        self::assertFalse($chain->supports($this->createMetadata()));
    }

    #[Test]
    public function supportsWhenAnyDecoratorSupports(): void
    {
        $unsupported = $this->createStub(FunctionDecoratorInterface::class);
        $unsupported->method('supports')->willReturn(false);

        $supported = $this->createStub(FunctionDecoratorInterface::class);
        $supported->method('supports')->willReturn(true);

        $chain = new FunctionDecoratorChain([$unsupported, $supported]);

        self::assertTrue($chain->supports($this->createMetadata()));
    }

    #[Test]
    public function doesNotSupportWhenNoDecoratorSupports(): void
    {
        $unsupported = $this->createStub(FunctionDecoratorInterface::class);
        $unsupported->method('supports')->willReturn(false);

        $chain = new FunctionDecoratorChain([$unsupported]);

        self::assertFalse($chain->supports($this->createMetadata()));
    }

    #[Test]
    public function decorateWithNoDecorators(): void
    {
        $chain = new FunctionDecoratorChain([]);
        $fn = fn () => 'original';

        $result = $chain->decorate($fn, $this->createMetadata());

        self::assertSame($fn, $result);
    }

    #[Test]
    public function decorateAppliesAllSupportedDecorators(): void
    {
        $decorator1 = $this->createStub(FunctionDecoratorInterface::class);
        $decorator1->method('supports')->willReturn(true);
        $decorator1->method('decorate')->willReturn(fn () => 'first');

        $decorator2 = $this->createStub(FunctionDecoratorInterface::class);
        $decorator2->method('supports')->willReturn(true);
        $decorator2->method('decorate')->willReturn(fn () => 'second');

        $chain = new FunctionDecoratorChain([$decorator1, $decorator2]);

        $result = $chain->decorate(fn () => 'original', $this->createMetadata());

        self::assertSame('second', $result());
    }

    #[Test]
    public function decorateSkipsUnsupportedDecorators(): void
    {
        $unsupported = $this->createStub(FunctionDecoratorInterface::class);
        $unsupported->method('supports')->willReturn(false);

        $supported = $this->createStub(FunctionDecoratorInterface::class);
        $supported->method('supports')->willReturn(true);
        $supported->method('decorate')->willReturn(fn () => 'decorated');

        $chain = new FunctionDecoratorChain([$unsupported, $supported]);

        $result = $chain->decorate(fn () => 'original', $this->createMetadata());

        self::assertSame('decorated', $result());
    }

    #[Test]
    public function decorateSkipsAllUnsupportedDecorators(): void
    {
        $unsupported = $this->createStub(FunctionDecoratorInterface::class);
        $unsupported->method('supports')->willReturn(false);

        $chain = new FunctionDecoratorChain([$unsupported]);
        $fn = fn () => 'original';

        $result = $chain->decorate($fn, $this->createMetadata());

        self::assertSame('original', $result());
    }
}
