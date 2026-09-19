<?php

declare(strict_types=1);

namespace C4Y\One4you\Style;

final class StyleDefinitionRegistry
{
    public const FIELD_NAME = 'one4youStyleManager';

    public const TABLES = [
        'layouts' => 'tl_layout',
        'pages' => 'tl_page',
        'articles' => 'tl_article',
        'modules' => 'tl_module',
        'news' => 'tl_news',
        'events' => 'tl_calendar_events',
        'forms' => 'tl_form',
        'form_fields' => 'tl_form_field',
        'content_elements' => 'tl_content',
    ];

    public const TARGET_FIELDS = [
        'tl_layout' => 'cssClass',
        'tl_page' => 'cssClass',
        'tl_article' => 'cssID',
        'tl_module' => 'cssID',
        'tl_news' => 'cssClass',
        'tl_calendar_events' => 'cssClass',
        'tl_form' => 'attributes',
        'tl_form_field' => 'class',
        'tl_content' => 'cssID',
    ];

    public static function blocksForTable(string $table, ?array $record = null): array
    {
        $context = array_search($table, self::TABLES, true);

        if (!$context) {
            return [];
        }

        $blocks = [];

        foreach ((StyleConfigLoader::load()['blocks'] ?? []) as $blockAlias => $block) {
            $showIn = $block['show_in'] ?? [];
            $showIn = \is_array($showIn) ? $showIn : [$showIn];

            if (!\in_array($context, $showIn, true)) {
                continue;
            }

            if (!self::isVisibleForRecord($block, $table, $record)) {
                continue;
            }

            $block = self::normalizeBlock((string) $blockAlias, $block, $table, $record);

            if ($block['tabs'] === []) {
                continue;
            }

            $blocks[$blockAlias] = $block;
        }

        return $blocks;
    }

    public static function targetField(string $table): ?string
    {
        return self::TARGET_FIELDS[$table] ?? null;
    }

    public static function collectClasses(string $table, array $values, ?array $record = null): array
    {
        $classes = [];

        foreach (self::blocksForTable($table, $record) as $blockAlias => $block) {
            foreach ($block['tabs'] as $tabAlias => $tab) {
                foreach ($tab['fields'] as $fieldAlias => $field) {
                    $value = $values[$blockAlias][$tabAlias][$fieldAlias] ?? null;

                    foreach (self::classesForFieldValue($field, $value) as $class) {
                        $classes[$class] = true;
                    }
                }
            }
        }

        return array_keys($classes);
    }

    public static function managedClassMatchers(string $table, ?array $record = null): array
    {
        $exact = [];
        $patterns = [];

        foreach (self::blocksForTable($table, $record) as $block) {
            foreach ($block['tabs'] as $tab) {
                foreach ($tab['fields'] as $field) {
                    foreach (self::exactClassesForField($field) as $class) {
                        $exact[$class] = true;
                    }

                    foreach (self::classPatternsForField($field) as $pattern) {
                        $patterns[$pattern] = true;
                    }
                }
            }
        }

        return [
            'exact' => array_keys($exact),
            'patterns' => array_keys($patterns),
        ];
    }

    public static function filterValuesForTable(string $table, array $values, ?array $record = null): array
    {
        $filtered = [];

        foreach (self::blocksForTable($table, $record) as $blockAlias => $block) {
            foreach ($block['tabs'] as $tabAlias => $tab) {
                foreach ($tab['fields'] as $fieldAlias => $field) {
                    if (!isset($values[$blockAlias][$tabAlias][$fieldAlias])) {
                        continue;
                    }

                    $filtered[$blockAlias][$tabAlias][$fieldAlias] = $values[$blockAlias][$tabAlias][$fieldAlias];
                }
            }
        }

        return $filtered;
    }

    private static function normalizeBlock(string $alias, array $block, string $table, ?array $record): array
    {
        $tabs = [];

        foreach (($block['tabs'] ?? []) as $tabAlias => $tab) {
            if (!self::isVisibleForRecord($tab, $table, $record)) {
                continue;
            }

            $fields = [];

            foreach (($tab['fields'] ?? []) as $fieldAlias => $field) {
                if (!self::isVisibleForRecord($field, $table, $record)) {
                    continue;
                }

                $fields[$fieldAlias] = self::normalizeField((string) $fieldAlias, $field);
            }

            if ($fields === []) {
                continue;
            }

            $tabs[$tabAlias] = [
                'alias' => (string) $tabAlias,
                'label' => (string) ($tab['label'] ?? $tabAlias),
                'fields' => $fields,
            ];
        }

        return [
            'alias' => $alias,
            'label' => (string) ($block['label'] ?? $alias),
            'tabs' => $tabs,
        ];
    }

