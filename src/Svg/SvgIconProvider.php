<?php

declare(strict_types=1);

namespace C4Y\One4you\Svg;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class SvgIconProvider
{
    public const SOURCE_OUTLINE = 'outline';
    public const SOURCE_FILLED = 'filled';
    public const SOURCE_CUSTOM = 'custom';

    private const SOURCES = [
        self::SOURCE_OUTLINE,
        self::SOURCE_FILLED,
        self::SOURCE_CUSTOM,
    ];

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        #[Autowire('%one4you.svg.icon_path%')]
        private readonly string $customIconPath,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getInlineSvg(string $identifier): string
    {
        $resolved = $this->resolveIdentifier($identifier);

        if ($resolved === null) {
            $this->logger->warning('Invalid SVG icon identifier "{identifier}".', ['identifier' => $identifier]);

            return '';
        }

        [$source, $name] = $resolved;
        $file = $this->findIconFile($source, $name);

        if ($file === null) {
            $this->logger->warning('SVG icon "{identifier}" was not found.', ['identifier' => $identifier]);

            return '';
        }

        return $this->sanitizeSvg($file, $source, $name);
    }

    public function getPickerIcons(): array
    {
        return [
            self::SOURCE_OUTLINE => $this->buildPickerList(self::SOURCE_OUTLINE),
            self::SOURCE_FILLED => $this->buildPickerList(self::SOURCE_FILLED),
            self::SOURCE_CUSTOM => $this->buildPickerList(self::SOURCE_CUSTOM),
        ];
    }

    private function buildPickerList(string $source): array
    {
        $icons = [];

        foreach ($this->listIconFiles($source) as $name => $file) {
            $icons[] = [
                'name' => $name,
                'source' => $source,
                'insertTag' => '{{svg::'.$source.':'.$name.'}}',
                'svg' => $this->sanitizeSvg($file, $source, $name),
            ];
        }

        return $icons;
    }

    private function resolveIdentifier(string $identifier): ?array
    {
        $identifier = trim($identifier);

        if ($identifier === '') {
            return null;
        }

        if (str_contains($identifier, ':')) {
            [$source, $name] = explode(':', $identifier, 2);

            if (!\in_array($source, self::SOURCES, true) || !$this->isValidName($name)) {
                return null;
            }

            return [$source, $name];
        }

        if (!$this->isValidName($identifier)) {
            return null;
        }

        foreach ([self::SOURCE_CUSTOM, self::SOURCE_OUTLINE, self::SOURCE_FILLED] as $source) {
            if ($this->findIconFile($source, $identifier) !== null) {
                return [$source, $identifier];
            }
        }

        return [self::SOURCE_CUSTOM, $identifier];
    }

    private function findIconFile(string $source, string $name): ?string
    {
        $file = $this->sourceDirectory($source).'/'.$name.'.svg';

        return is_file($file) ? $file : null;
    }

    private function listIconFiles(string $source): array
    {
        $directory = $this->sourceDirectory($source);

        if (!is_dir($directory)) {
            return [];
        }

        $files = [];

        foreach (glob($directory.'/*.svg') ?: [] as $file) {
            $name = basename($file, '.svg');

            if ($this->isValidName($name)) {
                $files[$name] = $file;
            }
        }

        ksort($files, \SORT_NATURAL);

        return $files;
    }

    private function sourceDirectory(string $source): string
    {
        return match ($source) {
            self::SOURCE_OUTLINE => \dirname(__DIR__, 2).'/resources/tabler/outline',
            self::SOURCE_FILLED => \dirname(__DIR__, 2).'/resources/tabler/filled',
            self::SOURCE_CUSTOM => $this->absoluteCustomIconPath(),
            default => throw new \InvalidArgumentException(sprintf('Unsupported SVG icon source "%s".', $source)),
        };
    }

    private function absoluteCustomIconPath(): string
    {
        if (str_starts_with($this->customIconPath, '/')) {
            return rtrim($this->customIconPath, '/');
        }

        return $this->projectDir.'/'.trim($this->customIconPath, '/');
    }

    private function sanitizeSvg(string $file, string $source, string $name): string
    {
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->load($file, \LIBXML_NONET | \LIBXML_NOERROR | \LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded || !$document->documentElement instanceof \DOMElement || $document->documentElement->localName !== 'svg') {
            return '';
        }

        $svg = $document->documentElement;
        $this->sanitizeNode($svg);
        $svg->setAttribute('class', trim('svg-inline svg--'.$source.' svg--'.$name));

        return trim($document->saveXML($svg) ?: '');
    }

    private function sanitizeNode(\DOMNode $node): void
    {
        if ($node instanceof \DOMElement) {
            if (\in_array($node->localName, ['script', 'foreignObject'], true)) {
                $node->parentNode?->removeChild($node);

                return;
            }

            $attributesToRemove = [];

            foreach (iterator_to_array($node->attributes ?? []) as $attribute) {
                $name = $attribute->nodeName;
                $lowerName = strtolower($name);

                if (
                    str_starts_with($lowerName, 'on')
                    || \in_array($lowerName, ['href', 'xlink:href', 'style'], true)
                ) {
                    $attributesToRemove[] = $name;
                }
            }

            foreach ($attributesToRemove as $attribute) {
                $node->removeAttribute($attribute);
            }
        }

        foreach (iterator_to_array($node->childNodes) as $childNode) {
            $this->sanitizeNode($childNode);
        }
    }

    private function isValidName(string $name): bool
    {
        return (bool) preg_match('/^[a-z0-9_-]+$/', $name);
    }
}
