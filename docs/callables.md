# Support for Any Callable

The invoker accepts any PHP callable type.

## Supported Callable Types

### Closures

```php
$invoker->invoke(fn (string $name) => "Hello, $name!", ['name' => 'World']);
```

### Invokable Objects

```php
class GreetHandler
{
    public function __invoke(string $name): string
    {
        return "Hello, $name!";
    }
}

$invoker->invoke(new GreetHandler(), ['name' => 'World']);
```

### Static Methods

```php
class MathHelper
{
    public static function add(int $a, int $b): int
    {
        return $a + $b;
    }
}

$invoker->invoke(MathHelper::add(...), ['a' => 1, 'b' => 2]);
```

### Instance Methods

```php
class Formatter
{
    public function format(string $text): string
    {
        return strtoupper($text);
    }
}

$invoker->invoke(new Formatter()->format(...), ['text' => 'hello']);
```

### Named Functions

```php
function greet(string $name): string
{
    return "Hello, $name!";
}

$invoker->invoke(greet(...), ['name' => 'World']);
```

## Limitations

The following parameter types are **not supported** and will throw specific exceptions:

- **Untyped parameters** — all parameters must have a type declaration
- **Variadic parameters** — `...$args` is not supported
