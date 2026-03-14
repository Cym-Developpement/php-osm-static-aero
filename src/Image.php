<?php

namespace Ycdev\OsmStaticAero;

/**
 * Ycdev\OsmStaticAero\Image provides GD image manipulation with tile caching.
 *
 * @package Ycdev\OsmStaticAero
 * @author Franck Alary <https://github.com/DantSu>
 * @see https://github.com/DantSu/php-image-editor Original project
 */
class Image
{
    const ALIGN_LEFT   = 'left';
    const ALIGN_CENTER = 'center';
    const ALIGN_RIGHT  = 'right';
    const ALIGN_TOP    = 'top';
    const ALIGN_MIDDLE = 'middle';
    const ALIGN_BOTTOM = 'bottom';

    /**
     * @var resource|\GdImage|null
     */
    private $image;
    /**
     * @var int|null
     */
    private $width;
    /**
     * @var int|null
     */
    private $height;
    /**
     * @var int|null
     */
    private $type;

    public function __clone()
    {
        $srcInstance = $this->image;
        $this
            ->resetCanvas($this->width, $this->height)
            ->pasteGdImageOn($srcInstance, $this->width, $this->height, 0, 0);
    }

    /**
     * @return int Image width
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * @return int Image height
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * @return int Image type (1=GIF, 2=JPG, 3=PNG)
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @return resource|\GdImage|null
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param mixed $image
     * @return bool
     */
    public static function isGdImage($image): bool
    {
        return \is_resource($image) || (\is_object($image) && $image instanceof \GdImage);
    }

    /**
     * @return bool
     */
    public function isImageDefined(): bool
    {
        return static::isGdImage($this->image);
    }

    // ===================== CREATE / DESTROY =====================

    /**
     * @param int $width
     * @param int $height
     * @return Image
     */
    public static function newCanvas(int $width, int $height): Image
    {
        return (new Image)->resetCanvas($width, $height);
    }

    /**
     * @param int $width
     * @param int $height
     * @return $this
     */
    public function resetCanvas(int $width, int $height): Image
    {
        if (($this->image = \imagecreatetruecolor($width, $height)) === false) {
            $this->resetFields();
            return $this;
        }

        \imagealphablending($this->image, false);
        \imagesavealpha($this->image, true);
        \imagefill($this->image, 0, 0, \imagecolorallocatealpha($this->image, 0, 0, 0, 127));

        $this->width  = $width;
        $this->height = $height;
        return $this;
    }

    /**
     * @param string $path
     * @return Image
     */
    public static function fromPath(string $path): Image
    {
        return (new Image)->path($path);
    }

    /**
     * @param string $path
     * @return $this
     */
    public function path(string $path): Image
    {
        $imageSize = \getimagesize($path);

        if ($imageSize === false) {
            return $this;
        }

        list($this->width, $this->height, $this->type, $attr) = $imageSize;

        switch ($this->type) {
            case 1:
                $this->image = \imagecreatefromgif($path);
                break;
            case 2:
                $this->image = \imagecreatefromjpeg($path);
                break;
            case 3:
                $this->image = \imagecreatefrompng($path);
                break;
        }

        if ($this->image === false) {
            return $this->resetFields();
        }

        if (!\imageistruecolor($this->image)) {
            \imagepalettetotruecolor($this->image);
        }

        \imagealphablending($this->image, false);
        \imagesavealpha($this->image, true);

        return $this;
    }

    /**
     * @param string $data
     * @return Image
     */
    public static function fromData(string $data): Image
    {
        return (new Image)->data($data);
    }

    /**
     * @param string $data
     * @return $this
     */
    public function data(string $data): Image
    {
        if (($this->image = \imagecreatefromstring($data)) === false) {
            return $this->resetFields();
        }

        $this->width  = \imagesx($this->image);
        $this->height = \imagesy($this->image);
        $this->type   = 3;

        if (!\imageistruecolor($this->image)) {
            \imagepalettetotruecolor($this->image);
        }

        \imagealphablending($this->image, false);
        \imagesavealpha($this->image, true);

        return $this;
    }

    /**
     * @param string $base64
     * @return Image
     */
    public static function fromBase64(string $base64): Image
    {
        return (new Image)->base64($base64);
    }

    /**
     * @param string $base64
     * @return $this
     */
    public function base64(string $base64): Image
    {
        return $this->data(\base64_decode($base64));
    }

