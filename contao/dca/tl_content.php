<?php

use C4Y\One4you\Style\StyleManagerDca;

/**
 * Global callbacks
 */
$GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = [
    \C4Y\One4you\EventListener\ContentDataContainer::class,
    'adjustPalettes',
];
$GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = [
    StyleManagerDca::class,
    'extendContentPalettes',
];

/**
 * Add fields
 */
$GLOBALS['TL_DCA']['tl_content']['fields']['elementsFilter_filters'] = [
    'label'            => &$GLOBALS['TL_LANG']['tl_content']['elementsFilter_filters'],
    'exclude'          => true,
    'inputType'        => 'checkbox',
    'options_callback' => [\C4Y\One4you\EventListener\ContentDataContainer::class, 'getFilters'],
    'eval'             => ['multiple' => true, 'tl_class' => 'clr'],
    'sql'              => "blob NULL",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['contentButtons'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['contentButtons'],
    'inputType' => 'rowWizard',
    'fields' => [
        'label' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['contentButtonLabel'],
            'inputType' => 'text',
            'eval' => ['style' => 'width:180px'],
        ],
        'url' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['contentButtonUrl'],
            'inputType' => 'text',
            'eval' => ['decodeEntities' => true, 'dcaPicker' => true, 'style' => 'width:220px'],
        ],
        'target' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['contentButtonTarget'],
            'inputType' => 'checkbox',
            'eval' => ['style' => 'width:70px'],
        ],
        'color' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['contentButtonColor'],
            'inputType' => 'select',
            'default' => 'btn-primary',
            'options' => ['btn-primary', 'btn-secondary', 'btn-accent'],
            'reference' => &$GLOBALS['TL_LANG']['tl_content']['contentButtonColor_options'],
            'eval' => ['style' => 'width:140px'],
        ],
        'variant' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['contentButtonVariant'],
            'inputType' => 'select',
            'options' => ['', 'btn-outline', 'btn-soft', 'btn-ghost', 'btn-text'],
            'reference' => &$GLOBALS['TL_LANG']['tl_content']['contentButtonVariant_options'],
            'eval' => ['style' => 'width:130px'],
        ],
    ],
    'eval' => [
        'tl_class' => 'clr',
        'max' => 2,
    ],
    'sql' => 'blob NULL',
];

if (isset($GLOBALS['TL_DCA']['tl_content']['palettes']['text'])) {
    $GLOBALS['TL_DCA']['tl_content']['palettes']['text'] = str_replace(
        ';{image_legend}',
        ';{button_legend:hide},contentButtons;{image_legend}',
        $GLOBALS['TL_DCA']['tl_content']['palettes']['text']
    );
}

StyleManagerDca::preserveLegacyColumns('tl_content');
StyleManagerDca::extend('tl_content');
