<?php

namespace OpenSolid\CallableInvoker\Tests\Unit\DependencyInjection\Compiler;

use OpenSolid\CallableInvoker\CallableServiceLocator;
use OpenSolid\CallableInvoker\Decorator\FunctionDecoratorInterface;

final class FunctionDecoratorPassTest extends AbstractGroupingPassTest
{
    protected function getChainServiceId(): string
    {
        return 'callable_invoker.decorator_chain';
    }

    protected function getLocatorServiceId(): string
    {
        return 'callable_invoker.decorator_groups';
    }

    protected function getLocatorClass(): string
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
