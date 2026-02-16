<?php

use OpenSolid\CallableInvoker\CallableInvoker;
use OpenSolid\CallableInvoker\CallableInvokerInterface;
use OpenSolid\CallableInvoker\Decorator\CallableDecorator;
use OpenSolid\CallableInvoker\Decorator\CallableDecoratorInterface;
use OpenSolid\CallableInvoker\CallableServiceLocator;
use OpenSolid\CallableInvoker\ValueResolver\ContextParameterValueResolver;
use OpenSolid\CallableInvoker\ValueResolver\DefaultValueParameterValueResolver;
use OpenSolid\CallableInvoker\ValueResolver\UnsupportedParameterValueResolver;
use OpenSolid\CallableInvoker\ValueResolver\NullableParameterValueResolver;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolver;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolverInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\abstract_arg;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->instanceof(CallableDecoratorInterface::class)
        ->tag('callable_invoker.decorator');

    $services->instanceof(ParameterValueResolverInterface::class)
        ->tag('callable_invoker.value_resolver');

    $services->set('callable_invoker.value_resolver.unsupported', UnsupportedParameterValueResolver::class)
        ->tag('callable_invoker.value_resolver', ['priority' => 100]);
    $services->set('callable_invoker.value_resolver.context', ContextParameterValueResolver::class)
        ->tag('callable_invoker.value_resolver', ['priority' => -100]);
    $services->set('callable_invoker.value_resolver.default_value', DefaultValueParameterValueResolver::class)
        ->tag('callable_invoker.value_resolver', ['priority' => -200]);
    $services->set('callable_invoker.value_resolver.nullable', NullableParameterValueResolver::class)
        ->tag('callable_invoker.value_resolver', ['priority' => -300]);

    $services->set('callable_invoker.decorator_groups', CallableServiceLocator::class)
        ->args([abstract_arg('groups of decorators')]);
    $services->set('callable_invoker.decorator', CallableDecorator::class)
        ->args([service('callable_invoker.decorator_groups')]);

    $services->set('callable_invoker.value_resolver_groups', CallableServiceLocator::class)
        ->args([abstract_arg('groups of value resolvers')]);
    $services->set('callable_invoker.value_resolver', ParameterValueResolver::class)
        ->args([service('callable_invoker.value_resolver_groups')]);

    $services->set('callable_invoker', CallableInvoker::class)
        ->args([
            service('callable_invoker.decorator'),
            service('callable_invoker.value_resolver'),
        ]);

    $services->alias(CallableInvokerInterface::class, 'callable_invoker');
};
