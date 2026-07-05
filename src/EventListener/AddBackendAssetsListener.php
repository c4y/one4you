<?php

declare(strict_types=1);

namespace C4Y\One4you\EventListener;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;

#[AsEventListener]
final readonly class AddBackendAssetsListener
{
    public function __construct(
        private ScopeMatcher $scopeMatcher,
        private RouterInterface $router,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$this->scopeMatcher->isBackendMainRequest($event)) {
            return;
        }

        $GLOBALS['TL_CSS'][] = 'bundles/contaoone4you/backend/one4you-stylemanager.css|static';
        $GLOBALS['TL_CSS'][] = 'bundles/contaoone4you/backend/one4you-svg-picker.css|static';
        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/contaoone4you/backend/one4you-svg-picker.js|static';
        $GLOBALS['TL_HEAD'][] = '<script>window.One4YouSvgPickerConfig = '.json_encode([
            'endpoint' => $this->router->generate('one4you_svg_icons'),
        ], \JSON_THROW_ON_ERROR).';</script>';
    }
}
