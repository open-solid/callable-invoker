<?php

namespace OpenSolid\CallableInvoker\Tests\Integration;

use OpenSolid\CallableInvoker\CallableInvoker;
use OpenSolid\CallableInvoker\CallableInvokerBundle;
use OpenSolid\CallableInvoker\CallableInvokerInterface;
use OpenSolid\CallableInvoker\Decorator\FunctionDecoratorChain;
use OpenSolid\CallableInvoker\Decorator\FunctionDecoratorInterface;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolverChain;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolverInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
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
    public function aliasesPointToCorrectServices(): void
    {
        $container = $this->createContainer();

        self::assertInstanceOf(CallableInvoker::class, $container->get(CallableInvokerInterface::class));
        self::assertInstanceOf(FunctionDecoratorChain::class, $container->get(FunctionDecoratorInterface::class));
        self::assertInstanceOf(ParameterValueResolverChain::class, $container->get(ParameterValueResolverInterface::class));
    }

    #[Test]
    public function invokerResolvesDefaultValues(): void
    {
        $invoker = $this->createContainer()->get(CallableInvokerInterface::class);

        $result = $invoker->invoke(fn (string $name = 'World') => "Hello, $name!");

        self::assertSame('Hello, World!', $result);
    }

    #[Test]
    public function invokerResolvesNullableValues(): void
    {
        $invoker = $this->createContainer()->get(CallableInvokerInterface::class);

        $result = $invoker->invoke(fn (?string $name) => $name ?? 'fallback');

        self::assertSame('fallback', $result);
    }

    private function createContainer(): ContainerInterface
    {
        $this->kernel = new class ('test', true) extends Kernel {
            public function registerBundles(): iterable
            {
                return [
                    new FrameworkBundle(),
                    new CallableInvokerBundle(),
                    new class extends Bundle
                    {
                        public function shutdown(): void
                        {
                            restore_exception_handler();
                        }
                    },
                ];
            }

            public function registerContainerConfiguration(LoaderInterface $loader): void
            {
                $loader->load(function (ContainerBuilder $container) {
                    $container->loadFromExtension('framework', ['test' => true]);
                });
            }
        };

        $this->kernel->boot();

        return $this->kernel->getContainer()->get('test.service_container');
    }
}
