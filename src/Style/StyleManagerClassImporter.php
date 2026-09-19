<?php

declare(strict_types=1);

namespace C4Y\One4you\Style;

final class StyleManagerClassImporter
{
    public function import(array $blocks, array $values, array $classList): array
    {
        $classList = array_values(array_unique(array_filter(array_map('strval', $classList))));
        $classLookup = array_fill_keys($classList, true);
        $matches = [];

        foreach ($this->candidates($blocks) as $candidate) {
            if (!$this->allClassesPresent($candidate['classes'], $classLookup)) {
                continue;
            }

            $matches[$candidate['slot']][$candidate['value']][] = $candidate;
        }

        $classOwners = [];

        foreach ($matches as $slot => $valuesBySlot) {
            foreach ($valuesBySlot as $candidates) {
                foreach ($candidates as $candidate) {
                    foreach ($candidate['classes'] as $class) {
                        $classOwners[$class][$slot] = true;
                    }
                }
            }
        }

        $blockedSlots = [];

        foreach ($classOwners as $owners) {
            if (\count($owners) < 2) {
                continue;
            }

            foreach (array_keys($owners) as $slot) {
                $blockedSlots[$slot] = true;
            }
        }

        $imported = [];
        $conflicts = [];

        foreach ($matches as $slot => $valuesBySlot) {
            $firstCandidate = reset($valuesBySlot)[0];

            if (isset($blockedSlots[$slot])) {
                $conflicts[$slot] = $this->classesFromMatches($valuesBySlot);

                continue;
            }

            if ($firstCandidate['multiple']) {
                $selectedValues = array_keys($valuesBySlot);
                $existingValues = $this->getValue($values, $firstCandidate['path']);
                $existingValues = \is_array($existingValues) ? array_map('strval', $existingValues) : [];
                $this->setValue($values, $firstCandidate['path'], array_values(array_unique(array_merge($existingValues, $selectedValues))));

                foreach ($valuesBySlot as $candidates) {
                    foreach ($candidates as $candidate) {
                        foreach ($candidate['classes'] as $class) {
                            $imported[$class] = true;
                        }
                    }
                }

                continue;
            }

            if (\count($valuesBySlot) !== 1) {
                $conflicts[$slot] = $this->classesFromMatches($valuesBySlot);

                continue;
            }

            $selectedValue = (string) array_key_first($valuesBySlot);
            $candidates = reset($valuesBySlot);
            $this->setValue($values, $firstCandidate['path'], $selectedValue);

            foreach ($candidates as $candidate) {
                foreach ($candidate['classes'] as $class) {
                    $imported[$class] = true;
                }
            }
        }

        return [
            'values' => $values,
            'importedClasses' => array_values(array_filter($classList, static fn (string $class): bool => isset($imported[$class]))),
            'conflicts' => $conflicts,
        ];
    }

    private function candidates(array $blocks): array
    {
        $candidates = [];

        foreach ($blocks as $blockAlias => $block) {
            foreach (($block['tabs'] ?? []) as $tabAlias => $tab) {
                foreach (($tab['fields'] ?? []) as $fieldAlias => $field) {
                    $path = [(string) $blockAlias, (string) $tabAlias, (string) $fieldAlias];

                    if (($field['type'] ?? 'select') === 'trbl') {
                        $candidates = array_merge($candidates, $this->trblCandidates($field, $path));

                        continue;
                    }

                    $candidates = array_merge($candidates, $this->optionCandidates($field, $path));
                }
            }
        }

        return $candidates;
    }

    private function optionCandidates(array $field, array $path): array
    {
        $candidates = [];
        $responsive = !empty($field['responsive']);
        $multiple = ($field['type'] ?? 'select') === 'checkbox';

        foreach (($field['options'] ?? []) as $option) {
            $canonicalValue = (string) ($option['value'] ?? '');

            if ($canonicalValue === '') {
                continue;
            }

            foreach ($this->optionInputValues($option) as $inputValue) {
                $classes = $this->classesForOption($field, $option, $inputValue);

                if ($classes !== []) {
                    $basePath = $responsive ? array_merge($path, ['value']) : $path;
                    $candidates[] = $this->candidate($basePath, $canonicalValue, $classes, $multiple);
                }

                if (!$responsive) {
                    continue;
                }

                foreach (($field['breakpoints'] ?? []) as $breakpoint) {
                    $classes = $this->classesForOption($field, $option, $inputValue, (string) $breakpoint);

                    if ($classes === []) {
                        continue;
                    }

                    $candidates[] = $this->candidate(
                        array_merge($path, ['responsive', (string) $breakpoint]),
                        $canonicalValue,
                        $classes,
                        $multiple
                    );
                }
            }
        }

        return $candidates;
    }

