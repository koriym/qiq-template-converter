# Qiq Template Converter (1.x → 3.x)

A command-line tool to automatically convert Qiq 1.x template files to Qiq 3.x syntax.

## What it does

This tool converts template variable access from Qiq 1.x style (`$this->variable`) to Qiq 3.x style (`$variable`), while preserving helper method calls like `$this->h()`, `$this->a()`, etc.

### Conversion Examples

**Before (Qiq 1.x):**
```php
{{h $this->name }}
<?= $this->email ?>
<?= $this->h($this->title) ?>
$this->items[0]
```

**After (Qiq 3.x):**
```php
{{h $name }}
<?= $email ?>
<?= $this->h($title) ?>
$items[0]
```

### What is NOT converted

- Helper method calls: `$this->h()`, `$this->a()`, `$this->u()`, `$this->c()`
- Other methods: `$this->setView()`, `$this->getView()`
- Comments: Variables in PHP comments remain unchanged

## Installation

```bash
composer install
```

## Usage

### Basic Usage

```bash
php convert-qiq-templates.php /path/to/templates
```

### Options

#### Dry Run Mode
Preview changes without modifying files:
```bash
php convert-qiq-templates.php /path/to/templates --dry-run
```

#### Backup Mode
Create `.bak` backup files before conversion:
```bash
php convert-qiq-templates.php /path/to/templates --backup
```

#### Show Diff
Display line-by-line differences:
```bash
php convert-qiq-templates.php /path/to/templates --show-diff
```

#### Exclude Patterns
Exclude specific files or directories:
```bash
php convert-qiq-templates.php /path/to/templates --exclude="*/vendor/*" --exclude="*/cache/*"
```

#### Combine Options
```bash
php convert-qiq-templates.php /path/to/templates --dry-run --show-diff
```

### Help

```bash
php convert-qiq-templates.php --help
```

## Output Example

```
Converting Qiq 1.x templates to 3.x...

Processed: /path/to/template/User.php (3 replacements)
Processed: /path/to/template/Error.php (2 replacements)
Processed: /path/to/template/Index.php (5 replacements)

Summary:
- Files processed: 3
- Total replacements: 10
- Completed successfully
```

## How It Works

The converter uses regular expressions to perform the following transformations:

1. **Qiq Syntax (`{{...}}`)**: Converts `$this->variable` to `$variable` within Qiq template blocks
2. **PHP Syntax (`<?php ?>`, `<?= ?>`)**: Converts `$this->variable` to `$variable`, but excludes method calls (identified by `(` after the variable name)

### Regular Expression Patterns

```php
// Qiq syntax: {{...}} blocks
'/(\{\{[^}]*?)\$this->(\w+)([^}]*?\}\})/s'
// Converts: {{h $this->name }} → {{h $name }}

// PHP syntax: excluding method calls
'/\$this->(\w+)(?!\s*\()/s'
// Converts: $this->email → $email
// Preserves: $this->h() (method call)
```

## Best Practices

1. **Always use `--dry-run` first** to preview changes before applying them
2. **Use `--backup`** if you want to keep original files
3. **Use version control** (Git) before running the converter
4. **Test your templates** after conversion to ensure everything works correctly

## Testing

Run the test suite:

```bash
composer test
```

## Development Commands

```text
bin               bamarni/composer-bin-plugin command
test              Run unit tests
coverage          Generate test coverage report
cs                Check the coding style
cs-fix            Fix the coding style
baseline          Generate baseline for PHPStan and Psalm
crc               Run composer require checker
metrics           Build metrics report
clean             Remove temporary files
sa                Run static analysis
tests             Run tests and quality checks
build             Build project
run-script --list List all available commands
```

## Troubleshooting

### Issue: Files not being converted

**Solution**: Check that:
- Files have `.php` extension
- Files are in the specified directory or subdirectories
- Files are not matching any `--exclude` patterns

### Issue: Incorrect conversions

**Solution**:
1. Use `--dry-run --show-diff` to see what will be changed
2. Review the changes and adjust exclude patterns if needed
3. Report issues with sample code that causes problems

## Contributing

Contributions are welcome! Please submit issues and pull requests.

## License

MIT License
