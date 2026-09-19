<?php

declare(strict_types=1);

namespace C4Y\One4you\EventListener\DataContainer;

use C4Y\One4you\Style\StyleManagerClassProcessor;
use C4Y\One4you\Style\StyleDefinitionRegistry;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\System;

final class One4YouStyleManagerListener
{
    public function __construct(private readonly StyleManagerClassProcessor $classProcessor)
    {
    }

    #[AsCallback(table: 'tl_layout', target: 'config.onsubmit')]
    #[AsCallback(table: 'tl_page', target: 'config.onsubmit')]
    #[AsCallback(table: 'tl_article', target: 'config.onsubmit')]
    #[AsCallback(table: 'tl_module', target: 'config.onsubmit')]
    #[AsCallback(table: 'tl_news', target: 'config.onsubmit')]
    #[AsCallback(table: 'tl_calendar_events', target: 'config.onsubmit')]
    #[AsCallback(table: 'tl_form', target: 'config.onsubmit')]
    #[AsCallback(table: 'tl_form_field', target: 'config.onsubmit')]
    #[AsCallback(table: 'tl_content', target: 'config.onsubmit')]
    public function syncClasses(DataContainer $dc): void
    {
        if (!$dc->id || !isset(StyleDefinitionRegistry::TARGET_FIELDS[$dc->table])) {
            return;
        }

        $targetField = StyleDefinitionRegistry::targetField($dc->table);

        if (!$targetField) {
            return;
        }

        $connection = System::getContainer()->get('database_connection');
        $record = $connection->fetchAssociative(sprintf('SELECT * FROM %s WHERE id=?', $dc->table), [(int) $dc->id]);

        if (!$record) {
            return;
        }

        $result = $this->classProcessor->synchronizeRecord($dc->table, $record);

        if ($result === null || !$result['changed']) {
            return;
        }

        $connection->update($dc->table, [
            $targetField => $result['targetValue'],
            StyleDefinitionRegistry::FIELD_NAME => $result['styleValue'],
        ], ['id' => (int) $dc->id]);
    }
}
