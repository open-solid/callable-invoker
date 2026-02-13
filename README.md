# CallableInvoker

A lightweight PHP callable invoker with smart parameter resolution and execution decoration.

A callable invoker is a component whose job is to:
1.	Take any PHP callable (function, closure, __invoke() object, etc.)
2.	Figure out what arguments it needs
3.	Resolve those arguments automatically (from context, defaults, nullables, etc.)
4.	Optionally wrap/decorate the callable (logging, validation, timing…)
5.	Execute it

## Installation

```console
$ composer require open-solid/callable-invoker
```

## TODO

- [ ] Add more examples and documentation
- [ ] Add support for grouping decorators and value resolvers (e.g. for controllers, commands, etc.)