    /**
     * @param array $file
     * @return Image
     */
    public static function fromForm(array $file): Image
    {
        return (new Image)->form($file);
    }

    /**
     * @param array $file
     * @return $this
     */
    public function form(array $file): Image
    {
        return $this->path($file['tmp_name']);
    }

    /**
     * @return $this
     */
    public function destroy(): Image
    {
        if ($this->isImageDefined()) {
            \imagedestroy($this->image);
        }
        $this->resetFields();
        return $this;
    }

    /**
     * @return $this
     */
    private function resetFields(): Image
    {
        $this->image  = null;
        $this->type   = null;
        $this->width  = null;
        $this->height = null;
        return $this;
    }

    // ===================== UTILS =====================

    /**
     * @param int|string $posX
     * @param int $width
     * @return int
     */
    private function convertPosX($posX, int $width = 0): int
    {
        switch ($posX) {
            case static::ALIGN_LEFT:
                return 0;
            case static::ALIGN_CENTER:
                return \round($this->width / 2 - $width / 2);
            case static::ALIGN_RIGHT:
                return $this->width - $width;
        }
        return \round($posX);
    }

    /**
     * @param int|string $posY
     * @param int $height
     * @return int
     */
    private function convertPosY($posY, int $height = 0): int
    {
        switch ($posY) {
            case static::ALIGN_TOP:
                return 0;
            case static::ALIGN_MIDDLE:
                return \round($this->height / 2 - $height / 2);
            case static::ALIGN_BOTTOM:
                return $this->height - $height;
        }
        return \round($posY);
    }

    /**
     * @param string $stringColor
     * @return string
     */
    private static function formatColor(string $stringColor): string
    {
        $stringColor = \trim(\str_replace('#', '', $stringColor));
        switch (\mb_strlen($stringColor)) {
            case 3:
                $r = \substr($stringColor, 0, 1);
                $g = \substr($stringColor, 1, 1);
                $b = \substr($stringColor, 2, 1);
                return $r . $r . $g . $g . $b . $b . '00';
            case 6:
                return $stringColor . '00';
            case 8:
                return $stringColor;
            default:
                return '00000000';
        }
    }

    /**
     * @param string $color
     * @return int|false
     */
    private function colorAllocate(string $color)
    {
        $color = static::formatColor($color);
        $red   = \hexdec(\substr($color, 0, 2));
        $green = \hexdec(\substr($color, 2, 2));
        $blue  = \hexdec(\substr($color, 4, 2));
        $alpha = \floor(\hexdec(\substr($color, 6, 2)) / 2);

        $colorId = \imagecolorexactalpha($this->image, $red, $green, $blue, $alpha);
        if ($colorId === -1) {
            $colorId = \imagecolorallocatealpha($this->image, $red, $green, $blue, $alpha);
        }

        return $colorId;
    }

    // ===================== PASTE =====================

    /**
     * @param Image $image
     * @param int|string $posX
     * @param int|string $posY
     * @return $this
     */
    public function pasteOn(Image $image, $posX = Image::ALIGN_CENTER, $posY = Image::ALIGN_MIDDLE): Image
    {
        if (!$this->isImageDefined() || !$image->isImageDefined()) {
            return $this;
        }

        return $this->pasteGdImageOn($image->getImage(), $image->getWidth(), $image->getHeight(), $posX, $posY);
    }

    /**
     * @param resource|\GdImage $image
     * @param int $imageWidth
     * @param int $imageHeight
     * @param int|string $posX
     * @param int|string $posY
     * @return $this
     */
    public function pasteGdImageOn($image, int $imageWidth, int $imageHeight, $posX = Image::ALIGN_CENTER, $posY = Image::ALIGN_MIDDLE): Image
    {
        if (!$this->isImageDefined() || !static::isGdImage($image)) {
            return $this;
        }

        $posX = $this->convertPosX($posX, $imageWidth);
        $posY = $this->convertPosY($posY, $imageHeight);

        \imagesavealpha($this->image, false);
        \imagealphablending($this->image, true);
        \imagecopy($this->image, $image, $posX, $posY, 0, 0, $imageWidth, $imageHeight);
        \imagealphablending($this->image, false);
        \imagesavealpha($this->image, true);

        return $this;
    }

