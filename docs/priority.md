# Priority Ordering

Priority controls the execution order of decorators and resolvers within a group. Higher priority values run first.

## How It Works

Each decorator or resolver can declare a priority (default `0`). Within the same group, services are sorted by priority in descending order before execution.

For **resolvers**, this determines which resolver gets the first chance to handle a parameter. For **decorators**, this determines the nesting order (higher priority = closer to the original callable).

## Built-in Resolver Priorities

The default resolvers are ordered by priority:

| Priority | Resolver                             | Purpose                          |
|----------|--------------------------------------|----------------------------------|
| `100`    | `UnsupportedParameterValueResolver`  | Rejects unsupported params early |
| `-100`   | `ContextParameterValueResolver`      | Resolves from context array      |
| `-200`   | `DefaultValueParameterValueResolver` | Uses parameter defaults          |
| `-300`   | `NullableParameterValueResolver`     | Falls back to `null`             |

This ensures unsupported parameters (variadic, untyped) are rejected immediately, context values take precedence over defaults, and `null` is the last resort.

## Setting Priority

### Via PHP attributes (Symfony)

```php
use OpenSolid\CallableInvoker\Decorator\Attribute\AsCallableDecorator;
use OpenSolid\CallableInvoker\ValueResolver\Attribute\AsParameterValueResolver;

#[AsCallableDecorator('api', priority: 100)]
class AuthDecorator implements CallableDecoratorInterface
{
    // Runs before lower-priority decorators
}

#[AsParameterValueResolver('api', priority: 50)]
class RequestBodyResolver implements ParameterValueResolverInterface
{
    // Checked before default resolvers (priority 0 and below)
}
```

### Via service tags (Symfony)

```yaml
services:
    App\Decorator\CacheDecorator:
        tags:
            - { name: callable_invoker.decorator, groups: ['api'], priority: -10 }
```

## Decorator Nesting Order

Decorators wrap the callable from highest priority (innermost) to lowest priority (outermost). When executed, the flow is from outermost to innermost:

```
Priority -10 (outer) -> Priority 0 (middle) -> Priority 100 (inner) -> Original callable
```

This means a decorator with **lower priority wraps those with higher priority**, giving it control over the entire execution including higher-priority decorators.
