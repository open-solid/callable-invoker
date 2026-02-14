<?php

namespace OpenSolid\CallableInvoker;

use OpenSolid\CallableInvoker\Decorator\Attribute\AsFunctionDecorator;
use OpenSolid\CallableInvoker\DependecyInjection\Compiler\FunctionDecoratorPass;
use OpenSolid\CallableInvoker\DependecyInjection\Compiler\ParameterValueResolverPass;
use OpenSolid\CallableInvoker\ValueResolver\Attribute\AsParameterValueResolver;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class CallableInvokerBundle extends AbstractBundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new FunctionDecoratorPass());
        $container->addCompilerPass(new ParameterValueResolverPass());
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->import('../config/definition.php');
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->registerAttributeForAutoconfiguration(AsFunctionDecorator::class, static function (ChildDefinition $definition, AsFunctionDecorator $attribute) {
            $definition->addTag('callable_invoker.decorator', ['groups' => $attribute->groups, 'priority' => $attribute->priority]);
        });

        $builder->registerAttributeForAutoconfiguration(AsParameterValueResolver::class, static function (ChildDefinition $definition, AsParameterValueResolver $attribute) {
            $definition->addTag('callable_invoker.value_resolver', ['groups' => $attribute->groups, 'priority' => $attribute->priority]);
        });

        $container->import('../config/services.php');
    }
}
