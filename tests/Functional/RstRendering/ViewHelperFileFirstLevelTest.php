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

class ViewHelperFileFirstLevelTest extends TestCase
{
    /**
     * @var vfsStreamDirectory
     */
    private $vfs;

    /**
     * the generated file is compared against this fixture file
     * @var string
     */
    private $fixtureFilePath = __DIR__ . '/../../Fixtures/rendering/output/Documentation/typo3/backend/9.4/ModuleLink.rst';

    /**
     * output of the generation process
     * @var string
     */
    private $generatedFilePath = 'outputDir/public/typo3/backend/9.4/ModuleLink.rst';

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
        $index = 5;
        $this->assertSame('moduleLink' . PHP_EOL, $output[$index]);
    }

    /**
     * @test
     */
    public function headlineIsProperlyDecorated()
    {
        $output = file($this->vfs->getChild($this->generatedFilePath)->url());
        // first line is include, then empty, then upper headline decoration, then text, then lower headline decoration
        $headlineTextIndex = 5;
        $lengthOfHeadline = strlen($output[$headlineTextIndex]);
        $this->assertSame($lengthOfHeadline, strlen($output[$headlineTextIndex - 1]));
        $this->assertRegExp('/^[=]+$/', $output[$headlineTextIndex - 1]);
        $this->assertSame($lengthOfHeadline, strlen($output[$headlineTextIndex + 1]));
        $this->assertRegExp('/^[=]+$/', $output[$headlineTextIndex + 1]);
    }

    /**
     * @test
     */
    public function descriptionGetsRendered()
    {
        $output = file($this->vfs->getChild($this->generatedFilePath)->url());
        $index = 9;
        $this->assertSame('Create internal link within backend app' . PHP_EOL, $output[$index]);
    }

    /**
     * @test
     */
    public function headerIdentifierGetsRendered()
    {
        $output = file($this->vfs->getChild($this->generatedFilePath)->url());
        $index = 2;
        $this->assertSame('.. _typo3-backend-modulelink:' . PHP_EOL, $output[$index]);
    }

    /**
     * @test
     */
    public function generatedFileIsSameAsFixture()
    {
        $this->assertSame(file_get_contents($this->fixtureFilePath),
            file_get_contents($this->vfs->getChild($this->generatedFilePath)->url()));
    }
}
