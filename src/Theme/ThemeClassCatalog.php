<?php

declare(strict_types=1);

namespace C4Y\One4you\Theme;

final class ThemeClassCatalog
{
    public const FIELD_PREFIX = 'c4yTheme';

    public static function articleFields(): array
    {
        return [
            'c4yThemeWidth' => self::group('Breite', [
                'full-bg' => 'Hintergrund vollflächig, Inhalt zentriert',
                'full-content' => 'Inhalt vollflächig',
            ]),
            'c4yThemeBackground' => self::group('Hintergrund', self::backgroundOptions()),
            'c4yThemeMarginTop' => self::spacingGroup('Außenabstand oben', 'mt'),
            'c4yThemeMarginBottom' => self::spacingGroup('Außenabstand unten', 'mb'),
            'c4yThemePaddingTop' => self::spacingGroup('Innenabstand oben', 'pt'),
            'c4yThemePaddingBottom' => self::spacingGroup('Innenabstand unten', 'pb'),
            'c4yThemeArticleExtras' => self::group('Zusatz', [
                'separator-top' => 'Trennlinie oben',
                'separator-bottom' => 'Trennlinie unten',
                'indent' => 'Links und rechts einrücken',
                'indent-left' => 'Links einrücken',
                'modal' => 'Als Modal öffnen',
            ], true),
            'c4yThemeExpert' => self::group('Expertenklassen', self::expertSpacingOptions(), true),
        ];
    }

    public static function contentFields(): array
    {
        return [
            'c4yThemeGrid' => self::group('Grid', [
                'row' => 'Grid aktivieren',
                'row-flex' => 'Flex-Grid aktivieren',
                'align-items-center' => 'Vertikal mittig ausrichten',
            ], true),
            'c4yThemeBackground' => self::group('Hintergrund', self::backgroundOptions()),
            'c4yThemeMarginTop' => self::spacingGroup('Außenabstand oben', 'mt'),
            'c4yThemeMarginBottom' => self::spacingGroup('Außenabstand unten', 'mb'),
            'c4yThemePaddingTop' => self::spacingGroup('Innenabstand oben', 'pt'),
            'c4yThemePaddingBottom' => self::spacingGroup('Innenabstand unten', 'pb'),
            'c4yThemeColumns' => self::group('Spalten', self::columnOptions('col')),
            'c4yThemeTextColumns' => self::group('Textspalten', self::textColumnOptions('c-text')),
            'c4yThemeContentExtras' => self::group('Zusatz', [
                'sticky' => 'Sticky',
                'overflow-x-auto' => 'Horizontaler Scrollbalken',
                'box' => 'Box',
                'highlight' => 'Hervorhebung',
                'center' => 'Zentrieren',
                'img-round' => 'Bild als Kreis',
                'fix-height-300' => 'Fixe Bildhöhe 300 px',
            ], true),
            'c4yThemeGridExpert' => self::group('Expertenklassen Grid', self::expertGridOptions(), true),
            'c4yThemeSpacingExpert' => self::group('Expertenklassen Abstand', self::expertSpacingOptions(), true),
        ];
    }

    public static function layoutFields(): array
    {
        return [
            'c4yThemeHeader' => self::group('Header', [
                'header-fixed header-fixed-padding' => 'Dauerhaft sichtbar, Inhalt darunter',
                'header-fixed' => 'Dauerhaft sichtbar, über den Inhalten',
                'shadow' => 'Schatten',
            ], true),
        ];
    }

    public static function formFields(): array
    {
        return [
            'c4yThemeFormLayout' => self::group('Formularlayout', [
                'form-sidebyside' => 'Beschriftung und Eingabefelder nebeneinander',
            ]),
        ];
    }

    public static function allManagedClasses(): array
    {
        $classes = [];

        foreach ([self::articleFields(), self::contentFields(), self::layoutFields(), self::formFields()] as $fields) {
            foreach ($fields as $config) {
                foreach (array_keys($config['options']) as $classList) {
                    foreach (preg_split('/\s+/', trim((string) $classList)) as $class) {
                        if ($class !== '') {
                            $classes[$class] = true;
                        }
                    }
                }
            }
        }

        return array_keys($classes);
    }

