<?php

namespace C4Y\One4you\EventListener;

use Contao\Template;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\StringUtil;
use Contao\ContentModel;

/**
 * @Hook("parseTemplate")
 */
final class TemplateListener
{
    /**
     * On parse the template
     *
     * @param Template $template
     */
    public function __invoke(Template $template): void
    {
        $this->ensureStyleManager($template);
        $this->handleElementTemplate($template);
    }

    private function ensureStyleManager(Template $template): void
    {
        if (\is_object($template->styleManager) && method_exists($template->styleManager, 'prepare')) {
            return;
        }

        $template->styleManager = new class {
            public function prepare(string|int $identifier, ?array $groups = null): self
            {
                return $this;
            }

            public function format(string $format, string $method = ''): string
            {
                return '';
            }

            public function get(array|string|int $identifier, mixed $groups = null): string
            {
                return '';
            }
        };
    }

    /**
     * Handle the content element template
     *
     * @param Template $template
     */
    private function handleElementTemplate(Template $template)
    {
        return;
        /*
        $element = ContentModel::findById($template->id);
        $parent = ContentModel::findOneBy(['sorting < ? AND pid = ? AND invisible=""'], [$element->sorting, $template->pid]);

        switch(TL_MODE) {
            case 'FE': $this->handleFrontend($template); break;
            case 'BE': $this->handleBackend($template); break;
        }
        */


        //$template->showBackgroundImage = $parent->showBackgroundImage;
    }

    protected function handleFrontend(Template $template)
    {
        if((stripos($template->getName(), 'ce_') !== 0 && stripos($template->getName(), 'rsce_') !== 0)
            //|| $parent == null
            || count(($filters = StringUtil::deserialize($template->filterElements, true))) < 1) {
            return;
        }

        $classes = [];
        foreach ($filters as $filter) {
            $classes[] = 'elements-filter-'.$filter;
        }
        $template->class = trim($template->class.' elements-filter '.implode(' ', $classes));
    }

    protected function handleBackend()
    {

    }
}