    private function trblCandidates(array $field, array $path): array
    {
        $candidates = [];

        foreach (($field['options'] ?? []) as $option) {
            $canonicalValue = (string) ($option['value'] ?? '');

            if ($canonicalValue === '') {
                continue;
            }

            foreach ($this->optionInputValues($option) as $inputValue) {
                foreach (($field['class_patterns'] ?? []) as $side => $pattern) {
                    $classes = $this->splitClasses($this->replacePattern((string) $pattern, $inputValue));

                    if ($classes !== []) {
                        $candidates[] = $this->candidate(array_merge($path, [(string) $side]), $canonicalValue, $classes);
                    }

                    if (empty($field['responsive'])) {
                        continue;
                    }

                    foreach (($field['breakpoints'] ?? []) as $breakpoint) {
                        $classes = $this->splitClasses($this->replacePattern((string) $pattern, $inputValue, (string) $breakpoint));

                        if ($classes === []) {
                            continue;
                        }

                        $candidates[] = $this->candidate(
                            array_merge($path, ['responsive', (string) $breakpoint, (string) $side]),
                            $canonicalValue,
                            $classes
                        );
                    }
                }
            }
        }

        return $candidates;
    }

    private function candidate(array $path, string $value, array $classes, bool $multiple = false): array
    {
        return [
            'slot' => implode('.', $path),
            'path' => $path,
            'value' => $value,
            'classes' => $classes,
            'multiple' => $multiple,
        ];
    }

    private function optionInputValues(array $option): array
    {
        $values = [(string) ($option['value'] ?? '')];

        foreach (($option['aliases'] ?? []) as $alias) {
            $values[] = (string) $alias;
        }

        return array_values(array_unique(array_filter($values, static fn (string $value): bool => $value !== '')));
    }

    private function classesForOption(array $field, array $option, string $inputValue, ?string $breakpoint = null): array
    {
        if ($breakpoint !== null && !empty($field['responsive_class_pattern'])) {
            return $this->splitClasses($this->replacePattern((string) $field['responsive_class_pattern'], $inputValue, $breakpoint));
        }

        if (!empty($field['class_pattern'])) {
            return $this->splitClasses($this->replacePattern((string) $field['class_pattern'], $inputValue));
        }

        if ($inputValue !== (string) ($option['value'] ?? '')) {
            return $this->splitClasses($inputValue);
        }

        return $this->splitClasses((string) ($option['classes'] ?? $inputValue));
    }

    private function allClassesPresent(array $classes, array $classLookup): bool
    {
        if ($classes === []) {
            return false;
        }

        foreach ($classes as $class) {
            if (!isset($classLookup[$class])) {
                return false;
            }
        }

        return true;
    }

    private function classesFromMatches(array $valuesBySlot): array
    {
        $classes = [];

        foreach ($valuesBySlot as $candidates) {
            foreach ($candidates as $candidate) {
                foreach ($candidate['classes'] as $class) {
                    $classes[$class] = true;
                }
            }
        }

        return array_keys($classes);
    }

    private function getValue(array $values, array $path): mixed
    {
        $current = $values;

        foreach ($path as $segment) {
            if (!\is_array($current) || !\array_key_exists($segment, $current)) {
                return null;
            }

            $current = $current[$segment];
        }

        return $current;
    }

    private function setValue(array &$values, array $path, mixed $value): void
    {
        $current = &$values;

        foreach ($path as $segment) {
            if (!isset($current[$segment]) || !\is_array($current[$segment])) {
                $current[$segment] = [];
            }

            $current = &$current[$segment];
        }

        $current = $value;
    }

    private function replacePattern(string $pattern, string $value, ?string $breakpoint = null): string
    {
        $breakpointValue = '';

        if ($breakpoint !== null) {
            $breakpointValue = str_contains($pattern, '{breakpoint}-') ? $breakpoint : $breakpoint.'-';
        }

        return str_replace(['{value}', '{breakpoint}'], [$value, $breakpointValue], $pattern);
    }

    private function splitClasses(string $classList): array
    {
        return array_values(array_filter(preg_split('/\s+/', trim($classList)) ?: []));
    }
}
