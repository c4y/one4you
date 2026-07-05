<?php

declare(strict_types=1);

namespace C4Y\One4you\EventListener;

use C4Y\One4you\Style\StyleManagerClassProcessor;
use Contao\ContentModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Contao\FormModel;
use Contao\FrontendTemplate;
use Contao\Module;
use Contao\ModuleModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Template;
use Doctrine\DBAL\Connection;

final class StyleManagerRenderClassListener
{
    public function __construct(
        private readonly Connection $connection,
        private readonly StyleManagerClassProcessor $classProcessor,
    ) {
    }

    #[AsHook('parseTemplate', priority: 100)]
    public function onParseTemplate(Template $template): void
    {
        if (!$template instanceof FrontendTemplate) {
            return;
        }

        $name = $template->getName();

        if ($name === 'fe_page' || str_starts_with($name, 'fe_page_')) {
            $this->addPageClasses($template);

            return;
        }

        if ($name !== 'mod_article' && !str_starts_with($name, 'mod_article_')) {
            return;
        }

        $id = (int) ($template->id ?? 0);
        $record = $id > 0 ? $this->fetchRecord('tl_article', $id) : null;

        if (!$record) {
            return;
        }

        $template->class = $this->classProcessor->renderClassList('tl_article', $record, (string) ($template->class ?? ''));
    }

    #[AsHook('getContentElement', priority: 100)]
    public function onGetContentElement(ContentModel $model, string $buffer, object $element): string
    {
        if ($model->type === 'text') {
            $buffer = $this->appendContentButtons($model, $buffer);
        }

        if ($model->type === 'form' && (int) ($model->form ?? 0) > 0) {
            $formRecord = $this->fetchRecord('tl_form', (int) $model->form);

            if ($formRecord) {
                $buffer = $this->addClassesToFirstElement('tl_form', $formRecord, $buffer);
            }
        }

        return $this->addClassListToFirstElement(
            $this->classProcessor->renderClassList('tl_content', $model->row()),
            $buffer
        );
    }

