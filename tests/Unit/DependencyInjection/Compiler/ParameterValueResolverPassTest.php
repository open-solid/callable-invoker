<?php

namespace OpenSolid\CallableInvoker\Tests\Unit\DependencyInjection\Compiler;

use OpenSolid\CallableInvoker\DependecyInjection\Compiler\ParameterValueResolverPass;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolverGroups;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolverInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

final class ParameterValueResolverPassTest extends AbstractGroupingPassTest
{
    protected function createPass(): CompilerPassInterface
    {
        return new ParameterValueResolverPass();
    }

    protected function getChainServiceId(): string
    {
        return 'callable_invoker.value_resolver_groups';
    }

    protected function getChainClass(): string
    {
        return ParameterValueResolverGroups::class;
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
