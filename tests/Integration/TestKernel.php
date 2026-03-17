<?php

namespace OpenSolid\CallableInvoker\Tests\Integration;

use OpenSolid\CallableInvoker\CallableInvokerBundle;
use OpenSolid\CallableInvoker\CallableInvokerInterface;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Attribute\Route;

final class TestKernel extends Kernel implements CompilerPassInterface
{
    private string $cacheDir;

    /**
     * @param \Closure(ContainerBuilder): void|null $configure
     * @param array<string, mixed>                  $bundleConfig
     */
    public function __construct(
        private ?\Closure $configure = null,
        private array $bundleConfig = [],
    ) {
        parent::__construct('test', false);
        $this->cacheDir = sys_get_temp_dir().'/callable_invoker_test_'.spl_object_id($this);
    }

    #[Route('/test', name: 'test')]
    public function __invoke(): Response
    {
        return new Response('original');
    }

    public function getCacheDir(): string
    {
        return $this->cacheDir;
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
        $bundleConfig = $this->bundleConfig;
        $loader->load(static function (ContainerBuilder $container) use ($bundleConfig) {
            $container->loadFromExtension('framework', [
                'test' => true,
                'router' => ['resource' => __DIR__.'/TestKernel.php', 'type' => 'attribute'],
            ]);

            if ([] !== $bundleConfig) {
                $container->loadFromExtension('callable_invoker', $bundleConfig);
            }
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

        if ($container->hasDefinition('callable_invoker.decorate_controller_listener')) {
            $container->getDefinition('callable_invoker.decorate_controller_listener')->setPublic(true);
        }
    }

    public function shutdown(): void
    {
        $cacheDir = $this->cacheDir;
        parent::shutdown();

        new Filesystem()->remove($cacheDir);
    }
}
