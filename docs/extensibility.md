# Extensibility

The invoker is designed around two extension points: parameter value resolvers and callable decorators. Both follow the same pattern — implement an interface, register the service, and the invoker picks it up automatically.

## Custom Parameter Value Resolver

Implement `ParameterValueResolverInterface` to add custom resolution logic:

```php
use OpenSolid\CallableInvoker\CallableMetadata;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolverInterface;

class CurrentUserResolver implements ParameterValueResolverInterface
{
    public function __construct(
        private UserProviderInterface $users,
    ) {
    }

    public function supports(\ReflectionParameter $parameter, CallableMetadata $metadata): bool
    {
        return User::class === $parameter->getType()?->getName();
    }

    public function resolve(\ReflectionParameter $parameter, CallableMetadata $metadata): User
    {
        return $this->users->getCurrentUser();
    }
}
```

### Skipping to the Next Resolver

If a resolver matches in `supports()` but cannot resolve at runtime, throw `SkipParameterException` to pass control to the next resolver in the chain:

```php
use OpenSolid\CallableInvoker\Exception\SkipParameterException;

public function resolve(\ReflectionParameter $parameter, CallableMetadata $metadata): mixed
{
    $value = $this->tryResolve($parameter);

    if (null === $value) {
        throw new SkipParameterException();
    }

    return $value;
}
```

## Custom Callable Decorator

Implement `CallableDecoratorInterface` to wrap callable execution:

```php
use OpenSolid\CallableInvoker\CallableMetadata;
use OpenSolid\CallableInvoker\Decorator\CallableClosure;
use OpenSolid\CallableInvoker\Decorator\CallableDecoratorInterface;

class TransactionalDecorator implements CallableDecoratorInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function supports(CallableMetadata $metadata): bool
    {
        return !empty($metadata->function->getAttributes(Transactional::class));
    }

    public function decorate(CallableClosure $callable, CallableMetadata $metadata): mixed
    {
        $this->connection->beginTransaction();

        try {
            $result = $callable->call();
            $this->connection->commit();

            return $result;
        } catch (\Throwable $e) {
            $this->connection->rollBack();

            throw $e;
        }
    }
}
```

## Using CallableMetadata Attributes

`CallableMetadata` provides an extensible attribute store that decorators and resolvers can use to share data during a single invocation:

```php
// In a decorator: compute and cache expensive data
public function decorate(CallableClosure $callable, CallableMetadata $metadata): mixed
{
    $permissions = $metadata->getAttribute('permissions', fn () => $this->loadPermissions($metadata));

    // ...

    return $callable->call();
}
```

The `getAttribute()` method accepts a factory closure for lazy initialization. The value is cached for subsequent calls within the same invocation.

## Standalone Registration

Without Symfony, register custom services via `InMemoryCallableServiceLocator`:

```php
use OpenSolid\CallableInvoker\CallableInvoker;
use OpenSolid\CallableInvoker\Decorator\CallableDecorator;
use OpenSolid\CallableInvoker\InMemoryCallableServiceLocator;
use OpenSolid\CallableInvoker\ValueResolver\ParameterValueResolver;

$invoker = new CallableInvoker(
    decorator: new CallableDecorator(new InMemoryCallableServiceLocator([
        '__NONE__' => [new TransactionalDecorator($connection)],
    ])),
    valueResolver: new ParameterValueResolver(new InMemoryCallableServiceLocator([
        '__NONE__' => [
            new UnsupportedParameterValueResolver(),
            new CurrentUserResolver($users),
            new ContextParameterValueResolver(),
            new DefaultValueParameterValueResolver(),
            new NullableParameterValueResolver(),
        ],
    ])),
);
```

## Symfony Registration

With the bundle, services implementing the interfaces are auto-tagged. Use PHP attributes for group and priority configuration:

```php
use OpenSolid\CallableInvoker\Decorator\Attribute\AsCallableDecorator;
use OpenSolid\CallableInvoker\ValueResolver\Attribute\AsParameterValueResolver;

#[AsCallableDecorator('api', priority: 10)]
class TransactionalDecorator implements CallableDecoratorInterface { /* ... */ }

#[AsParameterValueResolver('api', priority: 50)]
class CurrentUserResolver implements ParameterValueResolverInterface { /* ... */ }
```

Or register manually via service tags:

```yaml
services:
    App\Resolver\CurrentUserResolver:
        tags:
            - { name: callable_invoker.value_resolver, groups: ['api'], priority: 50 }
```
