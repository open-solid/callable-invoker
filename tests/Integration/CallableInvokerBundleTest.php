<?php

namespace OpenSolid\CallableInvoker\Tests\Integration;

use OpenSolid\CallableInvoker\CallableInvoker;
use OpenSolid\CallableInvoker\CallableInvokerBundle;
use OpenSolid\CallableInvoker\CallableInvokerInterface;
use OpenSolid\CallableInvoker\Decorator\FunctionDecoratorChain;
use OpenSolid\CallableInvoker\Decorator\FunctionDecoratorInterface;
use OpenSolid\CallableInvoker\ValueResolver\DefaultValueParameterValueResolver;
use OpenSolid\CallableInvoker\ValueResolver\NullableParameterValueResolver;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolverChain;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolverInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Kernel;

final class CallableInvokerBundleTest extends TestCase
{
    #[Test]
    public function servicesAreRegistered(): void
    {
        $container = $this->createContainer();

        self::assertTrue($container->has(CallableInvoker::class));
        self::assertTrue($container->has(FunctionDecoratorChain::class));
        self::assertTrue($container->has(ParameterValueResolverChain::class));
        self::assertTrue($container->has(DefaultValueParameterValueResolver::class));
        self::assertTrue($container->has(NullableParameterValueResolver::class));
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
        $kernel = new class ('test', true) extends Kernel {
            public function registerBundles(): iterable
            {
                return [new CallableInvokerBundle()];
            }

            public function registerContainerConfiguration(LoaderInterface $loader): void
            {
            }

            protected function build(ContainerBuilder $container): void
            {
                $container->register('kernel', self::class)
                    ->setPublic(true);

                $container->addCompilerPass(new class implements CompilerPassInterface {
                    public function process(ContainerBuilder $container): void
                    {
                        foreach ($container->getDefinitions() as $definition) {
                            $definition->setPublic(true);
                        }
                        foreach ($container->getAliases() as $alias) {
                            $alias->setPublic(true);
                        }
                    }
                });
            }
        };

        $kernel->boot();

        return $kernel->getContainer();
    }
}
