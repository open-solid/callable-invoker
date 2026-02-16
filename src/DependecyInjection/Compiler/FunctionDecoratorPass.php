<?php

declare(strict_types=1);

namespace OpenSolid\CallableInvoker\DependecyInjection\Compiler;

final readonly class FunctionDecoratorPass extends AbstractGroupingPass
{
    public function __construct()
    {
        parent::__construct('callable_invoker.decorator_groups', 'callable_invoker.decorator', ['callable_invoker.decorator_chain']);
    }
}
