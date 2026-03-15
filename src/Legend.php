<?php

namespace Ycdev\OsmStaticAero;

use Ycdev\OsmStaticAero\Interfaces\Draw;

class Legend implements Draw
{
    const ALIGN_LEFT = 'left';
    const ALIGN_RIGHT = 'right';
    const ALIGN_TOP = 'top';
    const ALIGN_BOTTOM = 'bottom';

    /**
     * @var LatLng|null
     */
    private $center;
    /**
     * @var string
     */
    private $text;
    /**
     * @var int
     */
    private $fontSize;
    /**
     * @var string
     */
    private $fontColor;
    /**
     * @var string
     */
    private $backgroundColor;
    /**
     * @var int
     */
    private $padding;
    /**
     * @var string|null
     */
    private $logoPath;
    /**
     * @var string|null
     */
    private $alignment;
    /**
     * @var int
     */
    private $offsetX = 0;
    /**
     * @var string|null
     */
    private $title;

    /**
     * @param LatLng|string $position Position or alignment ('left', 'right', 'top', 'bottom')
     * @param string $text Text to display
     * @param int $fontSize Font size
     * @param string $fontColor Hex color
     * @param string $backgroundColor Hex background color
     * @param int $padding Padding in pixels
     * @param string|null $logoPath Path to logo image
     * @param string|null $title Legend title
     */
    public function __construct($position, string $text, int $fontSize = 30, string $fontColor = '000000', string $backgroundColor = 'ffffff', int $padding = 10, string $logoPath = null, string $title = null)
    {
        $this->text = $text;
        $this->fontSize = $fontSize;
        $this->fontColor = $fontColor;
        $this->backgroundColor = $backgroundColor;
        $this->padding = $padding;
        $this->logoPath = $logoPath;
        $this->title = $title;

        if ($position instanceof LatLng) {
            $this->center = $position;
            $this->alignment = null;
        } else {
            $this->alignment = $position;
            $this->center = null;
        }
    }

    /**
     * Calculate position based on alignment
     * @param MapData $mapData
     * @return LatLng
     */
    private function calculatePosition(MapData $mapData): LatLng
    {
        if ($this->center) {
            return $this->center;
        }

        $outputSize = $mapData->getOutputSize();
        $width = $outputSize->getX();
        $height = $outputSize->getY();
        $margin = 0;

        switch ($this->alignment) {
            case self::ALIGN_LEFT:
                return $mapData->convertPxPositionToLatLng(new XY($margin + $this->offsetX, $margin));
            case self::ALIGN_RIGHT:
                return $mapData->convertPxPositionToLatLng(new XY($width - $margin - $this->offsetX, $margin));
            case self::ALIGN_TOP:
                return $mapData->convertPxPositionToLatLng(new XY($width / 2, $margin));
            case self::ALIGN_BOTTOM:
                return $mapData->convertPxPositionToLatLng(new XY($width / 2, $height - $margin));
            default:
                return $mapData->convertPxPositionToLatLng(new XY($width / 2, $height / 2));
        }
    }

    /**
     * Calculate text bounding box without rendering
     * @param string $text
     * @param string $fontPath
     * @param int $fontSize
     * @param string $color
     * @param int $posX
     * @param int $posY
     * @return array
     */
    private function calculateTextBoundingBox(string $text, string $fontPath, int $fontSize, string $color, int $posX, int $posY): array
    {
        $tempImage = Image::newCanvas(1, 1);
        return $tempImage->writeTextAndGetBoundingBox(
            $text,
            $fontPath,
            $fontSize,
            $color,
            $posX,
            $posY,
            Image::ALIGN_LEFT,
            Image::ALIGN_MIDDLE
        );
    }

