<?php

class FileIconBuilder {

    private $templatedir;
    private $fontdir;
    private $mimefile;
    private $mimetypes;
    private $colors;



    public function __construct(){
        $this->templatedir = __DIR__.'/templates/';
        $this->fontdir = __DIR__.'/fonts/';
        $this->mimefile = __DIR__.'/mime.types';

        // fixme find good colors
        $this->colors = array(
            'image' => '#338833',
            'text'  => '#888888',
            'video' => '#883388',
            'audio' => '#888833',
            'application' => '#d22121'
        );
    }

    /**
     * Create icons for all known extension from mime.types
     *
     * @param string $outdir directory to otput the files to
     */
    public function createAll($outdir) {
        mkdir("$outdir/16x16");

        foreach(array_keys($this->mimetypes) as $ext) {
            $this->create16x16($ext, "$outdir/16x16/$ext.png");
        }
    }


    /**
     * Create a 16x16 icon for the given extension
     *
     * @param string $ext extension name
     * @param string $out output file (png)
     */
    public function create16x16($ext, $out) {
        $im = imagecreatefrompng($this->templatedir.'16x16.png');
        imagesavealpha($im, true);

        // dimensions of the extension bar
        $left   = 0;
        $right  = 14;
        $top    = 7;
        $bottom = 13;

        list($r, $g, $b) = $this->ext2color($ext);

        // draw the extension bar
        for($x = $left; $x <= $right; $x++) {
            for($y = $top; $y <= $bottom; $y++) {

                // round the corners
                $alpha = 0;
                if($x == $left || $x == $right) {
                    switch($y) {
                        case $top:
                        case $bottom:
                            $alpha = 64;
                            break;
                        case $top + 1:
                        case $bottom - 1:
                            $alpha = 32;
                            break;
                    }
                } elseif(($x == $left + 1 || $x == $right - 1) &&
                    ($y == $top || $y == $bottom)
                ) {
                    $alpha = 32;
                }

                $c = imagecolorallocatealpha($im, $r, $g, $b , $alpha);
                imagesetpixel($im, $x, $y, $c);
            }
        }

        // write text
        $c = imagecolorallocate($im, 255, 255, 255);
        imagettftext($im, 6.0, 0, 1, 13, -1 * $c,
                     $this->fontdir.'pf_tempesta_five_condensed.ttf',
                     strtoupper($ext));

        imagepng($im, $out, 9);
        imagedestroy($im);
    }

    /**
     * Returns a color for the given extension
     *
     * @param string $ext
     * @return array RGB integers as array
     */
    private function ext2color($ext) {
        if(!$this->mimetypes) $this->loadmimetypes();

        // find the mimetype
        if(isset($this->mimetypes[$ext])){
            $mime = $this->mimetypes[$ext];
        } else {
            $mime = 'application/octet-stream';
        }

        // find an associated color for exact match or class
        if(isset($this->colors[$mime])) {
            $color = $this->colors[$mime];
        } else {
            list($mime) = explode('/', $mime);
            if (isset($this->colors[$mime])) {
                $color = $this->colors[$mime];
            } else {
                $color = '#333333';
            }
        }

        return $this->hex2rgb($color);
    }

    /**
     * Convert a hex color code to an rgb array
     *
     * @param string $hex HTML color code
     * @return array RGB integers as array
     */
    private function hex2rgb($hex) {
        // strip hash
        $hex = str_replace('#', '', $hex);

        // normalize short codes
        if(strlen($hex) == 3){
            $hex = substr($hex,0,1).
                   substr($hex,0,1).
                   substr($hex,1,1).
                   substr($hex,1,1).
                   substr($hex,2,1).
                   substr($hex,2,1);
        }

        // calc rgb
        return array(
           'r' => hexdec(substr($hex, 0, 2)),
           'g' => hexdec(substr($hex, 2, 2)),
           'b' => hexdec(substr($hex, 4, 2))
        );
    }

    /**
     * Load mimetypes for extensions
     */
    private function loadmimetypes() {
        $this->mimetypes = array();

        $lines = file($this->mimefile);
        foreach ($lines as $line){
            $exts = preg_split('/\s/', $line);
            $mime = array_shift($exts);
            if(!$exts) continue;
            foreach($exts as $ext){
                if(strlen($ext) > 3) continue; // we only handle 3 chars or less
                $this->mimetypes[$ext] = $mime;
            }
        }
    }

}