<?php

declare(strict_types=1);

namespace C4Y\One4you\Command;

use C4Y\One4you\Style\StyleDefinitionRegistry;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'one4you:wrapper:migrate-rsce',
    description: 'Migrates native One4You wrapper content elements to RSCE wrapper elements.',
)]
final class MigrateWrapperRsceCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->hasColumns('tl_content', ['id', 'type', StyleDefinitionRegistry::FIELD_NAME, 'c4yWrapperGrid'])) {
            $io->warning('tl_content is missing required columns.');

            return Command::SUCCESS;
        }

        $records = $this->connection->fetchAllAssociative("SELECT * FROM tl_content WHERE type IN ('c4y_wrapper', 'c4y_wrapper_stop', 'rsce_wrapper_start') ORDER BY id");

        if ($records === []) {
            $io->success('No wrapper records found.');

            return Command::SUCCESS;
        }

        $updated = 0;
        $hasRsceData = $this->hasColumns('tl_content', ['rsce_data']);

        foreach ($records as $record) {
            $type = (string) ($record['type'] ?? '');
            $data = [];

            if ($type === 'c4y_wrapper' || $type === 'c4y_wrapper_stop') {
                $data['type'] = $type === 'c4y_wrapper_stop' ? 'rsce_wrapper_stop' : 'rsce_wrapper_start';
            }

            if ($hasRsceData) {
                $data['rsce_data'] = '{}';
            }

            if ($type === 'c4y_wrapper' || $type === 'rsce_wrapper_start') {
                $data[StyleDefinitionRegistry::FIELD_NAME] = serialize($this->mergeStyleValues($record));
                $data['cssID'] = $this->cleanCssId((string) ($record['cssID'] ?? ''));
            }

            $this->connection->update('tl_content', $data, ['id' => (int) $record['id']]);
            ++$updated;
        }

        $io->success(sprintf('Processed %d wrapper records.', $updated));

        return Command::SUCCESS;
    }

    private function mergeStyleValues(array $record): array
    {
        $values = StringUtil::deserialize($record[StyleDefinitionRegistry::FIELD_NAME] ?? null, true);
        $values = \is_array($values) ? $values : [];
        $wrapperValues = $this->wrapperValues($record);

        if ($wrapperValues !== []) {
            $values['wrapper_layout'] = $wrapperValues;
        }

        return $values;
    }

    private function wrapperValues(array $record): array
    {
        $grid = StringUtil::deserialize($record['c4yWrapperGrid'] ?? null, true);

        if (!\is_array($grid) || $grid === []) {
            $grid = $this->gridValuesFromCssId((string) ($record['cssID'] ?? ''));
        }

        if ($grid === []) {
            $grid = $this->legacyGridValues($record);
        }
        $layout = [];
        $gap = [];
        $mode = (string) ($grid['mode'] ?? '');

        if ($mode === '' && ($grid['layout'] ?? '') === 'section-indexed') {
            $mode = 'section-indexed';
        }

        $gridType = match ($mode) {
            'auto', 'manual', 'flex' => $mode,
            'section-indexed' => 'section-indexed',
            default => '',
        };

        if ($gridType !== '') {
            $layout['grid_type'] = $gridType;
        }

        if (!empty($grid['verticalCenter'])) {
            $layout['vertical_center'] = 'align-items-center';
        }

        if (!empty($grid['sticky'])) {
            $layout['sticky'] = 'sticky';
        }

        $columns = $this->responsiveValue($grid['columns'] ?? []);

        if ($columns !== []) {
            $layout['columns'] = $columns;
        }

        $gapColumn = $this->responsiveValue($grid['gap']['column'] ?? []);

        if ($gapColumn !== []) {
            $gap['gap_column'] = $gapColumn;
        }

        $gapRow = $this->responsiveValue($grid['gap']['row'] ?? []);

        if ($gapRow !== []) {
            $gap['gap_row'] = $gapRow;
        }

        return array_filter([
            'layout' => $layout,
            'gap' => $gap,
        ]);
    }

    private function responsiveValue(mixed $values): array
    {
        if (!\is_array($values)) {
            return [];
        }

        $result = [];

        if (($values['base'] ?? '') !== '') {
            $result['value'] = (string) $values['base'];
        }

        foreach (['sm', 'md', 'lg', 'xl'] as $breakpoint) {
            if (($values[$breakpoint] ?? '') === '') {
                continue;
            }

            $result['responsive'][$breakpoint] = (string) $values[$breakpoint];
        }

        return $result;
    }

    private function legacyGridValues(array $record): array
    {
        $values = [
            'mode' => (string) ($record['c4yWrapperGridMode'] ?? ''),
            'verticalCenter' => (string) ($record['c4yWrapperVerticalCenter'] ?? ''),
            'sticky' => (string) ($record['c4yWrapperSticky'] ?? ''),
            'columns' => [],
            'gap' => [
                'column' => [],
                'row' => [],
            ],
        ];

        foreach (['base' => '', 'sm' => 'Sm', 'md' => 'Md', 'lg' => 'Lg', 'xl' => 'Xl'] as $breakpoint => $suffix) {
            $values['columns'][$breakpoint] = (string) ($record['c4yWrapperColumns'.$suffix] ?? '');
            $values['gap']['column'][$breakpoint] = (string) ($record['c4yWrapperGapColumn'.$suffix] ?? '');
            $values['gap']['row'][$breakpoint] = (string) ($record['c4yWrapperGapRow'.$suffix] ?? '');
        }

        return $values;
    }

    private function gridValuesFromCssId(string $cssId): array
    {
        [, $classString] = $this->splitCssId($cssId);
        $classes = preg_split('/\s+/', trim($classString)) ?: [];
        $values = [
            'mode' => '',
            'columns' => [],
        ];

        if (\in_array('section-indexed', $classes, true)) {
            $values['mode'] = 'section-indexed';
        } elseif (\in_array('row-flex', $classes, true) || \in_array('flex', $classes, true) || \in_array('flex-row', $classes, true)) {
            $values['mode'] = 'flex';
        } elseif (\in_array('row', $classes, true)) {
            $values['mode'] = $this->containsColumnClass($classes) ? 'auto' : 'manual';
        }

        foreach ($classes as $class) {
            if (preg_match('/^(?:cols|c)-(?:(sm|md|lg|xl)-)?([1-9]|1[0-2])$/', $class, $matches)) {
                $values['columns'][$matches[1] !== '' ? $matches[1] : 'base'] = $matches[2];
            }
        }

        if (\in_array('align-items-center', $classes, true)) {
            $values['verticalCenter'] = '1';
        }

        if (\in_array('sticky', $classes, true)) {
            $values['sticky'] = '1';
        }

        return array_filter($values, static fn (mixed $value): bool => $value !== '' && $value !== []);
    }

    private function containsColumnClass(array $classes): bool
    {
        foreach ($classes as $class) {
            if (preg_match('/^(?:cols|c)-(?:(?:sm|md|lg|xl)-)?(?:[1-9]|1[0-2])$/', $class)) {
                return true;
            }
        }

        return false;
    }

    private function cleanCssId(string $cssId): string
    {
        [$identifier, $classString] = $this->splitCssId($cssId);
        $classes = array_values(array_filter(
            preg_split('/\s+/', trim($classString)) ?: [],
            static fn (string $class): bool => !preg_match('/^(?:row|row-flex|flex|flex-row|section-indexed|align-items-center|sticky|(?:cols|c)-(?:(?:sm|md|lg|xl)-)?(?:[1-9]|1[0-2]))$/', $class)
        ));

        return serialize([$identifier, implode(' ', $classes)]);
    }

    private function splitCssId(string $cssId): array
    {
        $decoded = StringUtil::deserialize($cssId, true);

        if (!\is_array($decoded)) {
            return ['', ''];
        }

        return [(string) ($decoded[0] ?? ''), (string) ($decoded[1] ?? '')];
    }

    private function hasColumns(string $table, array $columns): bool
    {
        $schemaManager = method_exists($this->connection, 'createSchemaManager')
            ? $this->connection->createSchemaManager()
            : $this->connection->getSchemaManager();

        if (!$schemaManager->tablesExist([$table])) {
            return false;
        }

        $availableColumns = array_change_key_case($schemaManager->listTableColumns($table), \CASE_LOWER);

        foreach ($columns as $column) {
            if (!isset($availableColumns[strtolower((string) $column)])) {
                return false;
            }
        }

        return true;
    }
}
