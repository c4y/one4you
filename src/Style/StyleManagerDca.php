<?php

declare(strict_types=1);

namespace C4Y\One4you\Style;

use Contao\DataContainer;

final class StyleManagerDca
{
    public static function extend(string $table): void
    {
        if (!StyleDefinitionRegistry::blocksForTable($table)) {
            return;
        }

        $GLOBALS['TL_DCA'][$table]['fields'][StyleDefinitionRegistry::FIELD_NAME] = [
            'label' => ['', ''],
            'exclude' => true,
            'inputType' => 'one4you_stylemanager',
            'eval' => ['tl_class' => 'clr one4you-stylemanager-widget', 'alwaysSave' => true],
            'sql' => 'blob NULL',
        ];

        foreach (($GLOBALS['TL_DCA'][$table]['palettes'] ?? []) as $palette => $definition) {
            if ($palette === '__selector__' || !\is_string($definition) || str_contains($definition, StyleDefinitionRegistry::FIELD_NAME)) {
                continue;
            }

            $legend = '{one4you_style_legend},'.StyleDefinitionRegistry::FIELD_NAME;

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

    public static function extendContentPalettes(?DataContainer $dc = null): void
    {
        self::extend('tl_content');
    }

    public static function preserveLegacyColumns(string $table): void
    {
        $fields = match ($table) {
            'tl_article' => [
                'bg_image' => 'binary(16) NULL',
                'bg_size' => "varchar(128) DEFAULT '' NOT NULL",
                'bg_active' => "char(1) DEFAULT '' NOT NULL",
                'minHeight' => "varchar(128) DEFAULT '' NOT NULL",
                'c4yThemeWidth' => "varchar(255) NOT NULL default ''",
                'c4yThemeBackground' => "varchar(255) NOT NULL default ''",
                'c4yThemeMarginTop' => "varchar(255) NOT NULL default ''",
                'c4yThemeMarginBottom' => "varchar(255) NOT NULL default ''",
                'c4yThemePaddingTop' => "varchar(255) NOT NULL default ''",
                'c4yThemePaddingBottom' => "varchar(255) NOT NULL default ''",
                'c4yThemeArticleExtras' => 'blob NULL',
                'c4yThemeExpert' => 'blob NULL',
            ],
            'tl_content' => [
                'c4yThemeGrid' => 'blob NULL',
                'c4yThemeBackground' => "varchar(255) NOT NULL default ''",
                'c4yThemeMarginTop' => "varchar(255) NOT NULL default ''",
                'c4yThemeMarginBottom' => "varchar(255) NOT NULL default ''",
                'c4yThemePaddingTop' => "varchar(255) NOT NULL default ''",
                'c4yThemePaddingBottom' => "varchar(255) NOT NULL default ''",
                'c4yThemeColumns' => "varchar(255) NOT NULL default ''",
                'c4yThemeTextColumns' => "varchar(255) NOT NULL default ''",
                'c4yThemeContentExtras' => 'blob NULL',
                'c4yThemeGridExpert' => 'blob NULL',
                'c4yThemeSpacingExpert' => 'blob NULL',
                'c4yWrapperGrid' => 'blob NULL',
                'c4yWrapperGridMode' => "varchar(16) NOT NULL default ''",
                'c4yWrapperColumns' => "varchar(2) NOT NULL default ''",
                'c4yWrapperColumnsSm' => "varchar(2) NOT NULL default ''",
                'c4yWrapperColumnsMd' => "varchar(2) NOT NULL default ''",
                'c4yWrapperColumnsLg' => "varchar(2) NOT NULL default ''",
                'c4yWrapperColumnsXl' => "varchar(2) NOT NULL default ''",
                'c4yWrapperGapColumn' => "varchar(8) NOT NULL default ''",
                'c4yWrapperGapColumnSm' => "varchar(8) NOT NULL default ''",
                'c4yWrapperGapColumnMd' => "varchar(8) NOT NULL default ''",
                'c4yWrapperGapColumnLg' => "varchar(8) NOT NULL default ''",
                'c4yWrapperGapColumnXl' => "varchar(8) NOT NULL default ''",
                'c4yWrapperGapRow' => "varchar(8) NOT NULL default ''",
                'c4yWrapperGapRowSm' => "varchar(8) NOT NULL default ''",
                'c4yWrapperGapRowMd' => "varchar(8) NOT NULL default ''",
                'c4yWrapperGapRowLg' => "varchar(8) NOT NULL default ''",
                'c4yWrapperGapRowXl' => "varchar(8) NOT NULL default ''",
                'c4yWrapperVerticalCenter' => "char(1) NOT NULL default ''",
                'c4yWrapperSticky' => "char(1) NOT NULL default ''",
                'c4yWrapperFilter' => "char(1) NOT NULL default ''",
                'c4yWrapperFilterElements' => 'blob NULL',
            ],
            'tl_layout' => [
                'c4yThemeHeader' => 'blob NULL',
            ],
            'tl_form' => [
                'c4yThemeFormLayout' => "varchar(255) NOT NULL default ''",
            ],
            'tl_module' => [
                'one4you_email' => "varchar(255) NOT NULL default ''",
                'one4you_phone_text' => "text NOT NULL default ''",
                'one4you_phone' => "text NOT NULL default ''",
                'one4you_social_facebook' => "varchar(128) NOT NULL default ''",
                'one4you_social_xing' => "varchar(128) NOT NULL default ''",
                'one4you_social_instagram' => "varchar(128) NOT NULL default ''",
                'one4you_social_twitter' => "varchar(128) NOT NULL default ''",
                'one4you_info_link' => "text NOT NULL default ''",
                'one4you_info_text' => "text NOT NULL default ''",
            ],
            default => [],
        };

        foreach ($fields as $field => $sql) {
            $GLOBALS['TL_DCA'][$table]['fields'][$field] ??= ['sql' => $sql];
        }
    }
}
