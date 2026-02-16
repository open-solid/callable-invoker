<?php

namespace OpenSolid\CallableInvoker\Tests\Unit\DependencyInjection\Compiler;

use OpenSolid\CallableInvoker\CallableServiceLocator;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolverInterface;

final class ParameterValueResolverPassTest extends AbstractGroupingPassTest
{
    protected function getChainServiceId(): string
    {
        return 'callable_invoker.value_resolver_chain';
    }

    protected function getLocatorServiceId(): string
    {
        return 'callable_invoker.value_resolver_groups';
    }

    protected function getLocatorClass(): string
    {
        return CallableServiceLocator::class;
    }

    protected function getTaggedClass(): string
    {
        return ParameterValueResolverInterface::class;
    }

    protected function getTagName(): string
    {
        return 'callable_invoker.value_resolver';
    }
}
