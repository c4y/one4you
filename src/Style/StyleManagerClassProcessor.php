<?php

declare(strict_types=1);

namespace C4Y\One4you\Style;

use Contao\StringUtil;

final class StyleManagerClassProcessor
{
    public function cleanTargetValue(string $table, array $record, bool $onlyWhenManagedClassesSelected = false): ?string
    {
        $targetField = StyleDefinitionRegistry::targetField($table);

        if (!$targetField) {
            return null;
        }

        if ($onlyWhenManagedClassesSelected && $this->managedClasses($table, $record) === []) {
            return null;
        }

        [$identifier, $classString] = $this->splitTargetValue($this->normalizeTargetValue($record[$targetField] ?? '', $targetField), $targetField);

        return $this->buildTargetValue(
            $identifier,
            implode(' ', $this->manualClasses($table, $record, $classString)),
            $targetField
        );
    }

    public function renderClassList(string $table, array $record, string $existingClassList = ''): string
    {
        $targetField = StyleDefinitionRegistry::targetField($table);

        if ($targetField) {
            [, $targetClassList] = $this->splitTargetValue($this->normalizeTargetValue($record[$targetField] ?? '', $targetField), $targetField);
            $existingClassList = trim($targetClassList.' '.$existingClassList);
        }

        return $this->mergeClassLists($targetClassList ?? '', $existingClassList, implode(' ', $this->managedClasses($table, $record)));
    }

    public function managedClasses(string $table, array $record): array
    {
        $values = StringUtil::deserialize($record[StyleDefinitionRegistry::FIELD_NAME] ?? null, true);
        $values = \is_array($values) ? $values : [];

        return StyleDefinitionRegistry::collectClasses($table, $values, $record);
    }

    public function splitTargetValue(string $value, string $targetField): array
    {
        if (!\in_array($targetField, ['cssID', 'attributes'], true)) {
            return ['', $value];
        }

        $decoded = StringUtil::deserialize($value, true);

        if (!\is_array($decoded)) {
            return ['', ''];
        }

        return [(string) ($decoded[0] ?? ''), (string) ($decoded[1] ?? '')];
    }

    public function buildTargetValue(string $identifier, string $classes, string $targetField): string
    {
        if (!\in_array($targetField, ['cssID', 'attributes'], true)) {
            return $classes;
        }

        return serialize([$identifier, $classes]);
    }

    private function normalizeTargetValue(mixed $value, string $targetField): string
    {
        if (!\is_array($value)) {
            return (string) $value;
        }

        if (\in_array($targetField, ['cssID', 'attributes'], true)) {
            return serialize([
                (string) ($value[0] ?? ''),
                (string) ($value[1] ?? ''),
            ]);
        }

        return implode(' ', array_map('strval', $value));
    }

    private function manualClasses(string $table, array $record, string $classList): array
    {
        $matchers = StyleDefinitionRegistry::managedClassMatchers($table, $record);

        return array_values(array_filter(
            $this->splitClasses($classList),
            static fn (string $class): bool => !self::isManagedClass($class, $matchers)
        ));
    }

    private static function isManagedClass(string $class, array $matchers): bool
    {
        if (\in_array($class, $matchers['exact'] ?? [], true)) {
            return true;
        }

        foreach (($matchers['patterns'] ?? []) as $pattern) {
            if (preg_match($pattern, $class)) {
                return true;
            }
        }

        return false;
    }

    private function splitClasses(string $classList): array
    {
        return array_values(array_filter(preg_split('/\s+/', trim($classList)) ?: []));
    }

    private function mergeClassLists(string ...$classLists): string
    {
        $classes = [];

        foreach ($classLists as $classList) {
            foreach ($this->splitClasses($classList) as $class) {
                $classes[$class] = true;
            }
        }

        return implode(' ', array_keys($classes));
    }
}
