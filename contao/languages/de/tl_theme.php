<?php

declare(strict_types=1);

$GLOBALS['TL_LANG']['tl_theme']['c4y_theme_colors_legend'] = 'One4You Farben';

for ($i = 1; $i <= 10; ++$i) {
    $GLOBALS['TL_LANG']['tl_theme']['c4yThemeBackground'.$i] = [
        'Farbe '.$i,
        'Backend-Bezeichnung für bg-'.$i.'. Der echte Farbwert wird in theme-project.scss über --bg-'.$i.' gesetzt.',
    ];
}
