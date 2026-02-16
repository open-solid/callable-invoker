<?php

declare(strict_types=1);

namespace OpenSolid\CallableInvoker\DependecyInjection\Compiler;

final readonly class ParameterValueResolverPass extends AbstractGroupingPass
{
    public function __construct()
    {
        parent::__construct('callable_invoker.value_resolver_groups', 'callable_invoker.value_resolver', ['callable_invoker.value_resolver_chain']);
    }
}