    /**
     * @param float $opacity (0 to 1)
     * @return $this
     */
    public function setOpacity(float $opacity): Image
    {
        if (!$this->isImageDefined()) {
            return $this;
        }

        \imagealphablending($this->image, false);
        \imagesavealpha($this->image, true);
        \imagefilter($this->image, IMG_FILTER_COLORIZE, 0, 0, 0, \round(127 * (1 - $opacity)));

        return $this;
    }

    // ===================== TRANSFORM =====================

    /**
     * @param float $angle
     * @return $this
     */
    public function rotate(float $angle): Image
    {
        if (!$this->isImageDefined()) {
            return $this;
        }

        $angle = Geometry2D::degrees0to360($angle);

        if ($angle == 0) {
            return $this;
        }

        $rotated = \imagerotate($this->image, $angle, \imagecolorallocatealpha($this->image, 0, 0, 0, 127));

        if ($rotated === false) {
            return $this;
        }

        \imagedestroy($this->image);
        $this->image = $rotated;
        $this->width = \imagesx($this->image);
        $this->height = \imagesy($this->image);

        \imagealphablending($this->image, false);
        \imagesavealpha($this->image, true);

        return $this;
    }

    /**
     * @param int $width
     * @param int $height
     * @return $this
     */
    public function resize(int $width, int $height): Image
    {
        if (!$this->isImageDefined()) {
            return $this;
        }

        $newImage = \imagecreatetruecolor($width, $height);

        if ($newImage === false) {
            return $this;
        }

        \imagealphablending($newImage, false);
        \imagesavealpha($newImage, true);
        \imagefill($newImage, 0, 0, \imagecolorallocatealpha($newImage, 0, 0, 0, 127));

        \imagecopyresampled($newImage, $this->image, 0, 0, 0, 0, $width, $height, $this->width, $this->height);

        \imagedestroy($this->image);
        $this->image = $newImage;
        $this->width = $width;
        $this->height = $height;

        return $this;
    }

    /**
     * @param int $width
     * @param int $height
     * @return $this
     */
    public function resizeProportion(int $width, int $height): Image
    {
        if (!$this->isImageDefined()) {
            return $this;
        }

        $ratio = \min($width / $this->width, $height / $this->height);
        return $this->resize(\round($this->width * $ratio), \round($this->height * $ratio));
    }

    /**
     * @param int $maxWidth
     * @param int $maxHeight
     * @return $this
     */
    public function downscaleProportion(int $maxWidth, int $maxHeight): Image
    {
        if (!$this->isImageDefined() || ($this->width <= $maxWidth && $this->height <= $maxHeight)) {
            return $this;
        }

        return $this->resizeProportion($maxWidth, $maxHeight);
    }

    /**
     * @param int $width
     * @param int $height
     * @param int|string $posX
     * @param int|string $posY
     * @return $this
     */
    public function crop(int $width, int $height, $posX = Image::ALIGN_CENTER, $posY = Image::ALIGN_MIDDLE): Image
    {
        if (!$this->isImageDefined()) {
            return $this;
        }

        $posX = $this->convertPosX($posX, $width);
        $posY = $this->convertPosY($posY, $height);

        $newImage = \imagecreatetruecolor($width, $height);

        if ($newImage === false) {
            return $this;
        }

        \imagealphablending($newImage, false);
        \imagesavealpha($newImage, true);
        \imagefill($newImage, 0, 0, \imagecolorallocatealpha($newImage, 0, 0, 0, 127));

        \imagecopyresampled($newImage, $this->image, 0, 0, $posX, $posY, $width, $height, $width, $height);

        \imagedestroy($this->image);
        $this->image = $newImage;
        $this->width = $width;
        $this->height = $height;

        return $this;
    }

    /**
     * @param int $width
     * @param int $height
     * @param int|string $posX
     * @param int|string $posY
     * @return $this
     */
    public function downscaleAndCrop(int $width, int $height, $posX = Image::ALIGN_CENTER, $posY = Image::ALIGN_MIDDLE): Image
    {
        if (!$this->isImageDefined()) {
            return $this;
        }

        $ratio = \max($width / $this->width, $height / $this->height);

        if ($ratio < 1) {
            $this->resize(\round($this->width * $ratio), \round($this->height * $ratio));
        }

        return $this->crop($width, $height, $posX, $posY);
    }

    // ===================== DRAWING =====================

