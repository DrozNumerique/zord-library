<?php

/**
 * Deepzoom big images into tiles supported by "OpenSeadragon", "OpenLayers" and
 * many other viewers.
 *
 * The process is a mix of the Laravel plugin [Deepzoom](https://github.com/jeremytubbs/deepzoom)
 * of Jeremy Tubbs, the standalone open zoom builder [Deepzoom.php](https://github.com/nfabre/deepzoom.php)
 * of Nicolas Fabre, the [blog](http://omarriott.com/aux/leaflet-js-non-geographical-imagery/)
 * of Olivier Mariott, and the Zoomify converter (see the integrated library).
 * See respective copyright and license (MIT and GNU/GPL) in the above pages.
 */

class Deepzoom {
    
    public $processor = '';
    public $convert   = '';
    public $strategy  = 'exec';
    public $mode      = 0755;
    public $size      = 256;
    public $overlap   = 1;
    public $format    = 'jpg';
    public $quality   = 85;
    
    public static $PROCESSORS        = ['Imagick' => 'imagick', 'GD' => 'gd'];   
    public static $DEFAULT_PROCESSOR = 'ImageMagick';

    public function __construct(array $config = []) {
        foreach ($config as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        if (empty($this->processor)) {
            foreach (self::$PROCESSORS as $processor => $extension) {
                if (extension_loaded($extension)) {
                    $this->processor = $processor;
                    break;
                }
            }
            if (empty($this->processor)) {
                $this->processor = self::$DEFAULT_PROCESSOR;
            }
        }
        foreach (self::$PROCESSORS as $processor => $extension) {
            if ($this->processor == $processor) {
                if (!extension_loaded($extension)) {
                    throw new Exception($processor.' library is not available.');
                }
            }
        }
        if ($this->processor == self::$DEFAULT_PROCESSOR && empty($this->convert)) {
            $this->convert = Zord::execute($this->strategy, 'which convert');
            if (empty($this->convert)) {
                throw new Exception('Convert path is not available.');
            }
        }
    }
    
    public function process($file, $folder) {
        
        $file = realpath($file);
        
        list($width, $height) = getimagesize($file);
        
        $basePath = $folder.DS.pathinfo(basename($file), PATHINFO_FILENAME);
        mkdir($basePath.'_files', $this->mode, true);
        
        switch ($this->processor) {
            case 'Imagick':
                $image = new Imagick();
                $image->readImage($file);
                $image->transformImageColorspace(Imagick::COLORSPACE_SRGB);
                $image->stripImage();
                break;
            case 'GD':
                $image = null;
                switch (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
                    case 'png': {
                        $image = imagecreatefrompng($file);
                    }
                    case 'gif': {
                        $image = imagecreatefromgif($file);
                    }
                    case 'jpg':
                    case 'jpe':
                    case 'jpeg': 
                    default: {
                        $image = imagecreatefromjpeg($file);
                    }
                }
                break;
            case 'ImageMagick':
                $image = $file;
                break;
        }
        
        $numLevels = (integer) ceil(log(max([$width, $height]), 2));
        
        foreach (range($numLevels, 0) as $level) {
            $tileContainerPath = $basePath.'_files'.DS.$level;
            if (!is_dir($tileContainerPath)) {
                mkdir($tileContainerPath, $this->mode);
            }
            $scale      = pow(0.5, $numLevels - $level);
            $tileWidth  = (integer) ceil($width * $scale);
            $tileHeight = (integer) ceil($height * $scale);
            switch ($this->processor) {
                case 'Imagick': {
                    $image->resizeImage($tileWidth, $tileHeight, Imagick::FILTER_LANCZOS, 1, false);
                    break;
                }
                case 'GD': {
                    $tempImage = imagecreatetruecolor($tileWidth, $tileHeight);
                    if (imagecopyresampled(
                            $tempImage, $image,
                            0, 0, 0, 0,
                            $tileWidth, $tileHeight, $width, $height) === false) {
                        imagedestroy($tempImage);
                        throw new Exception('Cannot resize image with GD.');
                    }
                    break;
                }
                case 'ImageMagick': {
                    $resize = [
                        'width'  => $tileWidth,
                        'height' => $tileHeight
                    ];
                    break;
                }
            }
            
            foreach (range(0, (int) ceil(floatval($tileWidth) / $this->size) - 1) as $column) {
                foreach (range(0, (int) ceil(floatval($tileHeight) / $this->size) - 1) as $row) {
                    $tilepath = $basePath.'_files'.DS.$level.DS.$column.'_'.$row.'.'.$this->format;
                    $x        = ($column * $this->size) - ($column == 0 ? 0 : $this->overlap);
                    $y        = ($row    * $this->size) - ($row    == 0 ? 0 : $this->overlap);
                    $bounds   = [
                        'x'      => $x,
                        'y'      => $y,
                        'width'  => min([$this->size + ($column == 0 ? 1 : 2) * $this->overlap, $tileWidth  - $x]),
                        'height' => min([$this->size + ($row    == 0 ? 1 : 2) * $this->overlap, $tileHeight - $y])
                    ];
                                        
                    switch ($this->processor) {
                        case 'Imagick': {
                            $tileImage = clone $image;
                            $tileImage->setImagePage(0, 0, 0, 0);
                            $tileImage->cropImage($bounds['width'], $bounds['height'], $bounds['x'], $bounds['y']);
                            $tileImage->setImageFormat($this->format);
                            if ($this->format == 'jpg') {
                                $tileImage->setImageCompression(Imagick::COMPRESSION_JPEG);
                            }
                            $tileImage->setImageCompressionQuality($this->quality);
                            $tileImage->writeImage($tilepath);
                            $tileImage->destroy();
                            break;
                        }
                        case 'GD': {
                            $tileImage = imagecreatetruecolor($bounds['width'], $bounds['height']);
                            imagecopy($tileImage, $tempImage, 0, 0, $bounds['x'], $bounds['y'], $bounds['width'], $bounds['height']);
                            touch($tilepath);
                            imagejpeg($tileImage, $tilepath, $this->quality);
                            imagedestroy($tileImage);
                            break;
                        }
                        case 'ImageMagick': {
                            $params = ['+repage','-flatten'];
                            if ($resize) {
                                $params[] = '-thumbnail '.escapeshellarg(sprintf('%sx%s!', $resize['width'], $resize['height']));
                            }
                            if ($bounds) {
                                $params[] = '-crop '.escapeshellarg(sprintf('%dx%d+%d+%d', $bounds['width'], $bounds['height'], $bounds['x'], $bounds['y']));
                            }
                            $params[] = '-quality '.$this->quality;
                            Zord::execute($this->strategy, sprintf(
                                '%s %s %s %s',
                                $this->convert,
                                escapeshellarg($image.'[0]'),
                                implode(' ', $params),
                                escapeshellarg($tilepath)
                            ));
                            break;
                        }
                    }
                }
            }
            
            switch ($this->processor) {
                case 'Imagick':
                    break;
                case 'GD':
                    imagedestroy($tempImage);
                    break;
                case 'ImageMagick':
                    break;
            }
        }
        
        switch ($this->processor) {
            case 'Imagick':
                $image->destroy();
                break;
            case 'GD':
                imagedestroy($image);
                break;
            case 'ImageMagick':
                break;
        }
        
        file_put_contents($basePath.'.dzi', (new View('/xml/dzi', [
            'format'  => $this->format,
            'overlap' => $this->overlap,
            'size'    => $this->size,
            'width'   => $width,
            'height'  => $height
        ]))->render());
    }
}
