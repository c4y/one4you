<?php

declare(strict_types=1);

namespace C4Y\One4you\Controller;

use C4Y\One4you\Svg\SvgIconProvider;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SvgIconController extends AbstractController
{
    public function __construct(
        private readonly ScopeMatcher $scopeMatcher,
        private readonly SvgIconProvider $svgIconProvider,
    ) {
    }

    #[Route('/contao/one4you/svg-icons', name: 'one4you_svg_icons', defaults: ['_scope' => 'backend'], methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        if (!$this->scopeMatcher->isBackendRequest($request)) {
            return new JsonResponse(null, Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse([
            'icons' => $this->svgIconProvider->getPickerIcons(),
        ]);
    }
}
