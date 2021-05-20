<?php
namespace Bolt\Thumbs\Tests;

use Bolt\Filesystem\Adapter\Local;
use Bolt\Filesystem\Filesystem;
use Bolt\Filesystem\Handler\Image;
use Bolt\Filesystem\Handler\Image\Dimensions;
use Bolt\Thumbs\Action;
use Bolt\Thumbs\Creator;
use Bolt\Thumbs\Transaction;

class CreatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var Filesystem */
    protected $fs;

    /** @var Image */
    protected $logoJpg;
    /** @var Image 1000x667 */
    protected $landscapeImage;
    /** @var Image 427x640 */
    protected $portraitImage;
    /** @var Image */
    protected $svgImage;

    public function setup()
    {
        $this->fs = new Filesystem(new Local(__DIR__ . '/images'));
        $this->logoJpg = $this->fs->getImage('generic-logo.jpg');
        $this->landscapeImage = $this->fs->getImage('samples/sample1.jpg');
        $this->portraitImage = $this->fs->getImage('samples/sample2.jpg');
        $this->svgImage = $this->fs->getImage('samples/nut.svg');
    }

    /**
     * @testdox When target dimensions are (0, 0), thumbnail dimensions are set to image dimensions
     */
    public function testFallbacksForAutoscale()
    {
        $transaction = new Transaction($this->portraitImage);

        $result = (new Creator())->create($transaction);

        $this->assertDimensions(new Dimensions(427, 640), $result);
    }

    /**
     * @testdox When target width is 0, thumbnail width is autoscaled based on image ratio
     */
    public function testFallbacksForHorizontalAutoscale()
    {
        $transaction = new Transaction($this->portraitImage, Action::CROP, new Dimensions(0, 320));

        $result = (new Creator())->create($transaction);

        $this->assertDimensions(new Dimensions(214, 320), $result);
    }

    /**
     * @testdox When target height is 0, thumbnail height is autoscaled based on image ratio
     */
    public function testFallbacksForVerticalAutoscale()
    {
        $transaction = new Transaction($this->landscapeImage, Action::CROP, new Dimensions(500, 0));

        $result = (new Creator())->create($transaction);

        $this->assertDimensions(new Dimensions(500, 334), $result);
    }

    /**
     * @testdox When upscaling is allowed, thumbnail is enlarged to target dimensions
     */
    public function testAllowUpscaling()
    {
        $upscaled = new Dimensions(800, 600);
        $transaction = new Transaction($this->logoJpg, Action::CROP, $upscaled);

        $result = (new Creator(false))->create($transaction);

        $this->assertDimensions($upscaled, $result);
    }

    /**
     * @testdox When upscaling is not allowed, target dimensions are reduced to current image dimensions
     */
    public function testLimitUpscaling()
    {
        $upscaled = new Dimensions(800, 600);
        $original = new Dimensions(624, 351);

        $transaction = new Transaction($this->logoJpg, Action::CROP, $upscaled);

        $result = (new Creator(true))->create($transaction);

        $this->assertDimensions($original, $result);
    }

    public function testLandscapeCrop()
    {
        $expected = new Dimensions(500, 200);
        $transaction = new Transaction($this->landscapeImage, Action::CROP, $expected);

        $result = (new Creator())->create($transaction);

        $this->assertDimensions($expected, $result);
    }

    public function testLandscapeResize()
    {
        $transaction = new Transaction($this->landscapeImage, Action::RESIZE, new Dimensions(500, 200));

        $result = (new Creator())->create($transaction);

        $this->assertDimensions(new Dimensions(299, 200), $result);
    }

    public function testLandscapeFit()
    {
        $expected = new Dimensions(500, 200);
        $transaction = new Transaction($this->landscapeImage, Action::FIT, $expected);

        $result = (new Creator())->create($transaction);

        $this->assertDimensions($expected, $result);
    }

    public function testLandscapeBorder()
    {
        $expected = new Dimensions(500, 200);
        $transaction = new Transaction($this->landscapeImage, Action::BORDER, $expected);

        $result = (new Creator())->create($transaction);

        $this->assertDimensions($expected, $result);
    }

    public function testPortraitCrop()
    {
        $expected = new Dimensions(200, 500);
        $transaction = new Transaction($this->portraitImage, Action::CROP, $expected);

        $result = (new Creator())->create($transaction);

        $this->assertDimensions($expected, $result);
    }

    public function testPortraitResize()
    {
        $transaction = new Transaction($this->portraitImage, Action::RESIZE, new Dimensions(200, 500));

        $result = (new Creator())->create($transaction);

        $this->assertDimensions(new Dimensions(200, 299), $result);
    }

    public function testPortraitFit()
    {
        $expected = new Dimensions(200, 500);
        $transaction = new Transaction($this->portraitImage, Action::FIT, $expected);

        $result = (new Creator())->create($transaction);

        $this->assertDimensions($expected, $result);
    }

    public function testPortraitBorder()
    {
        $expected = new Dimensions(200, 500);
        $transaction = new Transaction($this->portraitImage, Action::BORDER, $expected);

        $result = (new Creator())->create($transaction);

        $this->assertDimensions($expected, $result);
    }

    public function testSvg()
    {
        $transaction = new Transaction($this->portraitImage, Action::RESIZE, new Dimensions(200, 500));

        $result = (new Creator())->create($transaction);

        $this->assertDimensions(new Dimensions(200, 299), $result);
    }

    /**
     * @param Dimensions        $expected
     * @param Dimensions|string $actual
     */
    protected function assertDimensions(Dimensions $expected, $actual)
    {
        if (is_string($actual)) {
            $info = Image\Info::createFromString($actual);
            $actual = new Dimensions($info->getWidth(), $info->getHeight());
        }
        $this->assertEquals($expected, $actual, "Expected dimension $expected does not equal actual $actual");
    }
}
