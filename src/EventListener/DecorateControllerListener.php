<?php

namespace OpenSolid\CallableInvoker\EventListener;

use OpenSolid\CallableInvoker\CallableDecoratorProviderInterface;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Event\ControllerAttributeEvent;

final readonly class DecorateControllerListener
{
    public function __construct(
        private CallableDecoratorProviderInterface $decorator,
    ) {
    }

    public function __invoke(ControllerArgumentsEvent $event): void
    {
        $controller = $this->decorator->decorate(
            callable: $event->getController(),
            context: ['event' => $event, 'request' => $event->getRequest()],
            groups: ['kernel.controller'],
        );

        // The decorated closure has no attributes of its own. Keep the attributes
        // of the original controller, so that attribute listeners (#[IsGranted],
        // #[Cache], #[RateLimit], ...) still apply to it.
        if (self::supportsFlatAttributes()) {
            $event->setController($controller, $event->getAttributes('*'));
        } else {
            $event->setController($controller, $event->getAttributes());
        }
    }

    /**
     * Symfony 8.1+ returns a flat list for getAttributes('*') and deprecates
     * grouped attributes in setController(). Earlier versions expect the grouped array.
     */
    private static function supportsFlatAttributes(): bool
    {
        return class_exists(ControllerAttributeEvent::class);
    }
}
