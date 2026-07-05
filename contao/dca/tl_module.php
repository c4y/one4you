<?php

declare(strict_types=1);

use C4Y\One4you\Style\StyleManagerDca;

$GLOBALS['TL_DCA']['tl_module']['palettes']['one4you_search'] = '
    {title_legend},name,headline,type;
    {redirect_legend},jumpTo;
    {one4you_search_legend},one4youSearchPlaceholder,one4youSearchLabel,one4youSearchOpenLabel,one4youSearchSubmitLabel;
    {template_legend:hide},customTpl;
    {protected_legend:hide},protected;
    {expert_legend:hide},cssID
';

$GLOBALS['TL_DCA']['tl_module']['fields']['one4youSearchPlaceholder'] = [
    'inputType' => 'text',
    'eval' => ['tl_class' => 'w50', 'maxlength' => 255],
    'sql' => "varchar(255) NOT NULL default 'Suche'",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['one4youSearchLabel'] = [
    'inputType' => 'text',
    'eval' => ['tl_class' => 'w50', 'maxlength' => 255],
    'sql' => "varchar(255) NOT NULL default 'Webseite durchsuchen'",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['one4youSearchOpenLabel'] = [
    'inputType' => 'text',
    'eval' => ['tl_class' => 'w50', 'maxlength' => 255],
    'sql' => "varchar(255) NOT NULL default 'Suche öffnen'",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['one4youSearchSubmitLabel'] = [
    'inputType' => 'text',
    'eval' => ['tl_class' => 'w50', 'maxlength' => 255],
    'sql' => "varchar(255) NOT NULL default 'Suche absenden'",
];

StyleManagerDca::preserveLegacyColumns('tl_module');
StyleManagerDca::extend('tl_module');
