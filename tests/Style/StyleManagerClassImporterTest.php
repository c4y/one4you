<?php

declare(strict_types=1);

namespace C4Y\One4you\Tests\Style;

use C4Y\One4you\Style\StyleManagerClassImporter;
use RuntimeException;

final class StyleManagerClassImporterTest
{
    private StyleManagerClassImporter $importer;

    public function __construct()
    {
        $this->importer = new StyleManagerClassImporter();
    }

    public function run(): void
    {
        $this->importsExactAndSpacingClasses();
        $this->importsResponsiveAndLegacySpacingClasses();
        $this->keepsUnknownClassesUnmanaged();
        $this->keepsConflictingSelectionsUnmanaged();
        $this->requiresEveryClassOfAMultiClassOption();
    }

    private function importsExactAndSpacingClasses(): void
    {
        $result = $this->importer->import($this->blocks(), [], ['full-bg', 'mb-0', 'custom-class']);

        self::assertSame('full-bg', $result['values']['article_layout']['article']['width']);
        self::assertSame('0', $result['values']['article_layout']['spacing']['margin']['bottom']);
        self::assertSame(['full-bg', 'mb-0'], $result['importedClasses']);
        self::assertSame([], $result['conflicts']);
    }

    private function importsResponsiveAndLegacySpacingClasses(): void
    {
        $result = $this->importer->import($this->blocks(), [], ['mt-md-s', 'mb-4']);

        self::assertSame('s', $result['values']['article_layout']['spacing']['margin']['responsive']['md']['top']);
        self::assertSame('l', $result['values']['article_layout']['spacing']['margin']['bottom']);
        self::assertSame(['mt-md-s', 'mb-4'], $result['importedClasses']);
    }

    private function keepsUnknownClassesUnmanaged(): void
    {
        $result = $this->importer->import($this->blocks(), [], ['mt-whatever', 'project-class']);

        self::assertSame([], $result['importedClasses']);
        self::assertSame([], $result['values']);
    }

    private function keepsConflictingSelectionsUnmanaged(): void
    {
        $result = $this->importer->import($this->blocks(), [], ['full-bg', 'full-width']);

        self::assertSame([], $result['importedClasses']);
        self::assertSame(['full-bg', 'full-width'], $result['conflicts']['article_layout.article.width']);
    }

    private function requiresEveryClassOfAMultiClassOption(): void
    {
        $blocks = $this->blocks();
        $blocks['article_layout']['tabs']['article']['fields']['header'] = [
            'type' => 'select',
            'responsive' => false,
            'breakpoints' => [],
            'options' => [[
                'value' => 'fixed',
                'classes' => 'header-fixed header-fixed-padding',
                'aliases' => [],
            ]],
        ];

        $partial = $this->importer->import($blocks, [], ['header-fixed']);
        self::assertSame([], $partial['importedClasses']);

        $complete = $this->importer->import($blocks, [], ['header-fixed', 'header-fixed-padding']);
        self::assertSame('fixed', $complete['values']['article_layout']['article']['header']);
        self::assertSame(['header-fixed', 'header-fixed-padding'], $complete['importedClasses']);
    }

    private function blocks(): array
    {
        $spacingOptions = [
            ['value' => '0', 'classes' => '0', 'aliases' => []],
            ['value' => 's', 'classes' => 's', 'aliases' => ['2']],
            ['value' => 'l', 'classes' => 'l', 'aliases' => ['4', '5', '6']],
        ];

        return [
            'article_layout' => [
                'tabs' => [
                    'article' => [
                        'fields' => [
                            'width' => [
                                'type' => 'select',
                                'responsive' => false,
                                'breakpoints' => [],
                                'options' => [
                                    ['value' => 'full-bg', 'classes' => 'full-bg', 'aliases' => []],
                                    ['value' => 'full-width', 'classes' => 'full-width', 'aliases' => []],
                                ],
                            ],
                        ],
                    ],
                    'spacing' => [
                        'fields' => [
                            'margin' => [
                                'type' => 'trbl',
                                'responsive' => true,
                                'breakpoints' => ['sm', 'md', 'lg'],
                                'options' => $spacingOptions,
                                'class_patterns' => [
                                    'top' => 'mt-{breakpoint}{value}',
                                    'right' => 'mr-{breakpoint}{value}',
                                    'bottom' => 'mb-{breakpoint}{value}',
                                    'left' => 'ml-{breakpoint}{value}',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private static function assertSame(mixed $expected, mixed $actual): void
    {
        if ($expected === $actual) {
            return;
        }

        throw new RuntimeException(sprintf(
            "Assertion failed.\nExpected: %s\nActual: %s",
            var_export($expected, true),
            var_export($actual, true)
        ));
    }
}
