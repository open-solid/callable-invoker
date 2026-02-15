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
