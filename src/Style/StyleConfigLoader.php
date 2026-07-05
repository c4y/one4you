<?php

declare(strict_types=1);

namespace C4Y\One4you\Style;

use Contao\System;
use Symfony\Component\Yaml\Yaml;

final class StyleConfigLoader
{
    private static ?array $config = null;

    public static function load(): array
    {
        if (self::$config !== null) {
            return self::$config;
        }

        $projectDir = System::getContainer()->getParameter('kernel.project_dir');
        $bundleFile = \dirname(__DIR__, 2).'/config/styles.yaml';
        $projectFile = $projectDir.'/config/one4you/styles.yaml';

        $config = self::readYaml($bundleFile);

        if (is_file($projectFile)) {
            $config = self::merge($config, self::readYaml($projectFile));
        }

        $config['variables'] = self::normalizeVariables($config['variables'] ?? []);
        self::$config = self::replaceVariables($config, $config['variables']);

        return self::$config;
    }

    private static function readYaml(string $file): array
    {
        if (!is_file($file)) {
            return [];
        }

        $data = Yaml::parseFile($file);

        return \is_array($data) ? $data : [];
    }

    private static function merge(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (\is_array($value) && \array_key_exists($key, $base) && \is_array($base[$key]) && self::isAssoc($value) && self::isAssoc($base[$key])) {
                $base[$key] = self::merge($base[$key], $value);
                continue;
            }

            $base[$key] = $value;
        }

        return $base;
    }

    private static function normalizeVariables(array $variables): array
    {
        $normalized = [];

        foreach ($variables as $key => $value) {
            $normalized[ltrim((string) $key, '$')] = (string) $value;
        }

        return $normalized;
    }

    private static function replaceVariables(mixed $value, array $variables): mixed
    {
        if (\is_string($value)) {
            foreach ($variables as $name => $replacement) {
                $value = str_replace('$'.$name, $replacement, $value);
            }

            return $value;
        }

        if (!\is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $item) {
            $value[$key] = self::replaceVariables($item, $variables);
        }

        return $value;
    }

    private static function isAssoc(array $value): bool
    {
        return array_keys($value) !== range(0, \count($value) - 1);
    }
}
