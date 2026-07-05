<?php

declare(strict_types=1);

$GLOBALS['TL_DCA']['tl_theme']['fields']['c4yThemeBackgrounds'] = ['sql' => 'blob NULL'];

foreach (range(1, 10) as $slot) {
    $GLOBALS['TL_DCA']['tl_theme']['fields']['c4yThemeBackground'.$slot] = [
        'sql' => "varchar(128) NOT NULL default ''",
    ];
}
