<?php

namespace OpenSolid\CallableInvoker\Tests\Unit\Decorator;

use OpenSolid\CallableInvoker\Decorator\FunctionDecoratorChain;
use OpenSolid\CallableInvoker\Decorator\FunctionDecoratorInterface;
use OpenSolid\CallableInvoker\Tests\Unit\TestHelper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class FunctionDecoratorChainTest extends TestCase
{
    use TestHelper;

    #[Test]
    public function doesNotSupportWhenEmpty(): void
    {
        $chain = new FunctionDecoratorChain($this->createContainer([]));

        self::assertFalse($chain->supports($this->createMetadata()));
    }

    #[Test]
    public function supportsWhenAnyDecoratorSupports(): void
    {
        $unsupported = $this->createStub(FunctionDecoratorInterface::class);
        $unsupported->method('supports')->willReturn(false);

        $supported = $this->createStub(FunctionDecoratorInterface::class);
        $supported->method('supports')->willReturn(true);

        $chain = new FunctionDecoratorChain($this->createContainer([$unsupported, $supported]));

        self::assertTrue($chain->supports($this->createMetadata()));
    }

    #[Test]
    public function doesNotSupportWhenNoDecoratorSupports(): void
    {
        $unsupported = $this->createStub(FunctionDecoratorInterface::class);
        $unsupported->method('supports')->willReturn(false);

        $chain = new FunctionDecoratorChain($this->createContainer([$unsupported]));

        self::assertFalse($chain->supports($this->createMetadata()));
    }

    #[Test]
    public function decorateWithNoDecorators(): void
    {
        $chain = new FunctionDecoratorChain($this->createContainer([]));
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

        $chain = new FunctionDecoratorChain($this->createContainer([$decorator1, $decorator2]));

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

        $chain = new FunctionDecoratorChain($this->createContainer([$unsupported, $supported]));

        $result = $chain->decorate(fn () => 'original', $this->createMetadata());

        self::assertSame('decorated', $result());
    }

    #[Test]
    public function decorateSkipsAllUnsupportedDecorators(): void
    {
        $unsupported = $this->createStub(FunctionDecoratorInterface::class);
        $unsupported->method('supports')->willReturn(false);

        $chain = new FunctionDecoratorChain($this->createContainer([$unsupported]));
        $fn = fn () => 'original';

        $result = $chain->decorate($fn, $this->createMetadata());

        self::assertSame('original', $result());
    }

    #[Test]
    public function supportsUsesGroupDecorators(): void
    {
        $decorator = $this->createStub(FunctionDecoratorInterface::class);
        $decorator->method('supports')->willReturn(true);

        $chain = new FunctionDecoratorChain($this->createContainer([$decorator], 'my_group'));

        self::assertTrue($chain->supports($this->createMetadata(group: 'my_group')));
    }

    #[Test]
    public function supportsReturnsFalseWhenGroupHasNoDecorators(): void
    {
        $decorator = $this->createStub(FunctionDecoratorInterface::class);
        $decorator->method('supports')->willReturn(true);

        $chain = new FunctionDecoratorChain($this->createContainer([$decorator], 'my_group'));

        self::assertFalse($chain->supports($this->createMetadata(group: 'other_group')));
    }

    #[Test]
    public function decorateUsesGroupDecorators(): void
    {
        $decorator = $this->createStub(FunctionDecoratorInterface::class);
        $decorator->method('supports')->willReturn(true);
        $decorator->method('decorate')->willReturn(fn () => 'from_group');

        $chain = new FunctionDecoratorChain($this->createContainer([$decorator], 'my_group'));

        $result = $chain->decorate(fn () => 'original', $this->createMetadata(group: 'my_group'));

        self::assertSame('from_group', $result());
    }

    #[Test]
    public function decorateReturnsOriginalWhenGroupHasNoDecorators(): void
    {
        $decorator = $this->createStub(FunctionDecoratorInterface::class);
        $decorator->method('supports')->willReturn(true);

        $chain = new FunctionDecoratorChain($this->createContainer([$decorator], 'my_group'));
        $fn = fn () => 'original';

        $result = $chain->decorate($fn, $this->createMetadata(group: 'other_group'));

        self::assertSame($fn, $result);
    }

    #[Test]
    public function nullGroupFallsBackToNoneGroup(): void
    {
        $decorator = $this->createStub(FunctionDecoratorInterface::class);
        $decorator->method('supports')->willReturn(true);
        $decorator->method('decorate')->willReturn(fn () => 'from_none');

        $chain = new FunctionDecoratorChain($this->createContainer([$decorator]));

        $result = $chain->decorate(fn () => 'original', $this->createMetadata());

        self::assertSame('from_none', $result());
    }

    /**
     * @param list<FunctionDecoratorInterface> $decorators
     */
    private function createContainer(array $decorators, string $group = '__NONE__'): ContainerInterface
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturnCallback(fn (string $id) => $id === $group);
        $container->method('get')->willReturnCallback(fn (string $id) => $id === $group ? $decorators : []);

        return $container;
    }
}
