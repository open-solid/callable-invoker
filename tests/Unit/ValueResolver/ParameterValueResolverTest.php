<?php

namespace OpenSolid\CallableInvoker\Tests\Unit\ValueResolver;

use OpenSolid\CallableInvoker\Exception\ParameterNotSupportedException;
use OpenSolid\CallableInvoker\Exception\SkipParameterException;
use OpenSolid\CallableInvoker\InMemoryCallableServiceLocator;
use OpenSolid\CallableInvoker\Tests\Unit\TestHelper;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolver;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolverInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ParameterValueResolverTest extends TestCase
{
    use TestHelper;

    #[Test]
    public function resolveWithFirstSupportingResolver(): void
    {
        $resolver = $this->createStub(ParameterValueResolverInterface::class);
        $resolver->method('supports')->willReturn(true);
        $resolver->method('resolve')->willReturn('resolved');

        $resolver = new ParameterValueResolver(new InMemoryCallableServiceLocator(['__NONE__' => [$resolver]]));
        $parameter = $this->getParameter(static fn (string $name) => null, 'name');

        self::assertSame('resolved', $resolver->resolve($parameter, $this->createMetadata()));
    }

    #[Test]
    public function resolveSkipsUnsupportedResolvers(): void
    {
        $unsupported = $this->createStub(ParameterValueResolverInterface::class);
        $unsupported->method('supports')->willReturn(false);

        $supported = $this->createStub(ParameterValueResolverInterface::class);
        $supported->method('supports')->willReturn(true);
        $supported->method('resolve')->willReturn('fallback');

        $resolver = new ParameterValueResolver(new InMemoryCallableServiceLocator(['__NONE__' => [$unsupported, $supported]]));
        $parameter = $this->getParameter(static fn (string $name) => null, 'name');

        self::assertSame('fallback', $resolver->resolve($parameter, $this->createMetadata()));
    }

    #[Test]
    public function throwsWhenNoResolverSupports(): void
    {
        $unsupported = $this->createStub(ParameterValueResolverInterface::class);
        $unsupported->method('supports')->willReturn(false);

        $resolver = new ParameterValueResolver(new InMemoryCallableServiceLocator(['__NONE__' => [$unsupported]]));
        $parameter = $this->getParameter(static fn (string $name) => null, 'name');

        $this->expectException(ParameterNotSupportedException::class);
        $this->expectExceptionMessage('Could not resolve value for parameter "$name"');
        $resolver->resolve($parameter, $this->createMetadata());
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

        $resolver = new ParameterValueResolver(new InMemoryCallableServiceLocator(['__NONE__' => [$skipping, $fallback]]));
        $parameter = $this->getParameter(static fn (string $name) => null, 'name');

        self::assertSame('fallback', $resolver->resolve($parameter, $this->createMetadata()));
    }

    #[Test]
    public function throwsWhenEmpty(): void
    {
        $resolver = new ParameterValueResolver(new InMemoryCallableServiceLocator());
        $parameter = $this->getParameter(static fn (string $name) => null, 'name');

        $this->expectException(ParameterNotSupportedException::class);
        $resolver->resolve($parameter, $this->createMetadata());
    }

    #[Test]
    public function resolveUsesGroupResolvers(): void
    {
        $resolver = $this->createStub(ParameterValueResolverInterface::class);
        $resolver->method('supports')->willReturn(true);
        $resolver->method('resolve')->willReturn('from_group');

        $resolver = new ParameterValueResolver(new InMemoryCallableServiceLocator(['my_group' => [$resolver]]));
        $parameter = $this->getParameter(static fn (string $name) => null, 'name');

        self::assertSame('from_group', $resolver->resolve($parameter, $this->createMetadata(groups: ['my_group'])));
    }

    #[Test]
    public function resolveThrowsWhenGroupHasNoMatchingResolver(): void
    {
        $resolver = $this->createStub(ParameterValueResolverInterface::class);
        $resolver->method('supports')->willReturn(true);

        $resolver = new ParameterValueResolver(new InMemoryCallableServiceLocator(['my_group' => [$resolver]]));
        $parameter = $this->getParameter(static fn (string $name) => null, 'name');

        $this->expectException(ParameterNotSupportedException::class);
        $resolver->resolve($parameter, $this->createMetadata(groups: ['other_group']));
    }

    #[Test]
    public function nullGroupFallsBackToNoneGroup(): void
    {
        $resolver = $this->createStub(ParameterValueResolverInterface::class);
        $resolver->method('supports')->willReturn(true);
        $resolver->method('resolve')->willReturn('from_none');

        $resolver = new ParameterValueResolver(new InMemoryCallableServiceLocator(['__NONE__' => [$resolver]]));
        $parameter = $this->getParameter(static fn (string $name) => null, 'name');

        self::assertSame('from_none', $resolver->resolve($parameter, $this->createMetadata()));
    }

    #[Test]
    public function resolveAggregatesResolversFromMultipleGroups(): void
    {
        $resolverA = $this->createStub(ParameterValueResolverInterface::class);
        $resolverA->method('supports')->willReturn(false);

        $resolverB = $this->createStub(ParameterValueResolverInterface::class);
        $resolverB->method('supports')->willReturn(true);
        $resolverB->method('resolve')->willReturn('from_bar');

        $resolver = new ParameterValueResolver(new InMemoryCallableServiceLocator([
            'foo' => [$resolverA],
            'bar' => [$resolverB],
        ]));
        $parameter = $this->getParameter(static fn (string $name) => null, 'name');

        self::assertSame('from_bar', $resolver->resolve($parameter, $this->createMetadata(groups: ['foo', 'bar'])));
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

        $resolver = new ParameterValueResolver(new InMemoryCallableServiceLocator([
            'foo' => [$shared, $fallback],
            'bar' => [$shared],
        ]));
        $parameter = $this->getParameter(static fn (string $name) => null, 'name');

        $resolver->resolve($parameter, $this->createMetadata(groups: ['foo', 'bar']));

        // shared resolver should only be checked once despite being in both groups
        self::assertSame(1, $callCount);
    }
}
