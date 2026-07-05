<?php

declare(strict_types=1);

namespace C4Y\One4you\Style;

final class StyleCssGenerator
{
    public static function generate(): string
    {
        $rules = [];

        foreach ((StyleConfigLoader::load()['css'] ?? []) as $rule) {
            if (!\is_array($rule) || empty($rule['selector']) || empty($rule['declarations']) || !\is_array($rule['declarations'])) {
                continue;
            }

            $declarations = [];

            foreach ($rule['declarations'] as $property => $value) {
                $declarations[] = $property.': '.$value;
            }

            if ($declarations) {
                $rules[] = $rule['selector'].'{'.implode(';', $declarations).'}';
            }
        }

        return implode('', $rules);
    }
}
