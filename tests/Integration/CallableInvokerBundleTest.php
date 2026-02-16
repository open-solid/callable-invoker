<?php

namespace OpenSolid\CallableInvoker\Tests\Integration;

use OpenSolid\CallableInvoker\CallableInvokerBundle;
use OpenSolid\CallableInvoker\CallableInvokerInterface;
use OpenSolid\CallableInvoker\CallableMetadata;
use OpenSolid\CallableInvoker\Decorator\FunctionDecoratorInterface;
use OpenSolid\CallableInvoker\Exception\UntypedParameterNotSupportedException;
use OpenSolid\CallableInvoker\Exception\VariadicParameterNotSupportedException;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolverInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Kernel;

final class CallableInvokerBundleTest extends TestCase
{
    private ?Kernel $kernel = null;

    protected function tearDown(): void
    {
        $this->kernel?->shutdown();
    }

    #[Test]
    public function servicesAreRegistered(): void
    {
        $container = $this->createContainer();

        self::assertTrue($container->has(CallableInvokerInterface::class));
        self::assertTrue($container->has(FunctionDecoratorInterface::class));
        self::assertTrue($container->has(ParameterValueResolverInterface::class));
    }

    #[Test]
    public function invokerResolvesDefaultValues(): void
    {
        $invoker = $this->createContainer()->get(CallableInvokerInterface::class);

        $result = $invoker->invoke(static fn (string $name = 'World') => "Hello, $name!");

        self::assertSame('Hello, World!', $result);
    }

    #[Test]
    public function invokerResolvesNullableValues(): void
    {
        $invoker = $this->createContainer()->get(CallableInvokerInterface::class);

        $result = $invoker->invoke(static fn (?string $name) => $name);

        self::assertNull($result);
    }

    #[Test]
    public function invokerRejectsUntypedParameter(): void
    {
        $invoker = $this->createContainer()->get(CallableInvokerInterface::class);

        $this->expectException(UntypedParameterNotSupportedException::class);

        $invoker->invoke(static fn ($name) => $name);
    }

    #[Test]
    public function invokerRejectsVariadicParameter(): void
    {
        $invoker = $this->createContainer()->get(CallableInvokerInterface::class);

        $this->expectException(VariadicParameterNotSupportedException::class);

        $invoker->invoke(static fn (string ...$names) => implode(', ', $names));
    }

    #[Test]
    public function customDecoratorIsApplied(): void
    {
        $invoker = $this->createContainer(static function (ContainerBuilder $container) {
            $container->register('test.decorator', LoggingDecorator::class)
                ->addTag('callable_invoker.decorator');
        })->get(CallableInvokerInterface::class);

        $result = $invoker->invoke(static fn (string $name = 'World') => "Hello, $name!");

        self::assertSame('[decorated] Hello, World!', $result);
    }

    #[Test]
    public function customValueResolverIsApplied(): void
    {
        $invoker = $this->createContainer(static function (ContainerBuilder $container) {
            $container->register('test.value_resolver', GreetingValueResolver::class)
                ->addTag('callable_invoker.value_resolver');
        })->get(CallableInvokerInterface::class);

        $result = $invoker->invoke(static fn (string $greeting) => $greeting);

        self::assertSame('Hey!', $result);
    }

    /**
     * @param \Closure(ContainerBuilder): void|null $configure
     */
    private function createContainer(?\Closure $configure = null): ContainerInterface
    {
        $this->kernel = new class('test', true, $configure) extends Kernel implements CompilerPassInterface {
            private ?\Closure $configure;

            public function __construct(string $environment, bool $debug, ?\Closure $configure = null)
            {
                parent::__construct($environment, $debug);
                $this->configure = $configure;
            }

            public function getCacheDir(): string
            {
                return sys_get_temp_dir().'/callable_invoker_test_'.spl_object_id($this);
            }

            public function registerBundles(): iterable
            {
                return [
                    new FrameworkBundle(),
                    new CallableInvokerBundle(),
                    new class extends Bundle {
                        public function shutdown(): void
                        {
                            restore_exception_handler();
                        }
                    },
                ];
            }

            public function registerContainerConfiguration(LoaderInterface $loader): void
            {
                $loader->load(static function (ContainerBuilder $container) {
                    $container->loadFromExtension('framework', ['test' => true]);
                });
            }

            protected function build(ContainerBuilder $container): void
            {
                if (null !== $this->configure) {
                    ($this->configure)($container);
                }
            }

            public function process(ContainerBuilder $container): void
            {
                $container->getAlias(CallableInvokerInterface::class)->setPublic(true);
            }
        };

        $this->kernel->boot();

        return $this->kernel->getContainer()->get('test.service_container');
    }
}

final class LoggingDecorator implements FunctionDecoratorInterface
{
    public function supports(CallableMetadata $metadata, ?string $group = null): bool
    {
        return true;
    }

    public function decorate(\Closure $function, CallableMetadata $metadata, ?string $group = null): \Closure
    {
        return static fn (...$args) => '[decorated] '.$function(...$args);
    }
}

final class GreetingValueResolver implements ParameterValueResolverInterface
{
    public function supports(\ReflectionParameter $parameter, CallableMetadata $metadata, ?string $group = null): bool
    {
        return 'greeting' === $parameter->getName();
    }

    public function resolve(\ReflectionParameter $parameter, CallableMetadata $metadata, ?string $group = null): string
    {
        return 'Hey!';
    }
}