    /**
     * @param Image $image The map image
     * @param MapData $mapData Bounding box of the map
     * @return $this Fluent interface
     */
    public function draw(Image $image, MapData $mapData): Legend
    {
        $position = $this->calculatePosition($mapData);
        $center = $mapData->convertLatLngToPxPosition($position);
        $fontPath = __DIR__ . '/resources/SpaceMono-Bold.ttf';

        // Logo loading
        $logoImage = null;
        $logoHeight = 0;
        $logoWidth = 0;
        $logoMarginBottom = 10;
        if ($this->logoPath) {
            if (\file_exists($this->logoPath)) {
                $logoImage = Image::fromPath($this->logoPath);
                $logoWidth = $logoImage->getWidth();
                $logoHeight = $logoImage->getHeight();
            } else {
                \trigger_error("Le fichier logo fourni n'existe pas : " . $this->logoPath, E_USER_WARNING);
            }
        }

        // Title calculation
        $titleHeight = 0;
        $titleWidth = 0;
        $titleMarginBottom = 20;
        $fontPathTitle = $fontPath;
        $titleFontSize = $this->fontSize * 2;
        $titleColor = $this->fontColor;
        if ($this->title) {
            $titleBBox = $this->calculateTextBoundingBox($this->title, $fontPathTitle, $titleFontSize, $titleColor, 0, 0);
            $titleHeight = \abs($titleBBox['bottom-right']['y'] - $titleBBox['top-right']['y']);
            $titleWidth = \abs($titleBBox['bottom-right']['x'] - $titleBBox['top-left']['x']);
        }

        // Calculate text metrics
        $lines = \explode("\n", $this->text);
        $lineMetrics = [];
        $maxTextWidth = 0;
        $totalTextHeight = 0;
        foreach ($lines as $line) {
            $trimmed = \ltrim($line);
            $fontSize = $this->fontSize;
            if (\strpos($trimmed, '##') === 0) {
                $lineText = \trim(\substr($trimmed, 2));
                $fontSize = \intval($this->fontSize * 1.5);
            } elseif (\strpos($trimmed, '#') === 0) {
                $lineText = \trim(\substr($trimmed, 1));
                $fontSize = \intval($this->fontSize * 2);
            } else {
                $lineText = $line;
            }
            $bbox = $this->calculateTextBoundingBox($lineText, $fontPath, $fontSize, $this->fontColor, 0, 0);
            $lineWidth = \abs($bbox['bottom-right']['x'] - $bbox['top-left']['x']);
            $lineHeight = \intval($fontSize * 1.4);
            $lineMetrics[] = [
                'text' => $lineText,
                'fontSize' => $fontSize,
                'width' => $lineWidth,
                'height' => $lineHeight,
            ];
            if ($lineWidth > $maxTextWidth) {
                $maxTextWidth = $lineWidth;
            }
            $totalTextHeight += $lineHeight;
        }

        // Max content width
        $maxContentWidth = $maxTextWidth;
        if ($titleWidth > $maxContentWidth) {
            $maxContentWidth = $titleWidth;
        }

        // Resize logo if needed
        if ($logoImage && $logoWidth > $maxContentWidth) {
            $logoImage->resize($maxContentWidth, \intval($logoHeight * ($maxContentWidth / $logoWidth)));
            $logoWidth = $logoImage->getWidth();
            $logoHeight = $logoImage->getHeight();
        }

        // Total content height
        $totalContentHeight = $totalTextHeight;
        if ($logoImage) {
            $totalContentHeight += $logoHeight + $logoMarginBottom;
        }
        if ($this->title) {
            $totalContentHeight += $titleHeight + $titleMarginBottom;
        }

        // Rectangle bounds
        $textX = $center->getX();
        $textY = $center->getY();
        $left = $textX - $maxContentWidth / 2 - $this->padding;
        $right = $textX + $maxContentWidth / 2 + $this->padding;
        $top = $textY - $totalContentHeight / 2 - $this->padding;
        $bottom = $textY + $totalContentHeight / 2 + $this->padding;

        // Clamp to image bounds
        $imageWidth = $image->getWidth();
        $imageHeight = $image->getHeight();
        if ($right > $imageWidth) {
            $offset = $right - $imageWidth + $this->padding;
            $left -= $offset;
            $right -= $offset;
        }
        if ($left < 0) {
            $offset = \abs($left) + $this->padding;
            $left += $offset;
            $right += $offset;
        }
        if ($bottom > $imageHeight) {
            $offset = $bottom - $imageHeight + $this->padding;
            $top -= $offset;
            $bottom -= $offset;
        }
        if ($top < 0) {
            $offset = \abs($top) + $this->padding;
            $top += $offset;
            $bottom += $offset;
        }

        // Draw background
        $image->drawRectangle($left, $top, $right, $bottom, '#ffffff19');

        // Render content
        $currentY = $top + $this->padding;

        if ($logoImage) {
            $logoX = $left + $this->padding;
            $image->pasteOn($logoImage, $logoX, $currentY);
            $currentY += $logoHeight + $logoMarginBottom;
        }

        if ($this->title) {
            $titleX = $left + $this->padding + ($maxContentWidth / 2);
            $titleY = $currentY + $titleHeight / 2;
            $image->writeText(
                $this->title,
                $fontPathTitle,
                $titleFontSize,
                $titleColor,
                $titleX,
                $titleY,
                Image::ALIGN_CENTER,
                Image::ALIGN_MIDDLE
            );
            $currentY += $titleHeight + $titleMarginBottom;
        }

        $textX = $left + $this->padding;
        foreach ($lineMetrics as $info) {
            $image->writeText(
                $info['text'],
                $fontPath,
                $info['fontSize'],
                $this->fontColor,
                $textX,
                $currentY + $info['height'] / 2,
                Image::ALIGN_LEFT,
                Image::ALIGN_MIDDLE
            );
            $currentY += $info['height'];
        }

        return $this;
    }

    /**
     * Get bounding box of the shape
     * @return LatLng[]
     */
    public function getBoundingBox(): array
    {
        if ($this->center) {
            return [$this->center, $this->center];
        }
        return [];
    }
}
