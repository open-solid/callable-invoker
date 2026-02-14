<?php

namespace OpenSolid\CallableInvoker\Tests\Unit\ValueResolver;

use OpenSolid\CallableInvoker\Exception\ParameterNotSupportedException;
use OpenSolid\CallableInvoker\Exception\SkipParameterException;
use OpenSolid\CallableInvoker\Tests\Unit\TestHelper;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolverChain;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolverInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class ParameterValueResolverChainTest extends TestCase
{
    use TestHelper;

    #[Test]
    public function supportsWhenAnyResolverSupports(): void
    {
        $unsupported = $this->createStub(ParameterValueResolverInterface::class);
        $unsupported->method('supports')->willReturn(false);

        $supported = $this->createStub(ParameterValueResolverInterface::class);
        $supported->method('supports')->willReturn(true);

        $chain = new ParameterValueResolverChain($this->createContainer([$unsupported, $supported]));
        $parameter = $this->getParameter(fn (string $name) => null, 'name');

        self::assertTrue($chain->supports($parameter, $this->createMetadata()));
    }

    #[Test]
    public function doesNotSupportWhenNoResolverSupports(): void
    {
        $unsupported = $this->createStub(ParameterValueResolverInterface::class);
        $unsupported->method('supports')->willReturn(false);

        $chain = new ParameterValueResolverChain($this->createContainer([$unsupported]));
        $parameter = $this->getParameter(fn (string $name) => null, 'name');

        self::assertFalse($chain->supports($parameter, $this->createMetadata()));
    }

    #[Test]
    public function doesNotSupportWhenEmpty(): void
    {
        $chain = new ParameterValueResolverChain($this->createContainer([]));
        $parameter = $this->getParameter(fn (string $name) => null, 'name');

        self::assertFalse($chain->supports($parameter, $this->createMetadata()));
    }

    #[Test]
    public function resolveWithFirstSupportingResolver(): void
    {
        $resolver = $this->createStub(ParameterValueResolverInterface::class);
        $resolver->method('supports')->willReturn(true);
        $resolver->method('resolve')->willReturn('resolved');

        $chain = new ParameterValueResolverChain($this->createContainer([$resolver]));
        $parameter = $this->getParameter(fn (string $name) => null, 'name');

        self::assertSame('resolved', $chain->resolve($parameter, $this->createMetadata()));
    }

    #[Test]
    public function resolveSkipsUnsupportedResolvers(): void
    {
        $unsupported = $this->createStub(ParameterValueResolverInterface::class);
        $unsupported->method('supports')->willReturn(false);

        $supported = $this->createStub(ParameterValueResolverInterface::class);
        $supported->method('supports')->willReturn(true);
        $supported->method('resolve')->willReturn('fallback');

        $chain = new ParameterValueResolverChain($this->createContainer([$unsupported, $supported]));
        $parameter = $this->getParameter(fn (string $name) => null, 'name');

        self::assertSame('fallback', $chain->resolve($parameter, $this->createMetadata()));
    }

    #[Test]
    public function throwsWhenNoResolverSupports(): void
    {
        $unsupported = $this->createStub(ParameterValueResolverInterface::class);
        $unsupported->method('supports')->willReturn(false);

        $chain = new ParameterValueResolverChain($this->createContainer([$unsupported]));
        $parameter = $this->getParameter(fn (string $name) => null, 'name');

        $this->expectException(ParameterNotSupportedException::class);
        $this->expectExceptionMessage('Could not resolve value for parameter "$name" in "test".');
        $chain->resolve($parameter, $this->createMetadata());
    }

    #[Test]
    public function resolveSkipsResolverThatThrowsSkipParameterException(): void
    {
        $skipping = $this->createStub(ParameterValueResolverInterface::class);
        $skipping->method('supports')->willReturn(true);
        $skipping->method('resolve')->willThrowException(new SkipParameterException());

        $fallback = $this->createStub(ParameterValueResolverInterface::class);
        $fallback->method('supports')->willReturn(true);
        $fallback->method('resolve')->willReturn('fallback');

        $chain = new ParameterValueResolverChain($this->createContainer([$skipping, $fallback]));
        $parameter = $this->getParameter(fn (string $name) => null, 'name');

        self::assertSame('fallback', $chain->resolve($parameter, $this->createMetadata()));
    }

    #[Test]
    public function throwsWhenEmpty(): void
    {
        $chain = new ParameterValueResolverChain($this->createContainer([]));
        $parameter = $this->getParameter(fn (string $name) => null, 'name');

        $this->expectException(ParameterNotSupportedException::class);
        $chain->resolve($parameter, $this->createMetadata());
    }

    #[Test]
    public function supportsUsesGroupResolvers(): void
    {
        $resolver = $this->createStub(ParameterValueResolverInterface::class);
        $resolver->method('supports')->willReturn(true);

        $chain = new ParameterValueResolverChain($this->createContainer([$resolver], 'my_group'));
        $parameter = $this->getParameter(fn (string $name) => null, 'name');

        self::assertTrue($chain->supports($parameter, $this->createMetadata(), 'my_group'));
    }

    #[Test]
    public function supportsReturnsFalseWhenGroupHasNoResolvers(): void
    {
        $resolver = $this->createStub(ParameterValueResolverInterface::class);
        $resolver->method('supports')->willReturn(true);

        $chain = new ParameterValueResolverChain($this->createContainer([$resolver], 'my_group'));
        $parameter = $this->getParameter(fn (string $name) => null, 'name');

        self::assertFalse($chain->supports($parameter, $this->createMetadata(), 'other_group'));
    }

    #[Test]
    public function resolveUsesGroupResolvers(): void
    {
        $resolver = $this->createStub(ParameterValueResolverInterface::class);
        $resolver->method('supports')->willReturn(true);
        $resolver->method('resolve')->willReturn('from_group');

        $chain = new ParameterValueResolverChain($this->createContainer([$resolver], 'my_group'));
        $parameter = $this->getParameter(fn (string $name) => null, 'name');

        self::assertSame('from_group', $chain->resolve($parameter, $this->createMetadata(), 'my_group'));
    }

    #[Test]
    public function resolveThrowsWhenGroupHasNoMatchingResolver(): void
    {
        $resolver = $this->createStub(ParameterValueResolverInterface::class);
        $resolver->method('supports')->willReturn(true);

        $chain = new ParameterValueResolverChain($this->createContainer([$resolver], 'my_group'));
        $parameter = $this->getParameter(fn (string $name) => null, 'name');

        $this->expectException(ParameterNotSupportedException::class);
        $chain->resolve($parameter, $this->createMetadata(), 'other_group');
    }

    #[Test]
    public function nullGroupFallsBackToNoneGroup(): void
    {
        $resolver = $this->createStub(ParameterValueResolverInterface::class);
        $resolver->method('supports')->willReturn(true);
        $resolver->method('resolve')->willReturn('from_none');

        $chain = new ParameterValueResolverChain($this->createContainer([$resolver]));
        $parameter = $this->getParameter(fn (string $name) => null, 'name');

        self::assertSame('from_none', $chain->resolve($parameter, $this->createMetadata(), null));
    }

    /**
     * @param list<ParameterValueResolverInterface> $resolvers
     */
    private function createContainer(array $resolvers, string $group = '__NONE__'): ContainerInterface
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturnCallback(fn (string $id) => $id === $group);
        $container->method('get')->willReturnCallback(fn (string $id) => $id === $group ? $resolvers : []);

        return $container;
    }
}
