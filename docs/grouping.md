# Grouping

Groups organize decorators and resolvers into named sets. Different callables can use different combinations of groups, giving you fine-grained control over which behavior applies to each invocation.

## How It Works

Each decorator and resolver can be assigned to one or more groups. When invoking a callable, you specify which groups to activate. Only decorators and resolvers registered in those groups will participate.

```php
$invoker->invoke(
    callable: $handler,
    context: ['name' => 'Alice'],
    groups: ['api', 'logging'],
);
```

## Default Group

When no groups are specified, the invoker uses the default group (`__NONE__`):

```php
// These two calls are equivalent
$invoker->invoke(fn () => 'hello');
$invoker->invoke(fn () => 'hello', groups: [CallableInvokerInterface::DEFAULT_GROUP]);
```

Services registered without an explicit group are placed in the default group **and** automatically included in all explicit groups. This ensures built-in resolvers (context, defaults, nullable) are always available.

## Assigning Groups

### Via PHP attributes (Symfony)

```php
use OpenSolid\CallableInvoker\Decorator\Attribute\AsCallableDecorator;

#[AsCallableDecorator(['api'])]
class ApiRateLimitDecorator implements CallableDecoratorInterface
{
    // ...
}

#[AsCallableDecorator(['api', 'admin'])]
class AuthDecorator implements CallableDecoratorInterface
{
    // ...
}
```

### Via service tags (Symfony)

```yaml
services:
    App\Decorator\CacheDecorator:
        tags:
            - { name: callable_invoker.decorator, groups: ['api'], priority: 10 }
```

### Via InMemoryCallableServiceLocator (standalone)

```php
$invoker = new CallableInvoker(
    decorator: new CallableDecorator(new InMemoryCallableServiceLocator([
        'api' => [new AuthDecorator(), new RateLimitDecorator()],
        'admin' => [new AuthDecorator(), new AuditDecorator()],
    ])),
);
```

## Combining Groups

Pass multiple groups to combine their decorators and resolvers. Services shared across groups are deduplicated automatically:

```php
// AuthDecorator is in both 'api' and 'admin' but will only execute once
$invoker->invoke($handler, groups: ['api', 'admin']);
```

## Example: Per-Context Decoration

Use groups to apply different decorators depending on the entry point:

```php
// HTTP controller — apply API-specific decorators
$invoker->invoke($controller, context: $request, groups: ['http']);

// CLI command — apply console-specific decorators
$invoker->invoke($command, context: $input, groups: ['console']);

// Message handler — apply async-specific decorators
$invoker->invoke($handler, context: $envelope, groups: ['messenger']);
```
