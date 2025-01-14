<?php
declare(strict_types=1);

namespace NamelessCoder\FluidDocumentationGenerator\Tests\Functional\RstRendering;

use NamelessCoder\FluidDocumentationGenerator\Data\DataFileResolver;
use NamelessCoder\FluidDocumentationGenerator\Entity\Schema;
use NamelessCoder\FluidDocumentationGenerator\Export\RstExporter;
use NamelessCoder\FluidDocumentationGenerator\SchemaDocumentationGenerator;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

class IndexForViewhelperGroupWithSubGroupsTest extends TestCase
{
    /**
     * @var vfsStreamDirectory
     */
    private $vfs;

    /**
     * the generated file is compared against this fixture file
     * @var string
     */
    private $fixtureFilePath = __DIR__ . '/../../Fixtures/rendering/output/Documentation/typo3/backend/9.4/ModuleLayout/Index.rst';

    /**
     * output of the generation process
     * @var string
     */
    private $generatedFilePath = 'outputDir/public/typo3/backend/9.4/ModuleLayout/Index.rst';

    protected function setUp()
    {
        $this->vfs = vfsStream::setup('outputDir');
        $this->vfs->addChild(vfsStream::newDirectory('cache'));
        $dataFileResolver = DataFileResolver::getInstance(vfsStream::url('outputDir'));
        $dataFileResolver->setResourcesDirectory(__DIR__ . '/../../../resources/');
        $dataFileResolver->setSchemasDirectory(__DIR__ . '/../../Fixtures/rendering/input/');
        $schemaDocumentationGenerator = new SchemaDocumentationGenerator(
            [
                new RstExporter()
            ]
        );
        $schemaDocumentationGenerator->generateFilesForRoot();
        foreach ($dataFileResolver->resolveInstalledVendors() as $vendor) {
            $schemaDocumentationGenerator->generateFilesForVendor($vendor);
            foreach ($vendor->getPackages() as $package) {
                $schemaDocumentationGenerator->generateFilesForPackage($package);
                foreach ($package->getVersions() as $version) {
                    $schemaDocumentationGenerator->generateFilesForSchema(new Schema($version));
                }
            }
        }
    }

    /**
     * @test
     */
    public function fileIsCreated()
    {
        $this->assertTrue($this->vfs->hasChild($this->generatedFilePath));
    }

    /**
     * @test
     */
    public function includeClausePointsToSettingsCfg()
    {
        $output = file($this->vfs->getChild($this->generatedFilePath)->url());
        $this->assertSame('.. include:: /Includes.rst.txt' . PHP_EOL, $output[0]);
    }

    /**
     * @test
     */
    public function headlineAsExpected()
    {
        $output = file($this->vfs->getChild($this->generatedFilePath)->url());
        // first line is include, then empty, then upper headline decoration, then text -> fourth line
        $index = 3;
        $this->assertSame('moduleLayout' . PHP_EOL, $output[$index]);
    }

    /**
     * @test
     */
    public function headlineIsProperlyDecorated()
    {
        $output = file($this->vfs->getChild($this->generatedFilePath)->url());
        // first line is include, then empty, then upper headline decoration, then text, then lower headline decoration
        $headlineTextIndex = 3;
        $lengthOfHeadline = strlen($output[$headlineTextIndex]);
        $this->assertSame($lengthOfHeadline, strlen($output[$headlineTextIndex - 1]));
        $this->assertRegExp('/^[=]+$/', $output[$headlineTextIndex - 1]);
        $this->assertSame($lengthOfHeadline, strlen($output[$headlineTextIndex + 1]));
        $this->assertRegExp('/^[=]+$/', $output[$headlineTextIndex + 1]);
    }

    /**
     * @test
     */
    public function viewHelperCountIsIntegrated()
    {
        $output = file($this->vfs->getChild($this->generatedFilePath)->url());
        $index = 7;
        $this->assertSame('* 2 ViewHelpers documented' . PHP_EOL, $output[$index]);
    }

    /**
     * @test
     */
    public function subNamespacesCountIsIntegrated()
    {
        $output = file($this->vfs->getChild($this->generatedFilePath)->url());
        $index = 8;
        $this->assertSame('* 1 Sub namespaces' . PHP_EOL, $output[$index]);
    }

    /**
     * @test
     */
    public function tocTreeContainsSubDirectoriesAsExpected()
    {
        $output = file($this->vfs->getChild($this->generatedFilePath)->url());
        $index = 14;
        $this->assertSame('   */Index' . PHP_EOL, $output[$index]);
        $this->assertSame('   MenuItem' . PHP_EOL, $output[$index + 1]);
        $this->assertSame('   Menu' . PHP_EOL, $output[$index + 2]);
    }

    /**
     * @test
     */
    public function generatedFileIsSameAsFixture()
    {
        $this->assertSame(trim(file_get_contents($this->fixtureFilePath)),
            trim(file_get_contents($this->vfs->getChild($this->generatedFilePath)->url())));
    }
}
