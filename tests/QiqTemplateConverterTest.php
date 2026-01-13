<?php

declare(strict_types=1);

namespace Koriym\QiqTemplateConverter;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

use function array_diff;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function rmdir;
use function scandir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

final class QiqTemplateConverterTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/qiq-converter-test-' . uniqid();
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }

    public function testIsInstanceOfQiqTemplateConverter(): void
    {
        $converter = new QiqTemplateConverter();
        $this->assertInstanceOf(QiqTemplateConverter::class, $converter);
    }

    public function testConvertQiqSyntaxVariable(): void
    {
        $content = '{{h $this->name }}';
        $expected = '{{h $name }}';

        $file = $this->createTempFile($content);
        $converter = new QiqTemplateConverter();
        $converter->convert($this->tempDir);

        $result = file_get_contents($file);
        $this->assertSame($expected, $result);
        $this->assertSame(1, $converter->getTotalReplacements());
    }

    public function testConvertPhpSyntaxVariable(): void
    {
        $content = '<?= $this->email ?>';
        $expected = '<?= $email ?>';

        $file = $this->createTempFile($content);
        $converter = new QiqTemplateConverter();
        $converter->convert($this->tempDir);

        $result = file_get_contents($file);
        $this->assertSame($expected, $result);
        $this->assertSame(1, $converter->getTotalReplacements());
    }

    public function testConvertVariableInsideHelperMethod(): void
    {
        $content = '<?= $this->h($this->title) ?>';
        $expected = '<?= $this->h($title) ?>';

        $file = $this->createTempFile($content);
        $converter = new QiqTemplateConverter();
        $converter->convert($this->tempDir);

        $result = file_get_contents($file);
        $this->assertSame($expected, $result);
        $this->assertSame(1, $converter->getTotalReplacements());
    }

    public function testConvertArrayAccess(): void
    {
        $content = '$this->items[0]';
        $expected = '$items[0]';

        $file = $this->createTempFile($content);
        $converter = new QiqTemplateConverter();
        $converter->convert($this->tempDir);

        $result = file_get_contents($file);
        $this->assertSame($expected, $result);
        $this->assertSame(1, $converter->getTotalReplacements());
    }

    public function testDoNotConvertHelperMethodCall(): void
    {
        $content = '$this->h($name)';
        $expected = '$this->h($name)';

        $file = $this->createTempFile($content);
        $converter = new QiqTemplateConverter();
        $converter->convert($this->tempDir);

        $result = file_get_contents($file);
        $this->assertSame($expected, $result);
        $this->assertSame(0, $converter->getTotalReplacements());
    }

    public function testDoNotConvertOtherMethodCall(): void
    {
        $content = '$this->getView()';
        $expected = '$this->getView()';

        $file = $this->createTempFile($content);
        $converter = new QiqTemplateConverter();
        $converter->convert($this->tempDir);

        $result = file_get_contents($file);
        $this->assertSame($expected, $result);
        $this->assertSame(0, $converter->getTotalReplacements());
    }

    public function testMultipleVariablesInQiqBlock(): void
    {
        $content = '{{h $this->firstName }} {{h $this->lastName }}';
        $expected = '{{h $firstName }} {{h $lastName }}';

        $file = $this->createTempFile($content);
        $converter = new QiqTemplateConverter();
        $converter->convert($this->tempDir);

        $result = file_get_contents($file);
        $this->assertSame($expected, $result);
        $this->assertSame(2, $converter->getTotalReplacements());
    }

    public function testComplexTemplate(): void
    {
        $content = <<<'PHP'
<?php $this->var ?>
{{h $this->name }}
<?= $this->h($this->email) ?>
$this->items[0]
$this->h($name)
$this->getView()
PHP;

        $expected = <<<'PHP'
<?php $var ?>
{{h $name }}
<?= $this->h($email) ?>
$items[0]
$this->h($name)
$this->getView()
PHP;

        $file = $this->createTempFile($content);
        $converter = new QiqTemplateConverter();
        $converter->convert($this->tempDir);

        $result = file_get_contents($file);
        $this->assertSame($expected, $result);
        $this->assertSame(4, $converter->getTotalReplacements());
    }

    public function testDryRunMode(): void
    {
        $content = '{{h $this->name }}';
        $expected = '{{h $this->name }}'; // Should not change

        $file = $this->createTempFile($content);
        $converter = new QiqTemplateConverter(['dry-run' => true]);
        $converter->convert($this->tempDir);

        $result = file_get_contents($file);
        $this->assertSame($expected, $result);
        $this->assertSame(1, $converter->getTotalReplacements()); // Counted but not written
    }

    public function testBackupMode(): void
    {
        $content = '{{h $this->name }}';
        $expected = '{{h $name }}';

        $file = $this->createTempFile($content);
        $converter = new QiqTemplateConverter(['backup' => true]);
        $converter->convert($this->tempDir);

        $result = file_get_contents($file);
        $backupExists = file_exists($file . '.bak');
        $backupContent = $backupExists ? file_get_contents($file . '.bak') : '';

        $this->assertSame($expected, $result);
        $this->assertTrue($backupExists);
        $this->assertSame($content, $backupContent);
    }

    public function testExcludePattern(): void
    {
        $content = '{{h $this->name }}';

        $file = $this->createTempFile($content, 'excluded.php');
        $converter = new QiqTemplateConverter(['exclude' => ['*/excluded.php']]);
        $converter->convert($this->tempDir);

        $result = file_get_contents($file);
        $this->assertSame($content, $result); // Should not change
        $this->assertSame(0, $converter->getTotalReplacements());
    }

    public function testInvalidDirectoryThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Directory not found:');

        $converter = new QiqTemplateConverter();
        $converter->convert('/nonexistent/directory');
    }

    private function createTempFile(string $content, string $filename = 'test.php'): string
    {
        $file = $this->tempDir . '/' . $filename;
        file_put_contents($file, $content);

        return $file;
    }
}