    /**
     * @param int $originX
     * @param int $originY
     * @param int $dstX
     * @param int $dstY
     * @param int $weight
     * @param string $color
     * @return $this
     */
    public function drawLine(int $originX, int $originY, int $dstX, int $dstY, int $weight, string $color = '#000000'): Image
    {
        if (!$this->isImageDefined()) {
            return $this;
        }

        $angleAndLength = Geometry2D::getAngleAndLengthFromPoints($originX, $originY, $dstX, $dstY);
        return $this->drawLineWithAngle($originX, $originY, $angleAndLength['angle'], $angleAndLength['length'], $weight, $color);
    }

    /**
     * @param int $originX
     * @param int $originY
     * @param float $angle
     * @param float $length
     * @param int $weight
     * @param string $color
     * @return $this
     */
    public function drawLineWithAngle(int $originX, int $originY, float $angle, float $length, int $weight, string $color = '#000000'): Image
    {
        $angle = Geometry2D::degrees0to360($angle);

        $points1 = Geometry2D::getDstXY($originX, $originY, Geometry2D::degrees0to360($angle - 90), \floor($weight / 2));
        $points2 = Geometry2D::getDstXY($points1['x'], $points1['y'], $angle, $length);
        $points4 = Geometry2D::getDstXY($originX, $originY, Geometry2D::degrees0to360($angle + 90), \floor($weight / 2));
        $points3 = Geometry2D::getDstXY($points4['x'], $points4['y'], $angle, $length);

        return $this->drawPolygon(
            [
                \round($points1['x']),
                \round($points1['y']),
                \round($points2['x']),
                \round($points2['y']),
                \round($points3['x']),
                \round($points3['y']),
                \round($points4['x']),
                \round($points4['y']),
            ],
            $color,
            true
        );
    }

    /**
     * @param array $points [x1, y1, x2, y2, ...]
     * @param string $color
     * @param bool $antialias
     * @return $this
     */
    public function drawPolygon(array $points, string $color = '000000', $antialias = false): Image
    {
        if (!$this->isImageDefined()) {
            return $this;
        }

        $color = $this->colorAllocate($color);

        if ($color === false) {
            return $this;
        }

        if ($antialias) {
            \imageantialias($this->image, true);
            if (PHP_MAJOR_VERSION >= 8) {
                \imagepolygon($this->image, $points, $color);
            } else {
                \imagepolygon($this->image, $points, \count($points) / 2, $color);
            }
        }

        if (PHP_MAJOR_VERSION >= 8) {
            \imagefilledpolygon($this->image, $points, $color);
        } else {
            \imagefilledpolygon($this->image, $points, \count($points) / 2, $color);
        }

        if ($antialias) {
            \imageantialias($this->image, false);
        }

        return $this;
    }

    /**
     * @param int $posX
     * @param int $posY
     * @param int $diameter
     * @param string $color
     * @param string $anchorX
     * @param string $anchorY
     * @return $this
     */
    public function drawCircle(int $posX, int $posY, int $diameter, string $color = '#FFFFFF', string $anchorX = Image::ALIGN_CENTER, string $anchorY = Image::ALIGN_MIDDLE): Image
    {
        if (!$this->isImageDefined()) {
            return $this;
        }

        $color = $this->colorAllocate($color);

        if ($color === false) {
            return $this;
        }

        switch ($anchorX) {
            case static::ALIGN_LEFT:
                $posX = \round($posX + $diameter / 2);
                break;
            case static::ALIGN_CENTER:
                break;
            case static::ALIGN_RIGHT:
                $posX = \round($posX - $diameter / 2);
                break;
        }

        switch ($anchorY) {
            case static::ALIGN_TOP:
                $posY = \round($posY + $diameter / 2);
                break;
            case static::ALIGN_MIDDLE:
                break;
            case static::ALIGN_BOTTOM:
                $posY = \round($posY - $diameter / 2);
                break;
        }

        \imagefilledellipse($this->image, $posX, $posY, $diameter, $diameter, $color);
        return $this;
    }

    /**
     * @param int $left
     * @param int $top
     * @param int $right
     * @param int $bottom
     * @param string $color
     * @return $this
     */
    public function drawRectangle(int $left, int $top, int $right, int $bottom, string $color): Image
    {
        if (!$this->isImageDefined()) {
            return $this;
        }

        $color = $this->colorAllocate($color);

        if (($bottom - $top) <= 1.5) {
            \imageline($this->image, $left, $top, $right, $top, $color);
        } elseif (($right - $left) <= 1.5) {
            \imageline($this->image, $left, $top, $left, $bottom, $color);
        } else {
            \imagefilledrectangle($this->image, $left, $top, $right, $bottom, $color);
        }
        return $this;
    }

