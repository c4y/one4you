<?php

declare(strict_types=1);

use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_c4y_theme_color'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_theme',
        'notEditable' => true,
        'closed' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid' => 'index',
                'slot' => 'index',
                'alias' => 'index',
            ],
        ],
    ],
    'fields' => [
        'id' => ['sql' => 'int(10) unsigned NOT NULL auto_increment'],
        'pid' => ['sql' => "int(10) unsigned NOT NULL default 0"],
        'sorting' => ['sql' => "int(10) unsigned NOT NULL default 0"],
        'tstamp' => ['sql' => "int(10) unsigned NOT NULL default 0"],
        'slot' => ['sql' => "varchar(8) NOT NULL default ''"],
        'title' => ['sql' => "varchar(128) NOT NULL default ''"],
        'alias' => ['sql' => "varchar(64) NOT NULL default ''"],
        'backgroundColor' => ['sql' => "varchar(8) NOT NULL default ''"],
        'textColor' => ['sql' => "varchar(8) NOT NULL default ''"],
    ],
];