    private static function normalizeField(string $alias, array $field): array
    {
        $field['alias'] = $alias;
        $field['label'] = (string) ($field['label'] ?? $alias);
        $field['type'] = (string) ($field['type'] ?? 'select');
        $field['help'] = (string) ($field['help'] ?? '');
        $field['options'] = self::resolveOptions($field);
        $field['options_source'] = (string) ($field['options_source'] ?? '');
        $field['responsive'] = (bool) ($field['responsive'] ?? false);
        $field['breakpoints'] = $field['breakpoints'] ?? (StyleConfigLoader::load()['breakpoints'] ?? ['sm', 'md', 'lg', 'xl']);

        return $field;
    }

    private static function isVisibleForRecord(array $definition, string $table, ?array $record): bool
    {
        if (!isset($definition['show_for'])) {
            return true;
        }

        if ($record === null) {
            return true;
        }

        $rules = $definition['show_for'];

        if (!\is_array($rules)) {
            return true;
        }

        if (isset($rules[$table]) && \is_array($rules[$table])) {
            return self::recordMatchesRules($record, $rules[$table]);
        }

        if (self::containsFieldRules($rules)) {
            return self::recordMatchesRules($record, $rules);
        }

        return false;
    }

    private static function containsFieldRules(array $rules): bool
    {
        foreach (array_keys($rules) as $key) {
            $key = (string) $key;

            if (!\array_key_exists($key, self::TABLES) && !\in_array($key, self::TABLES, true)) {
                return true;
            }
        }

        return false;
    }

    private static function recordMatchesRules(array $record, array $rules): bool
    {
        foreach ($rules as $field => $expected) {
            $actual = (string) ($record[(string) $field] ?? '');
            $expectedValues = \is_array($expected) ? $expected : [$expected];
            $expectedValues = array_map('strval', $expectedValues);

            if (!\in_array($actual, $expectedValues, true)) {
                return false;
            }
        }

        return true;
    }

    private static function resolveOptions(array $field): array
    {
        $source = (string) ($field['options_source'] ?? '');

        if ($source !== '') {
            $field['options'] = self::optionsForSource($source);
        }

        $field['options'] = self::normalizeOptions($field);

        return $field['options'];
    }

    private static function optionsForSource(string $source): array
    {
        if ($source === 'theme_backgrounds') {
            $source = 'backgrounds';
        }

        $options = StyleConfigLoader::load()['option_sources'][$source] ?? [];

        return \is_array($options) ? $options : [];
    }

    private static function normalizeOptions(array $field): array
    {
        $options = $field['options'] ?? [];
        $normalized = [];

        if (!\is_array($options)) {
            return $normalized;
        }

        foreach ($options as $key => $option) {
            if (\is_array($option)) {
                $value = (string) ($option['value'] ?? $key);
                $classes = (string) ($option['classes'] ?? ($field['class_pattern'] ?? ''));

                $normalized[] = [
                    'value' => $value,
                    'label' => (string) ($option['label'] ?? $value),
                    'classes' => $classes !== '' ? $classes : $value,
                    'aliases' => array_values(array_filter(array_map('strval', (array) ($option['aliases'] ?? [])))),
                ];

                continue;
            }

            $normalized[] = [
                'value' => (string) $key,
                'label' => (string) $option,
                'classes' => (string) $key,
                'aliases' => [],
            ];
        }

        return $normalized;
    }

    private static function classesForFieldValue(array $field, mixed $value): array
    {
        $classes = [];

        if ($field['type'] === 'trbl') {
            return self::classesForTrblField($field, \is_array($value) ? $value : []);
        }

        foreach (self::normalizeSelectedValues($value, false) as $selected) {
            $classes = array_merge($classes, self::classesForOptionValue($field, $selected));
        }

        if (($field['responsive'] ?? false) && \is_array($value) && \is_array($value['responsive'] ?? null)) {
            foreach ($value['responsive'] as $breakpoint => $responsiveValue) {
                foreach (self::normalizeSelectedValues($responsiveValue, true) as $selected) {
                    $classes = array_merge($classes, self::classesForOptionValue($field, $selected, (string) $breakpoint));
                }
            }
        }

        return self::splitClasses(implode(' ', $classes));
    }

