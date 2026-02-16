# CallableInvoker

A lightweight PHP callable invoker with smart parameter resolution and execution decoration.

## Installation

```console
$ composer require open-solid/callable-invoker
```

## Usage

The callable invoker accepts any PHP callable — closures, invokable objects, static methods, etc. — and handles the full execution lifecycle:

1. **Resolve** parameters automatically from a context array, default values, or nullability
2. **Decorate** the callable with optional layers (logging, validation, caching, etc.)
3. **Execute** with the resolved arguments and return the result

```php
use OpenSolid\CallableInvoker\CallableInvoker;

class HelloHandler
{
    public function __invoke(string $name, int $age = 30): string
    {
        return "Hello, $name! You are $age years old.";
    }
}

$handler = new HelloHandler();
$invoker = new CallableInvoker();
$result = $invoker->invoke(callable: $handler, context: ['name' => 'Alice']);

echo $result; // Output: Hello, Alice! You are 30 years old.
```

## Features

- **Automatic Parameter Resolution**: Resolves callable parameters from a provided context array, default values, and nullability — no manual argument wiring needed.
- **Execution Decoration**: Wraps callables with nested decorators for cross-cutting concerns like logging, validation, caching, or timing. Each decorator can intercept, modify, or short-circuit the execution.
- **Grouping**: Organizes decorators and resolvers into named groups, allowing different callables to use different sets of decorators and resolvers. Multiple groups can be combined in a single invocation.
- **Priority Ordering**: Controls the execution order of decorators and resolvers via priority values, ensuring predictable behavior when multiple are registered.
- **Support for Any Callable**: Works with closures, invokable objects, static methods, named functions, and more.
- **Extensible**: Register custom parameter value resolvers (`ParameterValueResolverInterface`) and decorators (`CallableDecoratorInterface`) to extend the invoker's behavior.
- **Symfony Integration**: Ships as a Symfony bundle with autoconfiguration via interfaces and PHP attributes (`#[AsCallableDecorator]`, `#[AsParameterValueResolver]`), service tagging, and compiler passes.
- **Clear Error Handling**: Provides specific exceptions for untyped parameters, variadic parameters, unsupported callables, and unresolvable parameters.
