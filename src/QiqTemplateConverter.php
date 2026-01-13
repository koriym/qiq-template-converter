<?php

declare(strict_types=1);

namespace Koriym\QiqTemplateConverter;

use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function assert;
use function explode;
use function file_get_contents;
use function file_put_contents;
use function fnmatch;
use function is_dir;
use function is_string;
use function preg_replace;

final class QiqTemplateConverter
{
    private bool $dryRun = false;
    private bool $backup = false;
    private bool $showDiff = false;
    private int $filesProcessed = 0;
    private int $totalReplacements = 0;

    /** @var array<string> */
    private array $excludePatterns = [];

    /** @param array{dry-run?: bool, backup?: bool, show-diff?: bool, exclude?: array<string>} $options */
    public function __construct(array $options = [])
    {
        $this->dryRun = $options['dry-run'] ?? false;
        $this->backup = $options['backup'] ?? false;
        $this->showDiff = $options['show-diff'] ?? false;
        $this->excludePatterns = $options['exclude'] ?? [];
    }

    public function convert(string $directory): void
    {
        if (! is_dir($directory)) {
            throw new InvalidArgumentException("Directory not found: {$directory}");
        }

        $files = $this->findPhpFiles($directory);

        foreach ($files as $file) {
            $this->processFile($file);
        }
    }

    /** @return list<string> */
    private function findPhpFiles(string $directory): array
    {
        $files = [];
        /** @var RecursiveIteratorIterator<RecursiveDirectoryIterator> $iterator */
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            assert($file instanceof SplFileInfo);
            if ($file->isFile() && $file->getExtension() === 'php') {
                $filePath = $file->getPathname();

                // Check exclude patterns
                if ($this->shouldExclude($filePath)) {
                    continue;
                }

                $files[] = $filePath;
            }
        }

        return $files;
    }

    private function shouldExclude(string $filePath): bool
    {
        foreach ($this->excludePatterns as $pattern) {
            if (fnmatch($pattern, $filePath)) {
                return true;
            }
        }

        return false;
    }

    private function processFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return;
        }

        $originalContent = $content;
        $replacements = 0;

        // Convert Qiq syntax: {{...}} blocks
        // We need to apply this multiple times in case there are multiple $this-> in one block
        $count = 0;
        do {
            $result = preg_replace(
                '/(\{\{[^}]*?)\$this->(\w+)([^}]*?\}\})/s',
                '$1\$$2$3',
                $content,
                -1,
                $count,
            );
            if (! is_string($result)) {
                break;
            }

            $content = $result;
            $replacements += (int) $count;
        } while ($count > 0);

        // Convert PHP syntax: $this->variable (but not $this->method())
        // Exclude method calls by using negative lookahead for (
        $count = 0;
        $result = preg_replace(
            '/\$this->(\w+)\b(?!\s*\()/s',
            '\$$1',
            $content,
            -1,
            $count,
        );
        if (is_string($result)) {
            $content = $result;
            $replacements += (int) $count;
        }

        if ($replacements > 0) {
            $this->filesProcessed++;
            $this->totalReplacements += $replacements;

            if ($this->showDiff) {
                $this->showDifference($originalContent, $content);
            }

            if (! $this->dryRun) {
                if ($this->backup) {
                    $this->createBackup($filePath, $originalContent);
                }

                file_put_contents($filePath, $content);
            }
        }
    }

    private function createBackup(string $filePath, string $content): void
    {
        $backupPath = $filePath . '.bak';
        file_put_contents($backupPath, $content);
    }

    private function showDifference(string $original, string $modified): void
    {
        $originalLines = explode("\n", $original);
        $modifiedLines = explode("\n", $modified);

        foreach ($modifiedLines as $i => $line) {
            if (isset($originalLines[$i]) && $originalLines[$i] !== $line) {
                echo '  - ' . $originalLines[$i] . "\n";
                echo '  + ' . $line . "\n";
            }
        }

        echo "\n";
    }

    public function getFilesProcessed(): int
    {
        return $this->filesProcessed;
    }

    public function getTotalReplacements(): int
    {
        return $this->totalReplacements;
    }
}
