<?php

declare(strict_types=1);

namespace C4Y\One4you\Widget;

use C4Y\One4you\Style\StyleDefinitionRegistry;
use Contao\StringUtil;
use Contao\System;
use Contao\Widget;
use Twig\Environment;

final class One4YouStyleManagerWidget extends Widget
{
    protected $blnSubmitInput = true;

    protected $strTemplate = 'be_widget';

    private readonly ?Environment $twig;

    public function __construct($arrAttributes = null)
    {
        parent::__construct($arrAttributes);

        $this->twig = System::getContainer()->get('twig');
    }

    public function generate(): string
    {
        $record = $this->currentRecord();
        $blocks = StyleDefinitionRegistry::blocksForTable($this->strTable, $record);

        if (!$blocks) {
            return '<div class="tl_info">Für diesen Bereich sind keine One4You Styles definiert.</div>';
        }

        $values = StringUtil::deserialize($this->varValue, true);

        if (!\is_array($values)) {
            $values = [];
        }

        return (string) $this->twig?->render('@Contao/backend/widget/one4you_stylemanager.html.twig', [
            'id' => $this->strId,
            'name' => $this->strName,
            'blocks' => $blocks,
            'values' => $values,
        ]);
    }

    public function validate(): void
    {
        $value = $this->getPost($this->strName);
        $record = $this->currentRecord();

        if ($value === null) {
            $this->varValue = [];

            return;
        }

        $value = $this->cleanupValue(\is_array($value) ? $value : []);
        $this->varValue = StyleDefinitionRegistry::filterValuesForTable($this->strTable, $value, $record);
    }

    private function cleanupValue(array $value): array
    {
        $clean = [];

        foreach ($value as $key => $item) {
            if (\is_array($item)) {
                $item = $this->cleanupValue($item);

                if ($item !== []) {
                    $clean[$key] = $item;
                }

                continue;
            }

            $item = trim((string) $item);

            if ($item !== '') {
                $clean[$key] = $item;
            }
        }

        return $clean;
    }

    private function currentRecord(): ?array
    {
        $record = $this->activeRecord ?? null;

        if (\is_object($record) && method_exists($record, 'row')) {
            $row = $record->row();

            return \is_array($row) ? $row : null;
        }

        if (\is_object($record)) {
            return get_object_vars($record);
        }

        if (\is_array($record)) {
            return $record;
        }

        return null;
    }
}
