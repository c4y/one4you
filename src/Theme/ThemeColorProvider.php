<?php

declare(strict_types=1);

namespace C4Y\One4you\Theme;

use Contao\DataContainer;
use Contao\StringUtil;
use Contao\System;

final class ThemeColorProvider
{
    private const DEFAULT_BACKGROUND_OPTIONS = [
        'bg-1' => 'Farbe 1',
        'bg-2' => 'Farbe 2',
        'bg-3' => 'Farbe 3',
        'bg-4' => 'Farbe 4',
        'bg-5' => 'Farbe 5',
        'bg-6' => 'Farbe 6',
        'bg-7' => 'Farbe 7',
        'bg-8' => 'Farbe 8',
        'bg-9' => 'Farbe 9',
        'bg-10' => 'Farbe 10',
    ];

    public static function getBackgroundOptions(?DataContainer $dc = null): array
    {
        return array_replace(self::fallbackBackgroundOptions(), self::loadBackgroundLabels($dc));
    }

    public static function getBackgroundCss(int $themeId): string
    {
        return '';
    }

    public static function fallbackBackgroundOptions(): array
    {
        return array_replace(self::DEFAULT_BACKGROUND_OPTIONS, $GLOBALS['C4Y_THEME']['backgrounds'] ?? []);
    }

    private static function loadBackgroundLabels(?DataContainer $dc = null): array
    {
        $labels = [];

        foreach (self::loadLabelRecords(self::resolveThemeIds($dc)) as $record) {
            $slot = self::normalizeSlot((string) ($record['slot'] ?? ''));
            $label = trim((string) ($record['title'] ?? ''));

            if ($slot === '' || $label === '') {
                continue;
            }

            $labels[$slot] = $label;
        }

        return $labels;
    }

    private static function loadLabelRecords(array $themeIds = []): array
    {
        try {
            $columns = implode(', ', array_map(static fn (int $slot): string => 'c4yThemeBackground'.$slot, range(1, 10)));

            if ($themeIds) {
                $placeholders = implode(',', array_fill(0, \count($themeIds), '?'));
                $rows = self::connection()->fetchAllAssociative(
                    'SELECT '.$columns.', c4yThemeBackgrounds FROM tl_theme WHERE id IN ('.$placeholders.')',
                    $themeIds
                );
            } else {
                $rows = self::connection()->fetchAllAssociative(
                    'SELECT '.$columns.', c4yThemeBackgrounds FROM tl_theme'
                );
            }
        } catch (\Throwable) {
            return [];
        }

        $records = [];

        foreach ($rows as $row) {
            $records = array_merge($records, self::recordsFromThemeRow($row));
            $records = array_merge($records, self::deserializeRows($row['c4yThemeBackgrounds'] ?? null));
        }

        return $records;
    }

    private static function recordsFromThemeRow(array $row): array
    {
        $records = [];

        foreach (range(1, 10) as $slot) {
            $title = trim((string) ($row['c4yThemeBackground'.$slot] ?? ''));

            if ($title !== '') {
                $records[] = [
                    'slot' => 'bg-'.$slot,
                    'title' => $title,
                ];
            }
        }

        return $records;
    }

    private static function deserializeRows(mixed $value): array
    {
        $rows = StringUtil::deserialize($value, true);

        if (!\is_array($rows)) {
            return [];
        }

        return array_values(array_filter($rows, static fn (mixed $row): bool => \is_array($row)));
    }

    private static function resolveThemeIds(?DataContainer $dc): array
    {
        if (!$dc || !$dc->id) {
            return [];
        }

        try {
            return match ($dc->table) {
                'tl_theme' => [(int) $dc->id],
                'tl_layout' => self::fetchIds('SELECT pid FROM tl_layout WHERE id=?', [(int) $dc->id]),
                'tl_article' => self::themeIdsForArticle((int) $dc->id),
                'tl_content' => self::themeIdsForContent((int) $dc->id),
                default => [],
            };
        } catch (\Throwable) {
            return [];
        }
    }

    private static function themeIdsForContent(int $contentId): array
    {
        $content = self::connection()->fetchAssociative('SELECT pid,ptable FROM tl_content WHERE id=?', [$contentId]);

        if (!$content) {
            return [];
        }

        if (($content['ptable'] ?? null) === 'tl_theme') {
            return [(int) $content['pid']];
        }

        if (($content['ptable'] ?? null) === 'tl_article') {
            return self::themeIdsForArticle((int) $content['pid']);
        }

        return [];
    }

    private static function themeIdsForArticle(int $articleId): array
    {
        $article = self::connection()->fetchAssociative('SELECT pid FROM tl_article WHERE id=?', [$articleId]);

        if (!$article) {
            return [];
        }

        return self::themeIdsForPage((int) $article['pid']);
    }

    private static function themeIdsForPage(int $pageId): array
    {
        $ids = [];
        $currentId = $pageId;

        while ($currentId > 0) {
            $page = self::connection()->fetchAssociative('SELECT pid,includeLayout,layout FROM tl_page WHERE id=?', [$currentId]);

            if (!$page) {
                break;
            }

            if ($page['includeLayout'] && $page['layout']) {
                $ids = array_merge($ids, self::fetchIds('SELECT pid FROM tl_layout WHERE id=?', [(int) $page['layout']]));
            }

            $currentId = (int) $page['pid'];
        }

        return array_values(array_unique($ids));
    }

    private static function fetchIds(string $query, array $params): array
    {
        return array_values(array_filter(array_map('intval', self::connection()->fetchFirstColumn($query, $params))));
    }

    private static function normalizeSlot(string $value): string
    {
        $value = trim($value);

        return \array_key_exists($value, self::DEFAULT_BACKGROUND_OPTIONS) ? $value : '';
    }

    private static function connection(): object
    {
        return System::getContainer()->get('database_connection');
    }
}
