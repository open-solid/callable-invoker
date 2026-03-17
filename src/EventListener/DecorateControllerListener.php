<?php

namespace OpenSolid\CallableInvoker\EventListener;

use OpenSolid\CallableInvoker\CallableDecoratorProviderInterface;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;

final readonly class DecorateControllerListener
{
    public function __construct(
        private CallableDecoratorProviderInterface $decorator,
    ) {
    }

    public function __invoke(ControllerArgumentsEvent $event): void
    {
        $event->setController($this->decorator->decorate(
            callable: $event->getController(),
            context: ['event' => $event],
            groups: ['kernel.controller'],
        ));
    }
}