    private static function classesForOptionValue(array $field, string $selected, ?string $breakpoint = null): array
    {
        if ($selected === '') {
            return [];
        }

        $option = self::findOption($field, $selected);
        $classes = $option['classes'] ?? $selected;

        if ($breakpoint !== null && !empty($field['responsive_class_pattern'])) {
            $classes = (string) $field['responsive_class_pattern'];
        } elseif (!empty($field['class_pattern'])) {
            $classes = (string) $field['class_pattern'];
        }

        return self::splitClasses(self::replacePattern($classes, $selected, $breakpoint));
    }

    private static function classesForTrblField(array $field, array $value): array
    {
        $classes = [];
        $patterns = $field['class_patterns'] ?? [];

        foreach (['top', 'right', 'bottom', 'left'] as $side) {
            if (($value[$side] ?? '') !== '' && isset($patterns[$side])) {
                $classes[] = self::replacePattern((string) $patterns[$side], (string) $value[$side]);
            }
        }

        if (($field['responsive'] ?? false) && \is_array($value['responsive'] ?? null)) {
            foreach ($value['responsive'] as $breakpoint => $responsiveValues) {
                if (!\is_array($responsiveValues)) {
                    continue;
                }

                foreach (['top', 'right', 'bottom', 'left'] as $side) {
                    if (($responsiveValues[$side] ?? '') !== '' && isset($patterns[$side])) {
                        $classes[] = self::replacePattern((string) $patterns[$side], (string) $responsiveValues[$side], (string) $breakpoint);
                    }
                }
            }
        }

        return self::splitClasses(implode(' ', $classes));
    }

    private static function exactClassesForField(array $field): array
    {
        $classes = [];

        if (!empty($field['class_pattern']) || !empty($field['responsive_class_pattern']) || !empty($field['class_patterns'])) {
            return [];
        }

        foreach ($field['options'] as $option) {
            $classes = array_merge($classes, self::splitClasses($option['classes'] ?? ''));
        }

        return $classes;
    }

    private static function classPatternsForField(array $field): array
    {
        $patterns = [];

        foreach (['class_pattern', 'responsive_class_pattern'] as $key) {
            if (!empty($field[$key])) {
                foreach (self::splitClasses((string) $field[$key]) as $classPattern) {
                    $patterns[] = self::patternToRegex($classPattern, $field);
                }
            }
        }

        foreach (($field['class_patterns'] ?? []) as $classPattern) {
            foreach (self::splitClasses((string) $classPattern) as $pattern) {
                $patterns[] = self::patternToRegex($pattern, $field);
            }
        }

        return $patterns;
    }

    private static function findOption(array $field, string $value): ?array
    {
        foreach ($field['options'] as $option) {
            if ((string) $option['value'] === $value) {
                return $option;
            }
        }

        return null;
    }

    private static function normalizeSelectedValues(mixed $value, bool $responsive): array
    {
        if (\is_array($value)) {
            if (!$responsive && \array_key_exists('value', $value)) {
                return ($value['value'] ?? '') === '' ? [] : [(string) $value['value']];
            }

            if (!$responsive) {
                return [];
            }

            return array_values(array_filter(array_map('strval', $value), static fn (string $item): bool => $item !== '' && $item !== 'Array'));
        }

        return $value === null || $value === '' ? [] : [(string) $value];
    }

    private static function replacePattern(string $pattern, string $value, ?string $breakpoint = null): string
    {
        $breakpointValue = '';

        if ($breakpoint !== null) {
            $breakpointValue = str_contains($pattern, '{breakpoint}-') ? $breakpoint : $breakpoint.'-';
        }

        return str_replace(
            ['{value}', '{breakpoint}'],
            [$value, $breakpointValue],
            $pattern
        );
    }

    private static function patternToRegex(string $pattern, array $field): string
    {
        $quoted = preg_quote($pattern, '#');
        $breakpoints = implode('|', array_map(static fn (string $breakpoint): string => preg_quote($breakpoint, '#'), $field['breakpoints'] ?? ['sm', 'md', 'lg', 'xl']));

        if (str_contains($pattern, '{breakpoint}-')) {
            $quoted = str_replace('\\{breakpoint\\}\\-', '(?:(?:'.$breakpoints.')-)?', $quoted);
        } else {
            $quoted = str_replace('\\{breakpoint\\}', '(?:(?:'.$breakpoints.')-)?', $quoted);
        }

        $quoted = str_replace('\\{value\\}', '[^\\s]+', $quoted);

        return '#^'.$quoted.'$#';
    }

    private static function splitClasses(string $classList): array
    {
        return array_values(array_filter(preg_split('/\s+/', trim($classList)) ?: []));
    }
}
