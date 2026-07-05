<?php

declare(strict_types=1);

namespace C4Y\One4you\EventListener\DataContainer;

use C4Y\One4you\Theme\ThemeClassCatalog;
use Contao\Database;
use Contao\DataContainer;
use Contao\StringUtil;

final class ThemeClassDcaListener
{
    public function syncManagedClasses(DataContainer $dc): void
    {
        if (!$dc->id || !\in_array($dc->table, ['tl_article', 'tl_content', 'tl_layout', 'tl_form'], true)) {
            return;
        }

        $targetField = match ($dc->table) {
            'tl_layout' => 'cssClass',
            'tl_form' => 'attributes',
            default => 'cssID',
        };

        $record = Database::getInstance()
            ->prepare(sprintf('SELECT * FROM %s WHERE id=?', $dc->table))
            ->execute($dc->id)
            ->row();

        if (!$record) {
            return;
        }

        $managedClasses = ThemeClassCatalog::collectClasses($record, $dc->table);
        $knownClasses = ThemeClassCatalog::allManagedClasses();

        [$identifier, $classString] = $this->splitTargetValue((string) ($record[$targetField] ?? ''), $targetField);
        $classes = array_values(array_filter(preg_split('/\s+/', trim($classString)) ?: []));
        $classes = array_values(array_diff($classes, $knownClasses));
        $classes = array_values(array_unique(array_merge($classes, $managedClasses)));

        $newValue = $this->buildTargetValue($identifier, implode(' ', $classes), $targetField);

        Database::getInstance()
            ->prepare(sprintf('UPDATE %s SET %s=? WHERE id=?', $dc->table, $targetField))
            ->execute($newValue, $dc->id);
    }

    private function splitTargetValue(string $value, string $targetField): array
    {
        if ($targetField === 'cssClass') {
            return ['', $value];
        }

        $decoded = StringUtil::deserialize($value, true);

        if (!\is_array($decoded)) {
            return ['', ''];
        }

        return [(string) ($decoded[0] ?? ''), (string) ($decoded[1] ?? '')];
    }

    private function buildTargetValue(string $identifier, string $classes, string $targetField): string
    {
        if ($targetField === 'cssClass') {
            return $classes;
        }

        return serialize([$identifier, $classes]);
    }
}
