#!/usr/bin/env php
<?php
/**
 * Qiq 1.x to 3.x Template Converter CLI
 *
 * Converts Qiq 1.x template syntax to 3.x by removing $this-> from variable accesses
 * while preserving method calls like $this->h(), $this->a(), etc.
 */

declare(strict_types=1);

use Koriym\QiqTemplateConverter\QiqTemplateConverter;

// Autoload
$autoloadPaths = [
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/../../autoload.php',
];

foreach ($autoloadPaths as $autoloadPath) {
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
        break;
    }
}

if (! class_exists(QiqTemplateConverter::class)) {
    echo "Error: Unable to load QiqTemplateConverter class.\n";
    echo "Please run 'composer install' first.\n";
    exit(1);
}

// CLI Handling
function showHelp(): void
{
    echo <<<HELP
Qiq 1.x to 3.x Template Converter

Usage:
  php convert-qiq-templates.php <directory> [options]

Arguments:
  directory     Path to the templates directory

Options:
  --dry-run     Preview changes without modifying files
  --backup      Create .bak backup files before conversion
  --show-diff   Display differences for each file
  --exclude     Exclude pattern (can be used multiple times)
                Example: --exclude="*/vendor/*" --exclude="*/cache/*"
  --help        Show this help message

Examples:
  php convert-qiq-templates.php /path/to/templates
  php convert-qiq-templates.php /path/to/templates --dry-run
  php convert-qiq-templates.php /path/to/templates --backup
  php convert-qiq-templates.php /path/to/templates --dry-run --show-diff
  php convert-qiq-templates.php /path/to/templates --exclude="*/vendor/*"

HELP;
}

/** @return array{0: string|null, 1: array{dry-run: bool, backup: bool, show-diff: bool, exclude: array<string>}} */
function parseArguments(array $argv): array
{
    $directory = null;
    $options = [
        'dry-run' => false,
        'backup' => false,
        'show-diff' => false,
        'exclude' => [],
    ];

    for ($i = 1; $i < count($argv); $i++) {
        $arg = $argv[$i];

        if ($arg === '--help' || $arg === '-h') {
            showHelp();
            exit(0);
        } elseif ($arg === '--dry-run') {
            $options['dry-run'] = true;
        } elseif ($arg === '--backup') {
            $options['backup'] = true;
        } elseif ($arg === '--show-diff') {
            $options['show-diff'] = true;
        } elseif (str_starts_with($arg, '--exclude=')) {
            $pattern = substr($arg, strlen('--exclude='));
            $options['exclude'][] = trim($pattern, '"\'');
        } elseif (! str_starts_with($arg, '--')) {
            $directory = $arg;
        }
    }

    return [$directory, $options];
}

// Main execution
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

if ($argc < 2) {
    showHelp();
    exit(1);
}

try {
    [$directory, $options] = parseArguments($argv);

    if ($directory === null) {
        echo "Error: Directory argument is required.\n\n";
        showHelp();
        exit(1);
    }

    echo "Converting Qiq 1.x templates to 3.x...\n";
    if ($options['dry-run']) {
        echo "[DRY RUN MODE - No files will be modified]\n";
    }

    echo "\n";

    $converter = new QiqTemplateConverter($options);
    $converter->convert($directory);

    echo "\nSummary:\n";
    echo "- Files processed: " . $converter->getFilesProcessed() . "\n";
    echo "- Total replacements: " . $converter->getTotalReplacements() . "\n";
    echo "- Completed successfully\n";

    exit(0);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
