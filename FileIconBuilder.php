<?php
/**
 * Class FileIconBuilder
 *
 * Creates icons based on an file extension name and colors/templates associated with
 * mime types
 */
class FileIconBuilder {

    protected $templatedir;
    protected $fontdir;
    protected $mimefile;
    protected $mimetypes;
    protected $colors;

    /**
     * Constructor
     *
     * Initializes the default colors
     */
    public function __construct() {
        $this->templatedir = __DIR__.'/templates/';
        $this->fontdir     = __DIR__.'/fonts/';
        $this->mimefile    = __DIR__.'/mime.types';



        // default colors, additions welcome
        $this->colors = array(
            // used if nothing matches
            ''            => '#33333',

            // basic mime types
            'image'       => '#999900',
            'video'       => '#990099',
            'audio'       => '#009900',
            'text'        => '#999999',
            'application' => '#990000',
            'chemical'    => '#009999',

            // self defined types
            'package'           => '#996600',
            'geo'               => '#99CC00',
            'document-office'   => '#009999',
            'document-print'    => '#666666',
            'text-code'         => '#336699',
        );
    }

    /**
     * Define a color for a mime type
     *
     * Colors can be given as HTML hex colors
     *
     * @param string $mime
     * @param string $color
     */
    public function setColor($mime, $color) {
        $this->colors[$mime] = $color;
    }

    /**
     * Where to find the font files
     *
     * Needs trailing slash!
     *
     * @param string $fontdir
     */
    public function setFontdir($fontdir) {
        $this->fontdir = $fontdir;
    }

    /**
     * Where to find the known mimetypes
     *
     * This is a standard Linux /etc/mime.types files
     *
     * @param string $mimefile
     */
    public function setMimefile($mimefile) {
        $this->mimefile = $mimefile;
    }

    /**
     * Where to find the icon templates
     *
     * Needs trailing slash!
     *
     * @param string $templatedir
     */
    public function setTemplatedir($templatedir) {
        $this->templatedir = $templatedir;
    }

    /**
     * Create icons for all known extension from mime.types
     *
     * @param string $outdir directory to otput the files to
     */
    public function createAll($outdir) {
        if(!$this->mimetypes) $this->loadmimetypes();
        @mkdir($outdir);
        @mkdir("$outdir/16x16");
        @mkdir("$outdir/32x32");

        foreach(array_keys($this->mimetypes) as $ext) {
            $this->create16x16($ext, "$outdir/16x16/$ext.png");
            $this->create32x32($ext, "$outdir/32x32/$ext.png");
        }
    }

    /**
     * Create a 16x16 icon for the given extension
     *
     * @param string $ext extension name
     * @param string $out output file (png)
     */
    public function create16x16($ext, $out) {
        $box = array(0, 7, 15, 13);
        $tpl = $this->ext2template($ext, '16x16');
        $rgb = $this->ext2color($ext);

        $im = imagecreatefrompng($tpl);
        imagesavealpha($im, true);

        $this->drawcolorbox($im, $box, $rgb, 'corner');
        $this->drawtext($im, $box, strtoupper($ext), 6.0, 'pf_tempesta_five_compressed.ttf');

        imagepng($im, $out, 9);
        imagedestroy($im);
    }

    /**
     * Create a 32x32 icon for the given extension
     *
     * @param string $ext extension name
     * @param string $out output file (png)
     */
    public function create32x32($ext, $out) {
        $box = array(4, 22, 26, 28);
        $tpl = $this->ext2template($ext, '32x32');
        $rgb = $this->ext2color($ext);

        $im = imagecreatefrompng($tpl);
        imagesavealpha($im, true);

        $this->drawcolorbox($im, $box, $rgb, 'border');
        $this->drawtext($im, $box, strtoupper($ext), 6.0, 'pf_tempesta_five.ttf');

        imagepng($im, $out, 9);
        imagedestroy($im);
    }

    /**
     * Draws the extension text
     *
     * @param        $im
     * @param array  $box
     * @param string $text
     * @param float  $size
     * @param string $font
     */
    protected function drawtext($im, $box, $text, $size, $font) {
        list($left, $top, $right, $bottom) = $box;

        // calculate offset for centered text
        $bbox   = imagettfbbox(6.0, 0, $this->fontdir.$font, $text);
        $width  = $bbox[2];
        $offset = floor(($right - $left  - $width) / 2.0);
        $offset = $left + $offset;
        //if($offset < $left) $offset = $left;

        // write text
        $c = imagecolorallocate($im, 255, 255, 255);
        imagettftext(
            $im, 6.0, 0, $offset, $bottom, -1 * $c,
            $this->fontdir.$font,
            $text
        );
    }

