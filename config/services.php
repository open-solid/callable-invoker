<?php

use OpenSolid\CallableInvoker\CallableInvoker;
use OpenSolid\CallableInvoker\CallableInvokerInterface;
use OpenSolid\CallableInvoker\Decorator\FunctionDecoratorChain;
use OpenSolid\CallableInvoker\Decorator\FunctionDecoratorInterface;
use OpenSolid\CallableInvoker\ValueResolver\DefaultValueParameterValueResolver;
use OpenSolid\CallableInvoker\ValueResolver\NullableParameterValueResolver;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolverChain;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolverInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->instanceof(FunctionDecoratorInterface::class)
        ->tag('callable_invoker.decorator');

    $services->instanceof(ParameterValueResolverInterface::class)
        ->tag('callable_invoker.value_resolver');

    $services->set(DefaultValueParameterValueResolver::class);
    $services->set(NullableParameterValueResolver::class);

    $services->set(FunctionDecoratorChain::class)
        ->args([tagged_iterator('callable_invoker.decorator')]);

    $services->set(ParameterValueResolverChain::class)
        ->args([tagged_iterator('callable_invoker.value_resolver')]);

    $services->set(CallableInvoker::class)
        ->args([
            service(FunctionDecoratorChain::class),
            service(ParameterValueResolverChain::class),
        ]);

    $services->alias(CallableInvokerInterface::class, CallableInvoker::class);
    $services->alias(FunctionDecoratorInterface::class, FunctionDecoratorChain::class);
    $services->alias(ParameterValueResolverInterface::class, ParameterValueResolverChain::class);
};