    /**
     * @param int $originX
     * @param int $originY
     * @param int $dstX
     * @param int $dstY
     * @param int $weight
     * @param int $arrowWidth
     * @param int $arrowLength
     * @param string $color
     * @return $this
     */
    public function drawArrow(int $originX, int $originY, int $dstX, int $dstY, int $weight, int $arrowWidth, int $arrowLength, string $color = '#000000'): Image
    {
        $angleAndLength = Geometry2D::getAngleAndLengthFromPoints($originX, $originY, $dstX, $dstY);
        return $this->drawArrowWithAngle($originX, $originY, $angleAndLength['angle'], $angleAndLength['length'], $weight, $arrowWidth, $arrowLength, $color);
    }

    /**
     * @param int $originX
     * @param int $originY
     * @param float $angle
     * @param float $length
     * @param int $weight
     * @param int $arrowWidth
     * @param int $arrowLength
     * @param string $color
     * @return $this
     */
    public function drawArrowWithAngle(int $originX, int $originY, float $angle, float $length, int $weight, int $arrowWidth, int $arrowLength, string $color = '#000000'): Image
    {
        if (!$this->isImageDefined()) {
            return $this;
        }

        $angle = Geometry2D::degrees0to360($angle);
        $stemLength = $length - $arrowLength;

        if ($stemLength > 0) {
            $this->drawLineWithAngle($originX, $originY, $angle, $stemLength, $weight, $color);
        }

        $tip = Geometry2D::getDstXY($originX, $originY, $angle, $length);
        $arrowBase = Geometry2D::getDstXY($originX, $originY, $angle, \max(0, $stemLength));

        $branch1 = Geometry2D::getDstXY($arrowBase['x'], $arrowBase['y'], Geometry2D::degrees0to360($angle - 90), $arrowWidth / 2);
        $branch2 = Geometry2D::getDstXY($arrowBase['x'], $arrowBase['y'], Geometry2D::degrees0to360($angle + 90), $arrowWidth / 2);

        return $this->drawPolygon(
            [
                \round($tip['x']),
                \round($tip['y']),
                \round($branch1['x']),
                \round($branch1['y']),
                \round($branch2['x']),
                \round($branch2['y']),
            ],
            $color,
            true
        );
    }

    // ===================== FILTERS =====================

    /**
     * @return $this
     */
    public function grayscale(): Image
    {
        if (!$this->isImageDefined()) {
            return $this;
        }

        \imagefilter($this->image, IMG_FILTER_GRAYSCALE);
        return $this;
    }

    /**
     * @param Image $mask
     * @return $this
     */
    public function alphaMask(Image $mask): Image
    {
        if (!$this->isImageDefined() || !$mask->isImageDefined()) {
            return $this;
        }

        $maskImage = $mask->getImage();
        $maskWidth = $mask->getWidth();
        $maskHeight = $mask->getHeight();

        for ($x = 0; $x < $this->width; $x++) {
            for ($y = 0; $y < $this->height; $y++) {
                if ($x >= $maskWidth || $y >= $maskHeight) {
                    $alpha = 127;
                } else {
                    $maskColor = \imagecolorat($maskImage, $x, $y);
                    $maskRgb = \imagecolorsforindex($maskImage, $maskColor);
                    $gray = \round(($maskRgb['red'] + $maskRgb['green'] + $maskRgb['blue']) / 3);
                    $alpha = 127 - \round($gray / 2);
                }

                $srcColor = \imagecolorat($this->image, $x, $y);
                $srcRgb = \imagecolorsforindex($this->image, $srcColor);

                $newColor = \imagecolorallocatealpha($this->image, $srcRgb['red'], $srcRgb['green'], $srcRgb['blue'], $alpha);
                \imagesetpixel($this->image, $x, $y, $newColor);
            }
        }

        return $this;
    }

    // ===================== TEXT =====================

