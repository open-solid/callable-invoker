# Automatic Parameter Resolution

The invoker inspects each parameter of the callable via reflection and resolves its value automatically. You provide a context array and the resolver chain does the rest.

## How It Works

When you call `invoke()`, the invoker iterates over the callable's parameters and tries each registered resolver in order until one succeeds:

```php
$invoker = new CallableInvoker();

$result = $invoker->invoke(
    callable: fn (?string $title, string $name, int $age = 25) => "$title $name, age $age",
    context: ['name' => 'Alice'],
);

// Result: " Alice, age 25"
```

For each parameter:
- `$title` — not in context, no default, but nullable so resolves to `null`
- `$name` — matched by name in the context array
- `$age` — not in context, falls back to its default value `25`

## Built-in Resolvers

The default resolver chain includes four resolvers, applied in this order:

| Resolver                             | Condition                        | Result                      |
|--------------------------------------|----------------------------------|-----------------------------|
| `UnsupportedParameterValueResolver`  | Variadic or untyped parameter    | Throws a specific exception |
| `ContextParameterValueResolver`      | Parameter name exists in context | Returns `$context[$name]`   |
| `DefaultValueParameterValueResolver` | Parameter has a default value    | Returns the default value   |
| `NullableParameterValueResolver`     | Parameter allows `null`          | Returns `null`              |

If no resolver handles the parameter, a `ParameterNotSupportedException` is thrown.

## Context Array

The context array maps parameter names to values:

```php
$invoker->invoke(
    callable: fn (string $greeting, string $name) => "$greeting, $name!",
    context: ['greeting' => 'Hello', 'name' => 'World'],
);

// Result: "Hello, World!"
```

Context values take precedence over default values:

```php
$invoker->invoke(
    callable: fn (string $name = 'World') => "Hello, $name!",
    context: ['name' => 'PHP'],
);

// Result: "Hello, PHP!"
```

## Default Values

Parameters with default values are resolved automatically when no context is provided:

```php
$invoker->invoke(
    callable: fn (string $name = 'World', int $count = 3) => str_repeat("Hello, $name! ", $count),
);

// Result: "Hello, World! Hello, World! Hello, World! "
```

## Nullable Parameters

Nullable parameters resolve to `null` as a last resort:

```php
$invoker->invoke(
    callable: fn (?string $name) => $name ?? 'anonymous',
);

// Result: "anonymous"
```