    /**
     * Draws the colored box for the extension text
     *
     * @param        $im
     * @param array  $box
     * @param array  $rgb
     * @param string $bevel (border|corner)
     */
    protected function drawcolorbox($im, $box, $rgb, $bevel = '') {
        list($left, $top, $right, $bottom) = $box;
        list($r, $g, $b) = $rgb;

        for($x = $left; $x <= $right; $x++) {
            for($y = $top; $y <= $bottom; $y++) {

                // Alpha transparency for bevels
                $alpha = 0;
                if($bevel == 'corner') {
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
                    } elseif(
                        ($x == $left + 1 || $x == $right - 1) &&
                        ($y == $top || $y == $bottom)
                    ) {
                        $alpha = 32;
                    } elseif ( $y == $right ) {
                        $alpha = 32;
                    }
                } else if($bevel == 'border') {
                    if($x == $left || $x == $right) {
                        $alpha = 32;
                    }
                }

                $c = imagecolorallocatealpha($im, $r, $g, $b, $alpha);
                imagesetpixel($im, $x, $y, $c);
            }
        }
    }

    /**
     * Returns a color for the given extension
     *
     * @param string $ext
     * @return array RGB integers as array
     */
    protected function ext2color($ext) {
        // try to match extension first
        if(isset($this->colors["extension-$ext"])) {
            return $this->hex2rgb($this->colors["extension-$ext"]);
        }

        // find the mimetype
        if(!$this->mimetypes) $this->loadmimetypes();
        if(isset($this->mimetypes[$ext])) {
            $mime = $this->mimetypes[$ext];
        } else {
            $mime = 'application/octet-stream';
        }
        $mime = preg_split('/[\/\.\-]/', $mime);

        // try to find as exact match as possible
        while(count($mime)) {
            $test = join('-', $mime);
            if(isset($this->colors[$test])) {
                return $this->hex2rgb($this->colors[$test]);
            }
            array_pop($mime);
        }

        return $this->hex2rgb($this->colors['']);
    }

    /**
     * Returns a template for the given extension
     *
     * @param string $ext
     * @param string $size
     * @return string the template file
     */
    protected function ext2template($ext, $size) {
        // try to match extension first
        if(file_exists($this->templatedir."$size/extension-$ext.png")) {
            return $this->templatedir."$size/extension-$ext.png";
        }

        // find the mimetype
        if(!$this->mimetypes) $this->loadmimetypes();
        if(isset($this->mimetypes[$ext])) {
            $mime = $this->mimetypes[$ext];
        } else {
            $mime = 'application/octet-stream';
        }
        $mime = preg_split('/[\/\.\-]/', $mime);

        // try to find as exact match as possible
        while(count($mime)) {
            $test = join('-', $mime);
            if(file_exists($this->templatedir."$size/$test.png")) {
                return $this->templatedir."$size/$test.png";
            }
            array_pop($mime);
        }

        return $this->templatedir."$size/application.png";
    }

    /**
     * Convert a hex color code to an rgb array
     *
     * @param string $hex HTML color code
     * @return array RGB integers as array
     */
    protected function hex2rgb($hex) {
        // strip hash
        $hex = str_replace('#', '', $hex);

        // normalize short codes
        if(strlen($hex) == 3) {
            $hex = substr($hex, 0, 1).
                substr($hex, 0, 1).
                substr($hex, 1, 1).
                substr($hex, 1, 1).
                substr($hex, 2, 1).
                substr($hex, 2, 1);
        }

        // calc rgb
        return array(
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2))
        );
    }

    /**
     * Load mimetypes for extensions
     */
    protected function loadmimetypes() {
        $this->mimetypes = array();

        $lines = file($this->mimefile);
        foreach($lines as $line) {
            // skip comments
            $line = preg_replace('/#.*$/', '', $line);
            $line = trim($line);
            if($line === '') continue;

            $exts = preg_split('/\s+/', $line);
            $mime = array_shift($exts);
            if(!$exts) continue;
            foreach($exts as $ext) {
                if(empty($ext)) continue;
                if(strlen($ext) > 4) continue; // we only handle 4 chars or less
                $this->mimetypes[$ext] = $mime;
            }
        }
    }

}