    /**
     * @param string $string
     * @param string $fontPath
     * @param float $fontSize
     * @param string $color
     * @param float|string $posX
     * @param float|string $posY
     * @param float|string $anchorX
     * @param float|string $anchorY
     * @param float $rotation
     * @param float $letterSpacing
     * @return $this
     */
    public function writeText(string $string, string $fontPath, float $fontSize, string $color = 'ffffff', $posX = 0, $posY = 0, $anchorX = Image::ALIGN_CENTER, $anchorY = Image::ALIGN_MIDDLE, float $rotation = 0, float $letterSpacing = 0): Image
    {
        $this->writeTextAndGetBoundingBox($string, $fontPath, $fontSize, $color, $posX, $posY, $anchorX, $anchorY, $rotation, $letterSpacing);
        return $this;
    }

    /**
     * @param string $string
     * @param string $fontPath
     * @param float $fontSize
     * @param string $color
     * @param float|string $posX
     * @param float|string $posY
     * @param float|string $anchorX
     * @param float|string $anchorY
     * @param float $rotation
     * @param float $letterSpacing
     * @return array
     */
    public function writeTextAndGetBoundingBox(string $string, string $fontPath, float $fontSize, string $color = 'ffffff', $posX = 0, $posY = 0, $anchorX = Image::ALIGN_CENTER, $anchorY = Image::ALIGN_MIDDLE, float $rotation = 0, float $letterSpacing = 0): array
    {
        if (!$this->isImageDefined()) {
            return [];
        }

        $posX = $this->convertPosX($posX);
        $posY = $this->convertPosY($posY);

        \imagesavealpha($this->image, false);
        \imagealphablending($this->image, true);

        $color = $this->colorAllocate($color);

        if ($color === false) {
            return [];
        }

        if (
            $anchorX == static::ALIGN_LEFT ||
            $anchorX == static::ALIGN_CENTER ||
            $anchorX == static::ALIGN_RIGHT ||
            $anchorY == static::ALIGN_TOP ||
            $anchorY == static::ALIGN_MIDDLE ||
            $anchorY == static::ALIGN_BOTTOM
        ) {
            if (
                ($newImg = \imagecreatetruecolor(1, 1)) === false ||
                ($posText = $this->imagettftextWithSpacing($newImg, $fontSize, $rotation, 0, 0, $color, $fontPath, $string, $letterSpacing)) === false
            ) {
                return [];
            }
            \imagedestroy($newImg);

            $xMin = 0;
            $xMax = 0;
            $yMin = 0;
            $yMax = 0;
            for ($i = 0; $i < 8; $i += 2) {
                if ($posText[$i] < $xMin) {
                    $xMin = $posText[$i];
                }
                if ($posText[$i] > $xMax) {
                    $xMax = $posText[$i];
                }
                if ($posText[$i + 1] < $yMin) {
                    $yMin = $posText[$i + 1];
                }
                if ($posText[$i + 1] > $yMax) {
                    $yMax = $posText[$i + 1];
                }
            }

            $sizeWidth  = $xMax - $xMin;
            $sizeHeight = $yMax - $yMin;

            switch ($anchorX) {
                case static::ALIGN_LEFT:
                    $posX = $posX - $xMin;
                    break;
                case static::ALIGN_CENTER:
                    $posX = $posX - $sizeWidth / 2 - $xMin;
                    break;
                case static::ALIGN_RIGHT:
                    $posX = $posX - $sizeWidth - $xMin;
                    break;
            }
            switch ($anchorY) {
                case static::ALIGN_TOP:
                    $posY = $posY - $yMin;
                    break;
                case static::ALIGN_MIDDLE:
                    $posY = $posY - $sizeHeight / 2 - $yMin;
                    break;
                case static::ALIGN_BOTTOM:
                    $posY = $posY - $sizeHeight - $yMin;
                    break;
            }
        }

        $posText = $this->imagettftextWithSpacing($this->image, $fontSize, $rotation, $posX, $posY, $color, $fontPath, $string, $letterSpacing);

        if ($posText === false) {
            return [];
        }

        \imagealphablending($this->image, false);
        \imagesavealpha($this->image, true);

        return [
            'top-left'     => ['x' => $posText[6], 'y' => $posText[7]],
            'top-right'    => ['x' => $posText[4], 'y' => $posText[5]],
            'bottom-left'  => ['x' => $posText[0], 'y' => $posText[1]],
            'bottom-right' => ['x' => $posText[2], 'y' => $posText[3]],
            'baseline'     => ['x' => $posX, 'y' => $posY],
        ];
    }

