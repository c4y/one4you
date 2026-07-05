<?php

declare(strict_types=1);

namespace C4Y\One4you\Theme;

final class ThemeClassDca
{
    public static function extendArticleDca(): void
    {
        self::addFields('tl_article', ThemeClassCatalog::articleFields());
        self::addFieldsToPalettes('tl_article', ThemeClassCatalog::articleFields());
    }

    public static function extendContentDca(): void
    {
        self::addFields('tl_content', ThemeClassCatalog::contentFields());
        self::addFieldsToPalettes('tl_content', ThemeClassCatalog::contentFields());
    }

    public static function extendLayoutDca(): void
    {
        self::addFields('tl_layout', ThemeClassCatalog::layoutFields());
        self::addFieldsToPalettes('tl_layout', ThemeClassCatalog::layoutFields());
    }

    public static function extendFormDca(): void
    {
        self::addFields('tl_form', ThemeClassCatalog::formFields());
        self::addFieldsToPalettes('tl_form', ThemeClassCatalog::formFields());
    }

    private static function addFields(string $table, array $fields): void
    {
        foreach ($fields as $field => $config) {
            $multiple = (bool) ($config['multiple'] ?? false);
            $options = array_keys($config['options']);
            $fieldConfig = [
                'label' => [$config['label'], ''],
                'exclude' => true,
                'inputType' => $multiple ? 'checkbox' : 'select',
                'eval' => array_filter([
                    'multiple' => $multiple ?: null,
                    'includeBlankOption' => $multiple ? null : true,
                    'chosen' => $multiple ? null : true,
                    'tl_class' => $multiple ? 'clr c4y-theme-field c4y-theme-field--multiple' : 'w50 c4y-theme-field',
                ]),
                'sql' => $multiple ? 'blob NULL' : "varchar(255) NOT NULL default ''",
            ];

            if ($field === 'c4yThemeBackground') {
                $fieldConfig['options_callback'] = [ThemeColorProvider::class, 'getBackgroundOptions'];
            } else {
                $fieldConfig['options'] = $options;
                $fieldConfig['reference'] = $config['options'];
            }

            $GLOBALS['TL_DCA'][$table]['fields'][$field] = $fieldConfig;
        }
    }

    private static function addFieldsToPalettes(string $table, array $fields): void
    {
        $basicFields = [];
        $expertFields = [];

        foreach (array_keys($fields) as $field) {
            if (str_contains(strtolower($field), 'expert')) {
                $expertFields[] = $field;
            } else {
                $basicFields[] = $field;
            }
        }

        $legend = '{c4y_theme_legend},'.implode(',', $basicFields);

        if ($expertFields) {
            $legend .= ';{c4y_theme_expert_legend:hide},'.implode(',', $expertFields);
        }

        foreach (($GLOBALS['TL_DCA'][$table]['palettes'] ?? []) as $palette => $definition) {
            if ($palette === '__selector__' || !\is_string($definition) || str_contains($definition, 'c4y_theme_legend')) {
                continue;
            }

            if (str_contains($definition, ';{expert_legend')) {
                $GLOBALS['TL_DCA'][$table]['palettes'][$palette] = preg_replace('/;\\{expert_legend[^}]*\\}/', ';'.$legend.'$0', $definition, 1);
                continue;
            }

            if (str_contains($definition, ';{invisible_legend')) {
                $GLOBALS['TL_DCA'][$table]['palettes'][$palette] = preg_replace('/;\\{invisible_legend[^}]*\\}/', ';'.$legend.'$0', $definition, 1);
                continue;
            }

            $GLOBALS['TL_DCA'][$table]['palettes'][$palette] .= ';'.$legend;
        }
    }
}
