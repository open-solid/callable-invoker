<?php

namespace OpenSolid\CallableInvoker\Tests\Unit\Decorator;

use OpenSolid\CallableInvoker\CallableMetadata;
use OpenSolid\CallableInvoker\Decorator\AbstractCallableDecorator;
use OpenSolid\CallableInvoker\Decorator\ClosureHandler;
use OpenSolid\CallableInvoker\Tests\Unit\TestHelper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AbstractCallableDecoratorTest extends TestCase
{
    use TestHelper;

    #[Test]
    public function decorateReturnsClosureThatDelegatesToInvoke(): void
    {
        $decorator = new class extends AbstractCallableDecorator {
            public function supports(CallableMetadata $metadata): bool
            {
                return true;
            }

            protected function invoke(ClosureHandler $handler, CallableMetadata $metadata): mixed
            {
                return $handler->handle();
            }
        };

        $decorated = $decorator->decorate(static fn () => 'hello', $this->createMetadata());

        self::assertSame('hello', $decorated());
    }

    #[Test]
    public function decoratePassesArgumentsToClosure(): void
    {
        $decorator = new class extends AbstractCallableDecorator {
            public function supports(CallableMetadata $metadata): bool
            {
                return true;
            }

            protected function invoke(ClosureHandler $handler, CallableMetadata $metadata): mixed
            {
                return $handler->handle();
            }
        };

        $decorated = $decorator->decorate(static fn (string $a, string $b) => "$a $b", $this->createMetadata());

        self::assertSame('hello world', $decorated('hello', 'world'));
    }

    #[Test]
    public function decorateAllowsModifyingResult(): void
    {
        $decorator = new class extends AbstractCallableDecorator {
            public function supports(CallableMetadata $metadata): bool
            {
                return true;
            }

            protected function invoke(ClosureHandler $handler, CallableMetadata $metadata): string
            {
                return '[wrapped] '.$handler->handle();
            }
        };

        $decorated = $decorator->decorate(static fn () => 'result', $this->createMetadata());

        self::assertSame('[wrapped] result', $decorated());
    }

    #[Test]
    public function decorateAllowsSkippingOriginalClosure(): void
    {
        $decorator = new class extends AbstractCallableDecorator {
            public function supports(CallableMetadata $metadata): bool
            {
                return true;
            }

            protected function invoke(ClosureHandler $handler, CallableMetadata $metadata): string
            {
                return 'short-circuited';
            }
        };

        $decorated = $decorator->decorate(static fn () => 'original', $this->createMetadata());

        self::assertSame('short-circuited', $decorated());
    }

    #[Test]
    public function decorateExposesClosureAndArgumentsViaHandler(): void
    {
        $capturedHandler = null;

        $decorator = new class($capturedHandler) extends AbstractCallableDecorator {
            public function __construct(private(set) ?ClosureHandler &$captured)
            {
            }

            public function supports(CallableMetadata $metadata): bool
            {
                return true;
            }

            protected function invoke(ClosureHandler $handler, CallableMetadata $metadata): mixed
            {
                $this->captured = $handler;

                return $handler->handle();
            }
        };

        $fn = static fn (int $x) => $x * 2;
        $decorated = $decorator->decorate($fn, $this->createMetadata());
        $result = $decorated(5);

        self::assertSame(10, $result);
        self::assertNotNull($capturedHandler);
        self::assertSame([5], $capturedHandler->arguments);
        self::assertSame($result, ($capturedHandler->closure)(5));
    }
}
