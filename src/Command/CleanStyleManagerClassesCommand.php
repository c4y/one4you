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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'one4you:stylemanager:cleanup-classes',
    description: 'Removes One4You Style Manager classes from the regular Contao CSS class fields.',
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->framework->initialize();

        $updated = 0;
        $checked = 0;

        foreach (StyleDefinitionRegistry::TARGET_FIELDS as $table => $targetField) {
            if (!$this->hasColumns($table, ['id', $targetField])) {
                continue;
            }

            $records = $this->connection->fetchAllAssociative(sprintf('SELECT * FROM %s', $table));

            foreach ($records as $record) {
                ++$checked;

                $cleanedValue = $this->classProcessor->cleanTargetValue($table, $record, true);

                if ($cleanedValue === null || $cleanedValue === (string) ($record[$targetField] ?? '')) {
                    continue;
                }

                $this->connection->update($table, [$targetField => $cleanedValue], ['id' => (int) $record['id']]);
                ++$updated;
            }
        }

        $io->success(sprintf('Checked %d records and cleaned %d CSS class fields.', $checked, $updated));

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
