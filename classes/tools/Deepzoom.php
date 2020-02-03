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

class Deepzoom extends ImageProcessor {
    
    public $mode    = 0755;
    public $overlap = 1;
    
    public function run() {    
        $basePath = $this->folder.DS.pathinfo(basename($this->file), PATHINFO_FILENAME);
        $filesPath = $basePath.'_files';
        mkdir($filesPath, $this->mode, true);
        $numLevels = (integer) ceil(log(max([$this->width, $this->height]), 2));
        foreach (range($numLevels, 0) as $level) {
            $tileContainerPath = $filesPath.DS.$level;
            if (!is_dir($tileContainerPath)) {
                mkdir($tileContainerPath, $this->mode);
            }
            $scale      = pow(0.5, $numLevels - $level);
            $tileWidth  = (integer) ceil($this->width * $scale);
            $tileHeight = (integer) ceil($this->height * $scale);
            $tempImage = null;
            switch ($this->processor) {
                case 'Imagick': {
                    $tempImage = clone $this->image;
                    $tempImage->resizeImage($tileWidth, $tileHeight, Imagick::FILTER_LANCZOS, 1, false);
                    break;
                }
                case 'GD': {
                    $tempImage = imagecreatetruecolor($tileWidth, $tileHeight);
                    if (imagecopyresampled(
                            $tempImage, $this->image,
                            0, 0, 0, 0,
                            $tileWidth, $tileHeight, $this->width, $this->height) === false) {
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
                    $tilepath = $filesPath.DS.$level.DS.$column.'_'.$row.'.'.$this->format;
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
                            $tileImage = clone $this->image;
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
                                escapeshellarg($this->image.'[0]'),
                                implode(' ', $params),
                                escapeshellarg($tilepath)
                            ));
                            break;
                        }
                    }
                }
            }
            $this->destroy($tempImage);
        }        
        file_put_contents($basePath.'.dzi', (new View('/xml/dzi', [
            'format'  => $this->format,
            'overlap' => $this->overlap,
            'size'    => $this->size,
            'width'   => $this->width,
            'height'  => $this->height
        ]))->render());
    }
}