    /**
     * @param resource|\GdImage $image
     * @param float $size
     * @param float $angle
     * @param float $x
     * @param float $y
     * @param int $color
     * @param string $font
     * @param string $text
     * @param float $spacing
     * @return array|false
     */
    private function imagettftextWithSpacing($image, float $size, float $angle, float $x, float $y, int $color, string $font, string $text, float $spacing = 0)
    {
        if ($spacing == 0) {
            return \imagettftext($image, $size, $angle, \round($x), \round($y), $color, $font, $text);
        } else {
            $length = \mb_strlen($text);

            if ($length == 0) {
                return false;
            }

            $letterPos = ['x' => $x, 'y' => $y];
            $textWidth = $spacing * ($length - 1);
            $top       = 0;
            $bottom    = 0;

            for ($i = 0; $i < $length; ++$i) {
                $char = \mb_substr($text, $i, 1);
                \imagettftext($image, $size, $angle, \round($letterPos['x']), \round($letterPos['y']), $color, $font, $char);
                $bbox      = \imagettfbbox($size, 0, $font, $char);
                $letterPos = Geometry2D::getDstXY($letterPos['x'], $letterPos['y'], $angle, $spacing + $bbox[2]);

                $textWidth += $bbox[2];
                if ($top > $bbox[5]) {
                    $top = $bbox[5];
                }
                if ($bottom < $bbox[1]) {
                    $bottom = $bbox[1];
                }
            }

            $bottomLeft  = Geometry2D::getDstXY($x, $y, $angle - 90, $bottom);
            $bottomRight = Geometry2D::getDstXY($bottomLeft['x'], $bottomLeft['y'], $angle, $textWidth);
            $topLeft     = Geometry2D::getDstXY($x, $y, $angle + 90, \abs($top));
            $topRight    = Geometry2D::getDstXY($topLeft['x'], $topLeft['y'], $angle, $textWidth);

            return [$bottomLeft['x'], $bottomLeft['y'], $bottomRight['x'], $bottomRight['y'], $topRight['x'], $topRight['y'], $topLeft['x'], $topLeft['y']];
        }
    }

    // ===================== OUTPUT =====================

    /**
     * @param string $path
     * @return bool
     */
    public function savePNG(string $path): bool
    {
        if (!$this->isImageDefined()) {
            return false;
        }
        return \imagepng($this->image, $path);
    }

    /**
     * @param string $path
     * @param int $quality
     * @return bool
     */
    public function saveJPG(string $path, int $quality = -1): bool
    {
        if (!$this->isImageDefined()) {
            return false;
        }
        return \imagejpeg($this->image, $path, $quality);
    }

    /**
     * @param string $path
     * @return bool
     */
    public function saveGIF(string $path): bool
    {
        if (!$this->isImageDefined()) {
            return false;
        }
        return \imagegif($this->image, $path);
    }

    public function displayPNG()
    {
        if ($this->isImageDefined()) {
            \imagepng($this->image);
        }
    }

    public function displayJPG(int $quality = -1)
    {
        if ($this->isImageDefined()) {
            \imagejpeg($this->image, null, $quality);
        }
    }

    public function displayGIF()
    {
        if ($this->isImageDefined()) {
            \imagegif($this->image);
        }
    }

    /**
     * @param callable $imgFunction
     * @return string
     */
    private function getData(callable $imgFunction): string
    {
        if (!$this->isImageDefined()) {
            return '';
        }

        \ob_start();
        $imgFunction();
        $imageData = \ob_get_contents();
        \ob_end_clean();

        return $imageData !== false ? $imageData : '';
    }

    /**
     * @return string
     */
    public function getDataPNG(): string
    {
        return $this->getData(function () {
            $this->displayPNG();
        });
    }

    /**
     * @param int $quality
     * @return string
     */
    public function getDataJPG(int $quality = -1): string
    {
        return $this->getData(function () use ($quality) {
            $this->displayJPG($quality);
        });
    }

    /**
     * @return string
     */
    public function getDataGIF(): string
    {
        return $this->getData(function () {
            $this->displayGIF();
        });
    }

    /**
     * @return string
     */
    public function getBase64PNG(): string
    {
        return \base64_encode($this->getDataPNG());
    }

    /**
     * @param int $quality
     * @return string
     */
    public function getBase64JPG(int $quality = -1): string
    {
        return \base64_encode($this->getDataJPG($quality));
    }

    /**
     * @return string
     */
    public function getBase64GIF(): string
    {
        return \base64_encode($this->getDataGIF());
    }

