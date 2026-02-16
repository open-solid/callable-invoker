<?php

namespace OpenSolid\CallableInvoker\Tests\Unit\ValueResolver;

use OpenSolid\CallableInvoker\Exception\ParameterNotSupportedException;
use OpenSolid\CallableInvoker\Exception\SkipParameterException;
use OpenSolid\CallableInvoker\InMemoryCallableServiceLocator;
use OpenSolid\CallableInvoker\Tests\Unit\TestHelper;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolverChain;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolverInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

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

        $chain = new ParameterValueResolverChain(new InMemoryCallableServiceLocator(['__NONE__' => [$unsupported, $supported]]));
        $parameter = $this->getParameter(static fn (string $name) => null, 'name');

        self::assertTrue($chain->supports($parameter, $this->createMetadata()));
    }

    #[Test]
    public function doesNotSupportWhenNoResolverSupports(): void
    {
        $unsupported = $this->createStub(ParameterValueResolverInterface::class);
        $unsupported->method('supports')->willReturn(false);

        $chain = new ParameterValueResolverChain(new InMemoryCallableServiceLocator(['__NONE__' => [$unsupported]]));
        $parameter = $this->getParameter(static fn (string $name) => null, 'name');

        self::assertFalse($chain->supports($parameter, $this->createMetadata()));
    }

    #[Test]
    public function doesNotSupportWhenEmpty(): void
    {
        $chain = new ParameterValueResolverChain();
        $parameter = $this->getParameter(static fn (string $name) => null, 'name');

        self::assertFalse($chain->supports($parameter, $this->createMetadata()));
    }

    #[Test]
    public function resolveWithFirstSupportingResolver(): void
    {
        $resolver = $this->createStub(ParameterValueResolverInterface::class);
        $resolver->method('supports')->willReturn(true);
        $resolver->method('resolve')->willReturn('resolved');

        $chain = new ParameterValueResolverChain(new InMemoryCallableServiceLocator(['__NONE__' => [$resolver]]));
        $parameter = $this->getParameter(static fn (string $name) => null, 'name');

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

        $chain = new ParameterValueResolverChain(new InMemoryCallableServiceLocator(['__NONE__' => [$unsupported, $supported]]));
        $parameter = $this->getParameter(static fn (string $name) => null, 'name');

        self::assertSame('fallback', $chain->resolve($parameter, $this->createMetadata()));
    }

    #[Test]
    public function throwsWhenNoResolverSupports(): void
    {
        $unsupported = $this->createStub(ParameterValueResolverInterface::class);
        $unsupported->method('supports')->willReturn(false);

        $chain = new ParameterValueResolverChain(new InMemoryCallableServiceLocator(['__NONE__' => [$unsupported]]));
        $parameter = $this->getParameter(static fn (string $name) => null, 'name');

        $this->expectException(ParameterNotSupportedException::class);
        $this->expectExceptionMessage('Could not resolve value for parameter "$name"');
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

        $chain = new ParameterValueResolverChain(new InMemoryCallableServiceLocator(['__NONE__' => [$skipping, $fallback]]));
        $parameter = $this->getParameter(static fn (string $name) => null, 'name');

        self::assertSame('fallback', $chain->resolve($parameter, $this->createMetadata()));
    }

    #[Test]
    public function throwsWhenEmpty(): void
    {
        $chain = new ParameterValueResolverChain();
        $parameter = $this->getParameter(static fn (string $name) => null, 'name');

        $this->expectException(ParameterNotSupportedException::class);
        $chain->resolve($parameter, $this->createMetadata());
    }

    #[Test]
    public function supportsUsesGroupResolvers(): void
    {
        $resolver = $this->createStub(ParameterValueResolverInterface::class);
        $resolver->method('supports')->willReturn(true);

        $chain = new ParameterValueResolverChain(new InMemoryCallableServiceLocator(['my_group' => [$resolver]]));
        $parameter = $this->getParameter(static fn (string $name) => null, 'name');

        self::assertTrue($chain->supports($parameter, $this->createMetadata(groups: ['my_group'])));
    }

    #[Test]
    public function supportsReturnsFalseWhenGroupHasNoResolvers(): void
    {
        $resolver = $this->createStub(ParameterValueResolverInterface::class);
        $resolver->method('supports')->willReturn(true);

        $chain = new ParameterValueResolverChain(new InMemoryCallableServiceLocator(['my_group' => [$resolver]]));
        $parameter = $this->getParameter(static fn (string $name) => null, 'name');

        self::assertFalse($chain->supports($parameter, $this->createMetadata(groups: ['other_group'])));
    }

    #[Test]
    public function resolveUsesGroupResolvers(): void
    {
        $resolver = $this->createStub(ParameterValueResolverInterface::class);
        $resolver->method('supports')->willReturn(true);
        $resolver->method('resolve')->willReturn('from_group');

        $chain = new ParameterValueResolverChain(new InMemoryCallableServiceLocator(['my_group' => [$resolver]]));
        $parameter = $this->getParameter(static fn (string $name) => null, 'name');

        self::assertSame('from_group', $chain->resolve($parameter, $this->createMetadata(groups: ['my_group'])));
    }

    #[Test]
    public function resolveThrowsWhenGroupHasNoMatchingResolver(): void
    {
        $resolver = $this->createStub(ParameterValueResolverInterface::class);
        $resolver->method('supports')->willReturn(true);

        $chain = new ParameterValueResolverChain(new InMemoryCallableServiceLocator(['my_group' => [$resolver]]));
        $parameter = $this->getParameter(static fn (string $name) => null, 'name');

        $this->expectException(ParameterNotSupportedException::class);
        $chain->resolve($parameter, $this->createMetadata(groups: ['other_group']));
    }

    #[Test]
    public function nullGroupFallsBackToNoneGroup(): void
    {
        $resolver = $this->createStub(ParameterValueResolverInterface::class);
        $resolver->method('supports')->willReturn(true);
        $resolver->method('resolve')->willReturn('from_none');

        $chain = new ParameterValueResolverChain(new InMemoryCallableServiceLocator(['__NONE__' => [$resolver]]));
        $parameter = $this->getParameter(static fn (string $name) => null, 'name');

        self::assertSame('from_none', $chain->resolve($parameter, $this->createMetadata()));
    }

    #[Test]
    public function resolveAggregatesResolversFromMultipleGroups(): void
    {
        $resolverA = $this->createStub(ParameterValueResolverInterface::class);
        $resolverA->method('supports')->willReturn(false);

        $resolverB = $this->createStub(ParameterValueResolverInterface::class);
        $resolverB->method('supports')->willReturn(true);
        $resolverB->method('resolve')->willReturn('from_bar');

        $chain = new ParameterValueResolverChain(new InMemoryCallableServiceLocator([
            'foo' => [$resolverA],
            'bar' => [$resolverB],
        ]));
        $parameter = $this->getParameter(static fn (string $name) => null, 'name');

        self::assertSame('from_bar', $chain->resolve($parameter, $this->createMetadata(groups: ['foo', 'bar'])));
    }

    #[Test]
    public function resolveDeduplicatesResolversAcrossGroups(): void
    {
        $callCount = 0;
        $shared = $this->createStub(ParameterValueResolverInterface::class);
        $shared->method('supports')->willReturnCallback(static function () use (&$callCount) {
            ++$callCount;

            return false;
        });

        $fallback = $this->createStub(ParameterValueResolverInterface::class);
        $fallback->method('supports')->willReturn(true);
        $fallback->method('resolve')->willReturn('resolved');

        $chain = new ParameterValueResolverChain(new InMemoryCallableServiceLocator([
            'foo' => [$shared, $fallback],
            'bar' => [$shared],
        ]));
        $parameter = $this->getParameter(static fn (string $name) => null, 'name');

        $chain->resolve($parameter, $this->createMetadata(groups: ['foo', 'bar']));

        // shared resolver should only be checked once despite being in both groups
        self::assertSame(1, $callCount);
    }
}
