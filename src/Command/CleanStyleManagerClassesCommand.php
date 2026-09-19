<?php

declare(strict_types=1);

namespace C4Y\One4you\Command;

use C4Y\One4you\Style\StyleDefinitionRegistry;
use C4Y\One4you\Style\StyleManagerClassProcessor;
use Contao\CoreBundle\Framework\ContaoFramework;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'one4you:stylemanager:sync-classes',
    description: 'Imports configured CSS classes into the One4You Style Manager without deleting unknown classes.',
    aliases: ['one4you:stylemanager:cleanup-classes'],
)]
final class CleanStyleManagerClassesCommand extends Command
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Connection $connection,
        private readonly StyleManagerClassProcessor $classProcessor,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('apply', null, InputOption::VALUE_NONE, 'Apply the proposed changes. Without this option the command is read-only.')
            ->addOption('table', null, InputOption::VALUE_REQUIRED, 'Only inspect one supported table.')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Only inspect one record ID; requires --table.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->framework->initialize();
        $apply = (bool) $input->getOption('apply');
        $selectedTable = trim((string) $input->getOption('table'));
        $selectedId = trim((string) $input->getOption('id'));

        if ($selectedId !== '' && $selectedTable === '') {
            $io->error('The --id option requires --table.');

            return Command::INVALID;
        }

        if ($selectedTable !== '' && !isset(StyleDefinitionRegistry::TARGET_FIELDS[$selectedTable])) {
            $io->error(sprintf('Unsupported table "%s".', $selectedTable));

            return Command::INVALID;
        }

        $updated = 0;
        $checked = 0;
        $imported = 0;
        $conflicts = 0;
        $rows = [];
        $updates = [];

        foreach (StyleDefinitionRegistry::TARGET_FIELDS as $table => $targetField) {
            if ($selectedTable !== '' && $table !== $selectedTable) {
                continue;
            }

            if (!$this->hasColumns($table, ['id', $targetField])) {
                continue;
            }

            $sql = sprintf('SELECT * FROM %s', $table);
            $params = [];

            if ($selectedId !== '') {
                $sql .= ' WHERE id=?';
                $params[] = (int) $selectedId;
            }

            $records = $this->connection->fetchAllAssociative($sql, $params);

            foreach ($records as $record) {
                ++$checked;

                $result = $this->classProcessor->synchronizeRecord($table, $record);

                if ($result === null) {
                    continue;
                }

                $imported += \count($result['importedClasses']);
                $conflicts += \count($result['conflicts']);

                if ($result['changed'] || $result['conflicts'] !== []) {
                    $rows[] = [
                        $table,
                        (string) $record['id'],
                        implode(' ', $result['importedClasses']) ?: '-',
                        implode(' ', $result['manualClasses']) ?: '-',
                        $result['conflicts'] === [] ? '-' : implode(' | ', array_map(
                            static fn (array $classes): string => implode(' ', $classes),
                            $result['conflicts']
                        )),
                    ];
                }

                if (!$result['changed']) {
                    continue;
                }

                ++$updated;

                $updates[] = [
                    'table' => $table,
                    'targetField' => $targetField,
                    'targetValue' => $result['targetValue'],
                    'styleValue' => $result['styleValue'],
                    'id' => (int) $record['id'],
                ];
            }
        }

        if ($apply && $updates !== []) {
            $this->connection->transactional(function (Connection $connection) use ($updates): void {
                foreach ($updates as $update) {
                    $connection->update($update['table'], [
                        $update['targetField'] => $update['targetValue'],
                        StyleDefinitionRegistry::FIELD_NAME => $update['styleValue'],
                    ], ['id' => $update['id']]);
                }
            });
        }

        if ($rows !== []) {
            $io->table(['Table', 'ID', 'Imported', 'Manual classes', 'Conflicts'], $rows);
        }

        $mode = $apply ? 'applied' : 'proposed';
        $io->success(sprintf(
            'Checked %d records, %s %d record changes, found %d importable classes and %d conflicts.',
            $checked,
            $mode,
            $updated,
            $imported,
            $conflicts
        ));

        return Command::SUCCESS;
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
            if (!isset($availableColumns[strtolower($column)])) {
                return false;
            }
        }

        return true;
    }
}
