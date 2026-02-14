<?php

use OpenSolid\CallableInvoker\CallableInvoker;
use OpenSolid\CallableInvoker\CallableInvokerInterface;
use OpenSolid\CallableInvoker\Decorator\FunctionDecoratorChain;
use OpenSolid\CallableInvoker\Decorator\FunctionDecoratorInterface;
use OpenSolid\CallableInvoker\ValueResolver\DefaultValueParameterValueResolver;
use OpenSolid\CallableInvoker\ValueResolver\UnsupportedParameterValueResolver;
use OpenSolid\CallableInvoker\ValueResolver\NullableParameterValueResolver;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolverChain;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolverInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\abstract_arg;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->instanceof(FunctionDecoratorInterface::class)
        ->tag('callable_invoker.decorator');

    $services->instanceof(ParameterValueResolverInterface::class)
        ->tag('callable_invoker.value_resolver');

    $services->set('callable_invoker.value_resolver.unsupported', UnsupportedParameterValueResolver::class)
        ->tag('callable_invoker.value_resolver', ['priority' => 100]);
    $services->set('callable_invoker.value_resolver.default_value', DefaultValueParameterValueResolver::class)
        ->tag('callable_invoker.value_resolver', ['priority' => -100]);
    $services->set('callable_invoker.value_resolver.nullable', NullableParameterValueResolver::class)
        ->tag('callable_invoker.value_resolver', ['priority' => -200]);

    $services->set('callable_invoker.decorator_chain', FunctionDecoratorChain::class)
        ->args([abstract_arg('groups of decorators')]);

    $services->set('callable_invoker.value_resolver_chain', ParameterValueResolverChain::class)
        ->args([abstract_arg('groups of value resolvers')]);

    $services->set('callable_invoker', CallableInvoker::class)
        ->args([
            service('callable_invoker.decorator_chain'),
            service('callable_invoker.value_resolver_chain'),
        ]);

    $services->alias(CallableInvokerInterface::class, 'callable_invoker')->public();
    $services->alias(FunctionDecoratorInterface::class, 'callable_invoker.decorator_chain')->public();
    $services->alias(ParameterValueResolverInterface::class, 'callable_invoker.value_resolver_chain')->public();
};
