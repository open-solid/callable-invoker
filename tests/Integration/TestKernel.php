<?php

namespace OpenSolid\CallableInvoker\Tests\Integration;

use OpenSolid\CallableInvoker\CallableInvokerBundle;
use OpenSolid\CallableInvoker\CallableInvokerInterface;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Kernel;

final class TestKernel extends Kernel implements CompilerPassInterface
{
    private string $cacheDir;

    /**
     * @param \Closure(ContainerBuilder): void|null $configure
     */
    public function __construct(
        private ?\Closure $configure = null,
    ) {
        parent::__construct('test', true);
        $this->cacheDir = sys_get_temp_dir().'/callable_invoker_test_'.spl_object_id($this);
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

    public function shutdown(): void
    {
        $cacheDir = $this->cacheDir;
        parent::shutdown();

        new Filesystem()->remove($cacheDir);
    }
}