    public static function renderButtons(
        mixed $buttons,
        ?int $maxItems = 2,
        string $wrapperClass = 'd-flex flex-wrap-wrap align-items-center gap-column-md gap-row-md',
    ): string
    {
        $buttons = StringUtil::deserialize($buttons, true);
        $items = [];

        foreach ($buttons as $button) {
            if (\is_object($button)) {
                $button = get_object_vars($button);
            }

            if (!\is_array($button)) {
                continue;
            }

            $label = trim((string) ($button['label'] ?? $button['text'] ?? ''));
            $url = trim(System::getContainer()->get('contao.insert_tag.parser')->replaceInline((string) ($button['url'] ?? '')));

            if ($label === '' || $url === '') {
                continue;
            }

            $items[] = [
                'label' => $label,
                'url' => $url,
                'target' => !empty($button['target']),
                'class' => self::buttonClass($button),
            ];

            if ($maxItems !== null && \count($items) === $maxItems) {
                break;
            }
        }

        if ($items === []) {
            return '';
        }

        $html = sprintf(
            '<div class="%s">',
            htmlspecialchars($wrapperClass, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8')
        );

        foreach ($items as $item) {
            $target = $item['target'] ? ' target="_blank" rel="noopener"' : '';
            $html .= sprintf(
                '<a class="%s" href="%s"%s>%s</a>',
                htmlspecialchars($item['class'], \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($item['url'], \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8'),
                $target,
                htmlspecialchars($item['label'], \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8')
            );
        }

        return $html.'</div>';
    }

    private function appendContentButtons(ContentModel $model, string $buffer): string
    {
        $buttons = self::renderButtons($model->contentButtons ?? null);

        if ($buttons === '') {
            return $buffer;
        }

        if (str_contains($buffer, '</div>')) {
            return preg_replace('/<\/div>\s*$/', $buttons.'</div>', $buffer, 1) ?? $buffer.$buttons;
        }

        return $buffer.$buttons;
    }

    private static function buttonClass(array $button): string
    {
        $colors = ['btn-primary', 'btn-secondary', 'btn-accent'];
        $variants = ['btn-outline', 'btn-soft', 'btn-ghost', 'btn-text'];
        $classes = ['btn'];
        $legacyVariant = (string) ($button['variante'] ?? '');
        $color = (string) ($button['color'] ?? self::legacyButtonColor($legacyVariant));
        $variant = (string) ($button['variant'] ?? self::legacyButtonVariant($legacyVariant));

        $classes[] = \in_array($color, $colors, true) ? $color : 'btn-primary';

        if (\in_array($variant, $variants, true)) {
            $classes[] = $variant;
        }

        return implode(' ', $classes);
    }

    private static function legacyButtonColor(string $classes): string
    {
        return str_contains($classes, 'btn-secondary') ? 'btn-secondary' : 'btn-primary';
    }

    private static function legacyButtonVariant(string $classes): string
    {
        return $classes === 'link' ? 'btn-text' : '';
    }

    #[AsHook('getFrontendModule', priority: 100)]
    public function onGetFrontendModule(ModuleModel $model, string $buffer, object $module): string
    {
        if ($model->type === 'form' && (int) ($model->form ?? 0) > 0) {
            $formRecord = $this->fetchRecord('tl_form', (int) $model->form);

            if ($formRecord) {
                $buffer = $this->addClassesToFirstElement('tl_form', $formRecord, $buffer);
            }
        }

        return $this->addClassesToFirstElement('tl_module', $model->row(), $buffer);
    }

    #[AsHook('getForm', priority: 100)]
    public function onGetForm(FormModel $model, string $buffer, Form $form): string
    {
        return $this->addClassesToFirstElement('tl_form', $model->row(), $buffer);
    }

    #[AsHook('parseArticles', priority: 100)]
    public function onParseArticles(FrontendTemplate $template, array $newsEntry, Module $module): void
    {
        $template->class = $this->classProcessor->renderClassList('tl_news', $newsEntry, (string) ($template->class ?? ''));
    }

    #[AsHook('compileFormFields', priority: 100)]
    public function onCompileFormFields(array $fields, string $formId, Form $form): array
    {
        foreach ($fields as $field) {
            if (!\is_object($field) || !method_exists($field, 'row')) {
                continue;
            }

            $field->class = $this->classProcessor->renderClassList('tl_form_field', $field->row(), (string) ($field->class ?? ''));
        }

        return $fields;
    }

    private function addPageClasses(FrontendTemplate $template): void
    {
        $classList = html_entity_decode((string) ($template->class ?? ''), \ENT_QUOTES | \ENT_SUBSTITUTE | \ENT_HTML5, 'UTF-8');
        $page = $GLOBALS['objPage'] ?? null;

        if (\is_object($page) && method_exists($page, 'row')) {
            $classList = $this->classProcessor->renderClassList('tl_page', $page->row(), $classList);
        }

        $layout = $template->layout ?? null;

        if (\is_object($layout) && method_exists($layout, 'row')) {
            $classList = $this->classProcessor->renderClassList('tl_layout', $layout->row(), $classList);
        }

        $template->class = $classList;
    }

    private function addClassesToFirstElement(string $table, array $record, string $buffer): string
    {
        return $this->addClassListToFirstElement($this->classProcessor->renderClassList($table, $record), $buffer);
    }

    private function addClassListToFirstElement(string $classList, string $buffer): string
    {
        return $this->addClassListToElement('[a-z][a-z0-9:-]*', $classList, $buffer);
    }

    private function addClassListToElement(string $tagPattern, string $classList, string $buffer): string
    {
        if ($classList === '' || trim($buffer) === '') {
            return $buffer;
        }

        return preg_replace_callback(
            '/<('.$tagPattern.')(?![a-z0-9:-])([^<>]*?)>/i',
            static function (array $matches) use ($classList): string {
                $tag = $matches[1];
                $attributes = $matches[2] ?? '';

                if (preg_match('/\sclass=(["\'])(.*?)\1/si', $attributes, $classMatch, \PREG_OFFSET_CAPTURE)) {
                    $existingClasses = html_entity_decode($classMatch[2][0], \ENT_QUOTES | \ENT_SUBSTITUTE | \ENT_HTML5, 'UTF-8');
                    $classes = self::mergeClassLists($existingClasses, $classList);
                    $replacement = ' class='.$classMatch[1][0].htmlspecialchars($classes, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8').$classMatch[1][0];

                    $attributes = substr_replace(
                        $attributes,
                        $replacement,
                        $classMatch[0][1],
                        \strlen($classMatch[0][0])
                    );

                    return '<'.$tag.$attributes.'>';
                }

                return '<'.$tag.$attributes.' class="'.htmlspecialchars($classList, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8').'">';
            },
            $buffer,
            1
        ) ?? $buffer;
    }

    private static function mergeClassLists(string ...$classLists): string
    {
        $classes = [];

        foreach ($classLists as $classList) {
            foreach (preg_split('/\s+/', trim($classList)) ?: [] as $class) {
                if ($class !== '') {
                    $classes[$class] = true;
                }
            }
        }

        return implode(' ', array_keys($classes));
    }

    private function fetchRecord(string $table, int $id): ?array
    {
        $record = $this->connection->fetchAssociative(sprintf('SELECT * FROM %s WHERE id=?', $table), [$id]);

        return $record ?: null;
    }
}
