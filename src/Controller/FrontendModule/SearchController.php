<?php

declare(strict_types=1);

namespace C4Y\One4you\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\ModuleModel;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(type: 'one4you_search', category: 'one4you', template: 'frontend_module/one4you_search')]
final class SearchController extends AbstractFrontendModuleController
{
    private const ICON_INSERT_TAG = '{{svg::outline:search}}';

    public function __construct(private readonly InsertTagParser $insertTagParser)
    {
    }

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $template->set('action', $this->getAction($model, $request));
        $template->set('input_id', 'one4you-search-keywords-'.$model->id);
        $template->set('keyword', (string) $request->query->get('keywords', ''));
        $template->set('placeholder', $this->getText($model->one4youSearchPlaceholder, 'Suche'));
        $template->set('label', $this->getText($model->one4youSearchLabel, 'Webseite durchsuchen'));
        $template->set('open_label', $this->getText($model->one4youSearchOpenLabel, 'Suche öffnen'));
        $template->set('submit_label', $this->getText($model->one4youSearchSubmitLabel, 'Suche absenden'));
        $template->set('search_icon', $this->insertTagParser->replaceInline(self::ICON_INSERT_TAG));

        return $template->getResponse();
    }

    private function getAction(ModuleModel $model, Request $request): string
    {
        if ($model->jumpTo && null !== ($page = PageModel::findById($model->jumpTo))) {
            return $page->getFrontendUrl();
        }

        return $request->getPathInfo() ?: '/';
    }

    private function getText(mixed $value, string $fallback): string
    {
        $value = trim((string) $value);

        return '' !== $value ? $value : $fallback;
    }
}
