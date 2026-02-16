# Execution Decoration

Decorators wrap the callable execution with additional behavior. They form a nested chain where each decorator can run logic before and after the inner callable, modify the result, or short-circuit the execution entirely.

## How It Works

The decorator chain wraps the original closure layer by layer. When the decorated closure is finally called, execution flows through the outermost decorator inward:

```
Decorator 2 (outer)
    -> Decorator 1 (inner)
        -> Original callable
```

Each decorator receives a `CallableClosure` object that provides access to the inner callable and its resolved arguments.

## Implementing a Decorator

Implement `CallableDecoratorInterface`:

```php
use OpenSolid\CallableInvoker\CallableMetadata;
use OpenSolid\CallableInvoker\Decorator\CallableClosure;
use OpenSolid\CallableInvoker\Decorator\CallableDecoratorInterface;

class TimingDecorator implements CallableDecoratorInterface
{
    public function supports(CallableMetadata $metadata): bool
    {
        return true; // decorate all callables
    }

    public function decorate(CallableClosure $callable, CallableMetadata $metadata): mixed
    {
        $start = microtime(true);
        $result = $callable->call();
        $elapsed = microtime(true) - $start;

        echo sprintf("Executed in %.2fms\n", $elapsed * 1000);

        return $result;
    }
}
```

## The CallableClosure Object

`CallableClosure` wraps the inner closure and the resolved arguments:

- `$callable->call()` — executes the inner callable with its arguments and returns the result
- `$callable->closure` — the raw `\Closure` instance
- `$callable->args` — the resolved arguments array

## Delegating vs Short-Circuiting

A decorator can delegate to the inner callable or skip it entirely:

```php
// Delegating: executes inner callable and wraps result
public function decorate(CallableClosure $callable, CallableMetadata $metadata): mixed
{
    return '[prefix] ' . $callable->call();
}

// Short-circuiting: returns early without executing inner callable
public function decorate(CallableClosure $callable, CallableMetadata $metadata): mixed
{
    if ($this->cache->has($key)) {
        return $this->cache->get($key);
    }

    return $callable->call();
}
```

## Conditional Decoration

The `supports()` method determines whether a decorator applies to a given callable. Use the `CallableMetadata` to inspect the callable's reflection, context, or groups:

```php
public function supports(CallableMetadata $metadata): bool
{
    // Only decorate callables defined in a specific class
    return MyHandler::class === $metadata->function->getClosureScopeClass()?->getName();
}
```

## Standalone Usage

Without Symfony, configure decorators manually:

```php
use OpenSolid\CallableInvoker\CallableInvoker;
use OpenSolid\CallableInvoker\Decorator\CallableDecorator;
use OpenSolid\CallableInvoker\InMemoryCallableServiceLocator;

$invoker = new CallableInvoker(
    decorator: new CallableDecorator(new InMemoryCallableServiceLocator([
        '__NONE__' => [new TimingDecorator(), new LoggingDecorator()],
    ])),
);

$result = $invoker->invoke(fn () => 'hello');
```