    public static function collectClasses(array $record, string $table): array
    {
        $fields = match ($table) {
            'tl_article' => self::articleFields(),
            'tl_content' => self::contentFields(),
            'tl_layout' => self::layoutFields(),
            'tl_form' => self::formFields(),
            default => [],
        };

        $classes = [];

        foreach ($fields as $field => $config) {
            $value = $record[$field] ?? null;
            $values = self::normalizeValues($value);

            foreach ($values as $classList) {
                foreach (preg_split('/\s+/', trim($classList)) as $class) {
                    if ($class !== '') {
                        $classes[$class] = true;
                    }
                }
            }
        }

        return array_keys($classes);
    }

    private static function group(string $label, array $options, bool $multiple = false): array
    {
        return [
            'label' => $label,
            'options' => $options,
            'multiple' => $multiple,
        ];
    }

    private static function spacingGroup(string $label, string $prefix): array
    {
        return self::group($label, self::spacingOptions($prefix));
    }

    private static function spacingOptions(string $prefix): array
    {
        $options = [];

        for ($i = 0; $i <= 10; ++$i) {
            $options[$prefix.'-'.$i] = ($i * 10).' px';
        }

        return $options;
    }

    private static function backgroundOptions(): array
    {
        return ThemeColorProvider::getBackgroundOptions();
    }

    private static function expertSpacingOptions(): array
    {
        $options = [];

        foreach (['sm', 'md', 'lg', 'xl'] as $breakpoint) {
            foreach (['mt' => 'Außen oben', 'mb' => 'Außen unten', 'pt' => 'Innen oben', 'pb' => 'Innen unten'] as $prefix => $label) {
                for ($i = 0; $i <= 10; ++$i) {
                    $options[$prefix.'-'.$breakpoint.'-'.$i] = $label.' ab '.$breakpoint.': '.($i * 10).' px';
                }
            }
        }

        return $options;
    }

    private static function columnOptions(string $prefix): array
    {
        $options = [];

        for ($i = 1; $i <= 12; ++$i) {
            $options[$prefix.'-'.$i] = $i.' / 12';
        }

        return $options;
    }

    private static function textColumnOptions(string $prefix): array
    {
        $options = [];

        for ($i = 1; $i <= 6; ++$i) {
            $options[$prefix.'-'.$i] = $i.' Spalte'.($i > 1 ? 'n' : '');
        }

        return $options;
    }

    private static function expertGridOptions(): array
    {
        $options = [];

        foreach (['sm', 'md', 'lg', 'xl'] as $breakpoint) {
            for ($i = 1; $i <= 12; ++$i) {
                $options['col-'.$breakpoint.'-'.$i] = 'Elementbreite ab '.$breakpoint.': '.$i.' / 12';
                $options['cols-'.$breakpoint.'-'.$i] = 'Kind-Elemente ab '.$breakpoint.': '.$i.' / 12';
            }

            for ($i = 1; $i <= 6; ++$i) {
                $options['c-text-'.$breakpoint.'-'.$i] = 'Textspalten ab '.$breakpoint.': '.$i;
            }

            foreach (['column' => 'Spaltenabstand', 'row' => 'Zeilenabstand'] as $axis => $label) {
                for ($i = 0; $i <= 4; ++$i) {
                    $options['gap-'.$axis.'-'.$breakpoint.'-'.$i] = $label.' ab '.$breakpoint.': '.($i * 10).' px';
                }
            }

            foreach (['start' => 'links', 'end' => 'rechts'] as $direction => $label) {
                for ($i = 1; $i <= 4; ++$i) {
                    $options['cols-'.$breakpoint.'-offset-'.$direction.'-'.$i] = 'Einrückung '.$label.' ab '.$breakpoint.': '.$i;
                }
            }
        }

        foreach (['column' => 'Spaltenabstand', 'row' => 'Zeilenabstand'] as $axis => $label) {
            for ($i = 0; $i <= 4; ++$i) {
                $options['gap-'.$axis.'-'.$i] = $label.': '.($i * 10).' px';
            }
        }

        return $options;
    }

    private static function normalizeValues(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_string($value) && str_starts_with($value, 'a:')) {
            $decoded = @unserialize($value);
            if (is_array($decoded)) {
                $value = $decoded;
            }
        }

        if (!is_array($value)) {
            return [trim((string) $value)];
        }

        return array_values(array_filter(array_map(static fn ($item): string => trim((string) $item), $value)));
    }
}
