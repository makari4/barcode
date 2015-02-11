<?php


namespace makari4\barcode;


class Barcode39
{
    const ORIENTATION_HORIZONTAL = 0;
    const ORIENTATION_VERTICAL = 90;
    const ORIENTATION_HORIZONTAL_180 = 180;
    const ORIENTATION_VERTICAL_270 = 270;

    const MODE_PNG = 0;
    const MODE_JPEG = 1;
    const MODE_GIF = 2;

    public static $contentTypes = [
        self::MODE_PNG => 'image/png',
        self::MODE_JPEG => 'image/jpeg',
        self::MODE_GIF => 'image/gif',
    ];

    protected $size;
    protected $barSize;
    protected $barRatio = 3;
    protected $displayText = false;
    protected $filePath = null;
    protected $mode = self::MODE_PNG;
    protected $orientation = self::ORIENTATION_HORIZONTAL;
    protected $fontSize = 2;
    protected $image;
    protected $imageHeight;
    protected $imageWidth;
    protected $foregroundColor;
    protected $backgroundColor;


    protected static $charCodes = [
        '0' => '000110100',
        '1' => '100100001',
        '2' => '001100001',
        '3' => '101100000',
        '4' => '000110001',
        '5' => '100110000',
        '6' => '001110000',
        '7' => '000100101',
        '8' => '100100100',
        '9' => '001100100',
        'A' => '100001001',
        'B' => '001001001',
        'C' => '101001000',
        'D' => '000011001',
        'E' => '100011000',
        'F' => '001011000',
        'G' => '000001101',
        'H' => '100001100',
        'I' => '001001100',
        'J' => '000011100',
        'K' => '100000011',
        'L' => '001000011',
        'M' => '101000010',
        'N' => '000010011',
        'O' => '100010010',
        'P' => '001010010',
        'Q' => '000000111',
        'R' => '100000110',
        'S' => '001000110',
        'T' => '000010110',
        'U' => '110000001',
        'V' => '011000001',
        'W' => '111000000',
        'X' => '010010001',
        'Y' => '110010000',
        'Z' => '011010000',
        ' ' => '011000100',
        '$' => '010101000',
        '%' => '000101010',
        '*' => '010010100',
        '+' => '010001010',
        '-' => '010000101',
        '.' => '110000100',
        '/' => '010100010'
    ];

    public $text = '';

    function __construct($mode = self::MODE_PNG, $size = 50, $barSize = 2, $displayText = false, $fontSize = 2)
    {
        $this->size = $size;
        $this->barSize = $barSize;
        $this->displayText = $displayText;
        $this->mode = $mode;
        $this->fontSize = $fontSize;
    }

    /**
     * @param $size
     * @return $this
     */
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * @param $barSize
     * @return $this
     */
    public function setBarSize($barSize)
    {
        $this->barSize = $barSize;

        return $this;
    }

    /**
     * @param $displayText
     * @return $this
     */
    public function setDisplayText($displayText)
    {
        $this->displayText = $displayText;

        return $this;
    }

    /**
     * @param $mode
     * @return $this
     */
    public function setMode($mode)
    {
        $this->mode = $mode;

        return $this;
    }

    /**
     * @param $fontSize
     * @return $this
     */
    public function setFontSize($fontSize)
    {
        $this->fontSize = $fontSize;

        return $this;
    }

    /**
     * @param $barRatio
     * @return $this
     * @throws \Exception
     */
    public function setBarRatio($barRatio)
    {
        if (!in_array($barRatio, [2, 3])) {
            throw new \Exception('Only 2 and 3 are valid ratio values.');
        }
        $this->barRatio = $barRatio;

        return $this;
    }

    /**
     * @param $orientation
     * @return $this
     * @throws \Exception
     */
    public function setOrientation($orientation)
    {
        if (!in_array($orientation, [self::ORIENTATION_HORIZONTAL, self::ORIENTATION_VERTICAL])) {
            throw new \Exception('Invalid orientation.');
        }
        $this->orientation = $orientation;

        return $this;
    }


    public function generate($text, $filePath = null)
    {
        $this->text = $text;
        $encodedText = $this->encode($text);

        $barcodeLength = 0;
        $barcodeLength += (mb_strlen($text) + 2) * (7 * $this->barSize + 3 * $this->barSize * $this->barRatio);
        $this->setupImage($barcodeLength);

        $location = 0;
        $encodedTextLength = mb_strlen($encodedText);
        for ($i = 0; $i < $encodedTextLength; $i++) {
            $currentBarSize = ($encodedText[$i]) ? $this->barSize * $this->barRatio : $this->barSize;

            if (($i + 1) % 2) {
                $this->drawBarcodeLine($location, $currentBarSize);
            }

            $location += $currentBarSize;
        }

        if ($this->displayText) {
            $this->drawText($barcodeLength);
        }

        if ($this->orientation != self::ORIENTATION_HORIZONTAL) {
            $this->image = imagerotate($this->image, $this->orientation, $this->backgroundColor);
        }

        $this->output($filePath);
    }

    protected function output($filePath)
    {
        if ($filePath === null) {
            header("Content-type: " . static::$contentTypes[$this->mode]);
        }

        switch ($this->mode) {
            case self::MODE_GIF :
                imagegif($this->image, $filePath);
                break;
            case self::MODE_PNG :
                imagepng($this->image, $filePath);
                break;
            case self::MODE_JPEG :
                imagejpeg($this->image, $filePath);
                break;
        }

        imagedestroy($this->image);
    }

    protected function drawText($barcodeLength)
    {

        $textWidth = imagefontwidth($this->fontSize) * strlen($this->text) + 10;
        $textHeight = imagefontheight($this->fontSize) + 2;
        $barcodeCenter = $barcodeLength / 2;
        $textCenter = $textWidth / 2;

        imagefilledrectangle(
            $this->image,
            $barcodeCenter - $textCenter,
            $this->imageHeight - $textHeight,
            $barcodeCenter + $textCenter,
            $this->imageHeight,
            $this->backgroundColor
        );
        imagestring(
            $this->image,
            $this->fontSize,
            ($barcodeCenter - $textCenter) + 5,
            ($this->imageHeight - $textHeight) + 1,
            $this->text,
            $this->foregroundColor
        );

        return $this;
    }

    protected function drawBarcodeLine($location, $currentBarSize)
    {
        imagefilledrectangle(
            $this->image,
            $location,
            0,
            $location + $currentBarSize - 1,
            $this->imageHeight,
            $this->foregroundColor
        );


        return $this;
    }

    protected function setupImage($barcodeLength)
    {

        $this->imageWidth = $barcodeLength;
        $this->imageHeight = $this->size;

        $this->image = imagecreate($this->imageWidth, $this->imageHeight);
        $this->foregroundColor = imagecolorallocate($this->image, 0, 0, 0);
        $this->backgroundColor = imagecolorallocate($this->image, 255, 255, 255);
        imagefill($this->image, 0, 0, $this->backgroundColor);

        return $this;
    }

    protected function encode($text)
    {
        $text = '*' . mb_strtoupper($text) . '*';
        $textLength = mb_strlen($text);

        $encodedText = '';
        for ($i = 0; $i < $textLength; $i++) {
            $char = isset(static::$charCodes[$text[$i]]) ? static::$charCodes[$text[$i]] : static::$charCodes['0'];
            $encodedText .= $char . "0";
        }

        return $encodedText;
    }


}