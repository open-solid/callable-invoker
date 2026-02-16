# Error Handling

The library provides a hierarchy of specific exceptions so you can catch and handle different failure modes precisely.

## Exception Hierarchy

```
InvalidArgumentException
  +-- CallableNotSupportedException
  +-- ParameterNotSupportedException
        +-- UntypedParameterNotSupportedException
        +-- VariadicParameterNotSupportedException

RuntimeException
  +-- SkipParameterException (internal, not thrown to user code)
```

## ParameterNotSupportedException

Thrown when no resolver in the chain can handle a parameter:

```php
use OpenSolid\CallableInvoker\Exception\ParameterNotSupportedException;

try {
    $invoker->invoke(fn (SomeService $service) => $service->run());
} catch (ParameterNotSupportedException $e) {
    // "Could not resolve value for parameter "$service"."
}
```

## UntypedParameterNotSupportedException

Thrown for parameters without a type declaration:

```php
use OpenSolid\CallableInvoker\Exception\UntypedParameterNotSupportedException;

try {
    $invoker->invoke(fn ($name) => $name);
} catch (UntypedParameterNotSupportedException $e) {
    // "Untyped parameter "$name" is not supported in "MyClass::myMethod"."
}
```

## VariadicParameterNotSupportedException

Thrown for variadic parameters:

```php
use OpenSolid\CallableInvoker\Exception\VariadicParameterNotSupportedException;

try {
    $invoker->invoke(fn (string ...$names) => implode(', ', $names));
} catch (VariadicParameterNotSupportedException $e) {
    // "Variadic parameter "$names" is not supported in "MyClass::myMethod"."
}
```

## CallableNotSupportedException

Thrown by decorators when a callable cannot be decorated:

```php
use OpenSolid\CallableInvoker\Exception\CallableNotSupportedException;

try {
    $invoker->invoke($unsupportedCallable);
} catch (CallableNotSupportedException $e) {
    // "Callable "MyClass::myMethod" is not supported."
}
```

## SkipParameterException

This is an internal control flow exception used within the resolver chain. When a resolver matches in `supports()` but cannot resolve at runtime, it throws `SkipParameterException` to pass control to the next resolver. This exception is caught by the chain and never reaches user code.

```php
use OpenSolid\CallableInvoker\Exception\SkipParameterException;

class ConditionalResolver implements ParameterValueResolverInterface
{
    public function supports(\ReflectionParameter $parameter, CallableMetadata $metadata): bool
    {
        return Config::class === $parameter->getType()?->getName();
    }

    public function resolve(\ReflectionParameter $parameter, CallableMetadata $metadata): mixed
    {
        $config = $this->tryLoad($parameter->getName());

        if (null === $config) {
            throw new SkipParameterException(); // let the next resolver try
        }

        return $config;
    }
}
```

## Catching Specific vs General Exceptions

Since `UntypedParameterNotSupportedException` and `VariadicParameterNotSupportedException` extend `ParameterNotSupportedException`, you can catch at different levels:

```php
try {
    $invoker->invoke($callable, $context);
} catch (UntypedParameterNotSupportedException $e) {
    // Handle untyped parameter specifically
} catch (VariadicParameterNotSupportedException $e) {
    // Handle variadic parameter specifically
} catch (ParameterNotSupportedException $e) {
    // Handle any unresolvable parameter
}
```
