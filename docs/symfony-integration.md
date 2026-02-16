# Symfony Integration

The library ships as a Symfony bundle that handles service registration, autoconfiguration, and group compilation automatically.

## Setup

Register the bundle in your application:

```php
// config/bundles.php
return [
    // ...
    OpenSolid\CallableInvoker\CallableInvokerBundle::class => ['all' => true],
];
```

## Injecting the Invoker

The bundle registers `CallableInvokerInterface` as a service. Inject it via constructor or method injection:

```php
use OpenSolid\CallableInvoker\CallableInvokerInterface;

class MyController
{
    public function __construct(
        private CallableInvokerInterface $invoker,
    ) {
    }

    public function __invoke(): Response
    {
        $result = $this->invoker->invoke($handler, $context);

        // ...
    }
}
```

## Autoconfiguration via Interfaces

Any service implementing `CallableDecoratorInterface` or `ParameterValueResolverInterface` is automatically tagged and registered in the default group:

```php
use OpenSolid\CallableInvoker\Decorator\CallableDecoratorInterface;

class LoggingDecorator implements CallableDecoratorInterface
{
    // Automatically tagged with 'callable_invoker.decorator'
}
```

## PHP Attributes

Use `#[AsCallableDecorator]` and `#[AsParameterValueResolver]` to configure groups and priority directly on the class:

```php
use OpenSolid\CallableInvoker\Decorator\Attribute\AsCallableDecorator;

#[AsCallableDecorator(['api', 'admin'], priority: 100)]
class AuthDecorator implements CallableDecoratorInterface
{
    // Registered in 'api' and 'admin' groups with priority 100
}
```

```php
use OpenSolid\CallableInvoker\ValueResolver\Attribute\AsParameterValueResolver;

#[AsParameterValueResolver('api', priority: 50)]
class RequestBodyResolver implements ParameterValueResolverInterface
{
    // Registered in 'api' group with priority 50
}
```

Both attributes accept a single group as a string or multiple groups as an array.

## Service Tags

You can also configure services manually via tags:

```yaml
services:
    App\Decorator\CacheDecorator:
        tags:
            - { name: callable_invoker.decorator, groups: ['api'], priority: 10 }

    App\Resolver\RequestBodyResolver:
        tags:
            - { name: callable_invoker.value_resolver, groups: ['api'], priority: 50 }
```

## Built-in Services

The bundle registers four default value resolvers:

| Service ID                                      | Class                                | Priority |
|-------------------------------------------------|--------------------------------------|----------|
| `callable_invoker.value_resolver.unsupported`   | `UnsupportedParameterValueResolver`  | `100`    |
| `callable_invoker.value_resolver.context`       | `ContextParameterValueResolver`      | `-100`   |
| `callable_invoker.value_resolver.default_value` | `DefaultValueParameterValueResolver` | `-200`   |
| `callable_invoker.value_resolver.nullable`      | `NullableParameterValueResolver`     | `-300`   |

These are ungrouped, so they are available in the default group **and** automatically included in all explicit groups.
