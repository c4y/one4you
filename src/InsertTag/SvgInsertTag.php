<?php

declare(strict_types=1);

namespace C4Y\One4you\InsertTag;

use C4Y\One4you\Svg\SvgIconProvider;
use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\CoreBundle\InsertTag\Resolver\InsertTagResolverNestedResolvedInterface;

#[AsInsertTag('svg')]
final readonly class SvgInsertTag implements InsertTagResolverNestedResolvedInterface
{
    public function __construct(private SvgIconProvider $svgIconProvider)
    {
    }

    public function __invoke(ResolvedInsertTag $insertTag): InsertTagResult
    {
        return new InsertTagResult(
            $this->svgIconProvider->getInlineSvg((string) ($insertTag->getParameters()->get(0) ?? '')),
            OutputType::html,
        );
    }
}
