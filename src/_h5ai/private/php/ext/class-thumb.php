<?php

#function myerror($msg) {
#	error_log(date("Ymd H:i:s")." - $msg\n",3,"/mnt/home/www/root/h5ai.log");
#}

class Thumb {
    private static $THUMB_CACHE = 'thumbs';

    private $context;
    private $setup;
    private $thumbs_path;
    private $thumbs_href;
	
    public function __construct($context) {
        $this->context = $context;
        $this->setup = $context->get_setup();
        $this->thumbs_path = $this->setup->get('CACHE_PUB_PATH') . '/' . Thumb::$THUMB_CACHE;
        $this->thumbs_href = $this->setup->get('CACHE_PUB_HREF') . Thumb::$THUMB_CACHE;

        if (!is_dir($this->thumbs_path)) {
            @mkdir($this->thumbs_path, 0755, true);
        }
    }

    public function thumb($type, $source_href, $width, $height) {
        $source_path = $this->context->to_path($source_href);

		// check if a thumb exists in cache
        if (!file_exists($source_path) || Util::starts_with($source_path, $this->setup->get('CACHE_PUB_PATH'))) {
            return null;
		}
		
		$name = 'thumb-' . sha1($source_path) . '-' . $width . 'x' . $height . '.jpg';
        $thumb_path = $this->thumbs_path . '/' . $name;
        $thumb_href = $this->thumbs_href . '/' . $name;
        if (file_exists($thumb_path) && filemtime($source_path) <= filemtime($thumb_path))
			return $thumb_href;

		// no thumb exists, capture an image
		$capture_path = $this->thumbs_path . '/capture-' . sha1($source_path) . '.jpg';
		if (!file_exists($capture_path) || filemtime($source_path)>filemtime($capture_path)) { 

			foreach ($this->context->query_option("thumbnails.generators.$type", []) as $s) {
				list($class,$mode)=explode("-","$s-");
				$class='thumbgen_'.$class;

				// get the thumbnail generator object
				if (!($g=thumbgen::getGenerator($class,$this->context))) continue;
				if ($mode) $g->setMode($mode);
				$x=$g->generate($source_path, $type, $capture_path, $width, $height);
				if ($x) {
					$capture_path=$x;
					break;
				}
			}
		}
		if (!file_exists($capture_path)) return null;

		// create a thumbnail
	    $image = new Image();
		$image->set_source($capture_path);

		if ($this->context->query_option('thumbnails.method',1) == 1)
			$image->thumb($width, $height);
		else 
			$image->thumb2($width, $height);
		$image->save_dest_jpeg($thumb_path, 80);

		return file_exists($thumb_path) ? $thumb_href : null;
    }
}

class Image {
    private $source_file;
    private $source;
    private $width;
    private $height;
    private $type;
    private $dest;

    public function __construct($filename = null) {
        $this->source_file = null;
        $this->source = null;
        $this->width = null;
        $this->height = null;
        $this->type = null;

        $this->dest = null;

        $this->set_source($filename);
    }

    public function __destruct() {
        $this->release_source();
        $this->release_dest();
    }

    public function set_source($filename) {
        $this->release_source();
        $this->release_dest();

        if (is_null($filename)) {
            return;
        }

        $this->source_file = $filename;

        list($this->width, $this->height, $this->type) = @getimagesize($this->source_file);

        if (!$this->width || !$this->height) {
            $this->source_file = null;
            $this->width = null;
            $this->height = null;
            $this->type = null;
            return;
        }

        $this->source = imagecreatefromstring(file_get_contents($this->source_file));
	}

    public function save_dest_jpeg($filename, $quality = 80) {
        if (!is_null($this->dest)) {
            @imagejpeg($this->dest, $filename, $quality);
            @chmod($filename, 0775);
        }
    }

    public function release_dest() {
        if (!is_null($this->dest)) {
            @imagedestroy($this->dest);
            $this->dest = null;
        }
    }

    public function release_source() {
        if (!is_null($this->source)) {
            @imagedestroy($this->source);
            $this->source_file = null;
            $this->source = null;
            $this->width = null;
            $this->height = null;
            $this->type = null;
        }
    }

    public function thumb($width, $height) {
        if (is_null($this->source)) {
            return;
        }

        $src_r = 1.0 * $this->width / $this->height;

        if ($height == 0) {
            if ($src_r >= 1) {
                $height = 1.0 * $width / $src_r;
            } else {
                $height = $width;
                $width = 1.0 * $height * $src_r;
            }
            if ($width > $this->width) {
                $width = $this->width;
                $height = $this->height;
            }
        }

        $ratio = 1.0 * $width / $height;

        if ($src_r <= $ratio) {
            $src_w = $this->width;
            $src_h = $src_w / $ratio;
            $src_x = 0;
        } else {
            $src_h = $this->height;
            $src_w = $src_h * $ratio;
            $src_x = 0.5 * ($this->width - $src_w);
        }

        $width = intval($width);
        $height = intval($height);
        $src_x = intval($src_x);
        $src_w = intval($src_w);
        $src_h = intval($src_h);

        $this->dest = imagecreatetruecolor($width, $height);
        $icol = imagecolorallocate($this->dest, 255, 255, 255);
        imagefill($this->dest, 0, 0, $icol);
        imagecopyresampled($this->dest, $this->source, 0, 0, $src_x, 0, $width, $height, $src_w, $src_h);
    }

    public function thumb2($width, $height=0) {
        if (is_null($this->source)) {
            return;
        }
		if ($height == 0) $height=$width;
		$src_r = 1.0 * $this->width / $this->height;
		$ratio = 1.0 * $width / $height;
		if ($src_r<$ratio) {
			$dest_h=min($height,$this->height);
			$dest_w=$src_r*$height;
		} else {
			$dest_w=min($width,$this->width);
			$dest_h=$width/$src_r;
		}

        $width = intval($width);
        $height = intval($height);
        $dest_h = intval($dest_h);
        $dest_w = intval($dest_w);

        $this->dest = imagecreatetruecolor($width, $height);
        $icol = imagecolorallocatealpha($this->dest, 255, 255, 255, 127);
        imagefill($this->dest, 0, 0, $icol);
		imagesavealpha($this->dest, TRUE);
        imagecopyresampled($this->dest, $this->source,
			intval(($width-$dest_w)/2),
			intval(($height-$dest_h)/2),
			0,0,
			$dest_w,$dest_h,
			$this->width, $this->height);
    }
}
