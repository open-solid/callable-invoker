# CallableInvoker

A lightweight PHP callable invoker with smart parameter resolution and execution decoration.

A callable invoker is a component whose job is to:

1. Take any PHP callable (function, closure, __invoke() object, etc.)
2. Figure out what arguments it needs and resolve those arguments automatically (from context, defaults, nullables, etc.)
3. Optionally wrap/decorate the callable (for logging, validation, caching, timing, etc.)
4. Execute it

## Installation

```console
$ composer require open-solid/callable-invoker
```

## Usage

```php
use OpenSolid\CallableInvoker\CallableInvoker;
use OpenSolid\CallableInvoker\Decorator\FunctionDecoratorChain;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolverChain;

$invoker = new CallableInvoker(
    new FunctionDecoratorChain(),
    new ParameterValueResolverChain(),
);

class HelloHandler
{
    public function __invoke(string $name, int $age = 30): string
    {
        return "Hello, $name! You are $age years old.";
    }
}

$handler = new HelloHandler();
$result = $invoker->invoke($handler, ['name' => 'Alice']);

echo $result; // Output: Hello, Alice! You are 30 years old.
```