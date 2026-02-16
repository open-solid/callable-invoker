<?php

namespace OpenSolid\CallableInvoker\Tests\Unit\DependencyInjection\Compiler;

use OpenSolid\CallableInvoker\CallableServiceLocator;
use OpenSolid\CallableInvoker\Decorator\FunctionDecoratorInterface;
use OpenSolid\CallableInvoker\DependecyInjection\Compiler\FunctionDecoratorPass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

final class FunctionDecoratorPassTest extends AbstractGroupingPassTest
{
    protected function createPass(): CompilerPassInterface
    {
        return new FunctionDecoratorPass();
    }

    protected function getChainServiceId(): string
    {
        return 'callable_invoker.decorator_groups';
    }

    protected function getChainClass(): string
    {
        return CallableServiceLocator::class;
    }

    protected function getTaggedClass(): string
    {
        return FunctionDecoratorInterface::class;
    }

    protected function getTagName(): string
    {
        return 'callable_invoker.decorator';
    }
}
