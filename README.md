# CallableInvoker

A lightweight PHP callable invoker with smart parameter resolution and execution decoration.

## Installation

```console
$ composer require open-solid/callable-invoker
```

## Usage

A callable invoker is a component whose job is to:

1. Take any PHP callable (function, closure, __invoke() object, etc.)
2. Figure out what arguments it needs and resolve those arguments automatically (from context, defaults, nullables, etc.)
3. Optionally wrap/decorate the callable (for logging, validation, caching, timing, etc.)
4. Execute it

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