    /**
     * @return string
     */
    public function getBase64SourcePNG(): string
    {
        return 'data:image/png;base64,' . $this->getBase64PNG();
    }

    /**
     * @param int $quality
     * @return string
     */
    public function getBase64SourceJPG(int $quality = -1): string
    {
        return 'data:image/jpeg;base64,' . $this->getBase64JPG($quality);
    }

    /**
     * @return string
     */
    public function getBase64SourceGIF(): string
    {
        return 'data:image/gif;base64,' . $this->getBase64GIF();
    }

    // ===================== CACHE / CURL =====================

    /**
     * @var string
     */
    public $cacheDirectory = '.tiles_cache';

    /**
     * @param string $url
     * @param array $curlOptions
     * @param bool $failOnError
     * @return Image
     */
    public static function fromCurl(string $url, array $curlOptions = [], bool $failOnError = false): Image
    {
        return (new Image)->curl($url, $curlOptions, $failOnError);
    }

    /**
     * @param string $url
     * @param array $curlOptions
     * @param bool $failOnError
     * @param bool $cacheData
     * @return $this
     */
    public function curl(string $url, array $curlOptions = [], bool $failOnError = false, bool $cacheData = true): Image
    {
        if ($cacheData && $this->isCached($url)) {
            return $this->data($this->getFileFromCache($url));
        }

        $defaultCurlOptions = [
            CURLOPT_USERAGENT      => 'php-osm-static-aero/1.0 (https://github.com/ycdev/php-osm-static-aero)',
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT        => 5,
        ];

        if (php_sapi_name() !== 'cli' && isset($_SERVER["REQUEST_SCHEME"], $_SERVER["HTTP_HOST"], $_SERVER["REQUEST_URI"])) {
            $defaultCurlOptions[CURLOPT_REFERER] = \strtolower($_SERVER["REQUEST_SCHEME"] . '://' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
        }

        $curl = \curl_init();
        \curl_setopt($curl, CURLOPT_URL, $url);
        \curl_setopt_array($curl, $defaultCurlOptions + $curlOptions);

        $image = \curl_exec($curl);

        if ($failOnError && \curl_errno($curl)) {
            $error = \curl_error($curl);
            \curl_close($curl);
            throw new \Exception($error);
        }

        \curl_close($curl);

        if ($cacheData && $image !== false) {
            $this->saveToCache($url, $image);
        }

        if ($image === false) {
            return $this->resetFields();
        }

        return $this->data($image);
    }

    /**
     * @param string $url
     * @return string
     */
    private function cacheDirectory($url)
    {
        $url    = str_replace('.' . pathinfo($url)['extension'], '', $url);
        $dirs   = [$this->cacheDirectory];
        $dirs[] = parse_url($url, PHP_URL_HOST);
        $dirs   = array_merge($dirs, explode('/', substr(parse_url($url, PHP_URL_PATH), 1)));
        return array_reduce($dirs, function ($path, $dir) {
            $path .= (is_null($path)) ? $dir : "/$dir";
            if (!is_dir($path)) {
                mkdir($path);
            }
            return $path;
        }) . '/';
    }

    /**
     * @param string $url
     * @return string
     */
    private function cacheFileId($url)
    {
        return str_replace('/', '_', base64_encode($url));
    }

    /**
     * @param string $url
     * @return bool|string
     */
    private function getFileFromCache(string $url)
    {
        $id    = $this->cacheFileId($url);
        $files = glob($this->cacheDirectory($url) . $id . '*');
        foreach ($files as $file) {
            $parts = explode('_', basename($file));
            $time = isset($parts[1]) ? str_replace('.png', '', $parts[1]) : '';
            if (filesize($file) === 0) {
                unlink($file);
                continue;
            }
            if (intval($time) > time()) {
                return file_get_contents($file);
            } else {
                unlink($file);
            }
        }

        return false;
    }

    /**
     * @param string $url
     * @return bool
     */
    private function isCached(string $url): bool
    {
        return ($this->getFileFromCache($url) !== false);
    }

    /**
     * @param string $url
     * @param string $data
     */
    private function saveToCache(string $url, string $data)
    {
        $filename = $this->cacheFileId($url) . '_' . (time() + (86400 * 7)) . '.png';
        file_put_contents($this->cacheDirectory($url) . $filename, $data);
    }
}
