<?php
// Class for capturing an image from exif header
// Parameters in options.json: none
// Requires: php exif support, i.e. exif_read_data();

class thumbgen_exif extends thumbgen {
	
	public function capture($source, $type, $capture=null, $minwidth=0, $minheight=0) {
		if (!$this->setup->get('HAS_PHP_EXIF') || $minheight < 1) return null; 

		$exif=@exif_read_data($source, NULL, false, true);
		if($exif === false || empty($exif["THUMBNAIL"]["THUMBNAIL"])) return null;
		$et=$exif["THUMBNAIL"]["THUMBNAIL"];
	
		// check if tn is a valid image and rotate if necessary
		$i_et = @imageCreateFromString($et);
		if ($i_et === false) return null;
		$o=empty($exif['Orientation'])?0:$exif['Orientation'];
		if ($o>1) {
			switch ($o) {
				case 2:
				case 4:
				case 5:
				case 7:
					imageflip($i_et, IMG_FLIP_HORIZONTAL);
			}
			switch ($o) {
				case 3:
				case 4:
					$i_et=imagerotate($i_et, 180, 0);
					break;
				case 6:
				case 7:
					$i_et=imagerotate($i_et, 270, 0);
					break;
				case 8:
				case 5:
					$i_et=imagerotate($i_et, 90, 0);
					break;
			}
			ob_start();
			imagejpeg($i_et);
			$et = ob_get_contents();
			ob_end_clean();
		}
		imagedestroy($i_et);
		file_put_contents($capture,$et);
		return $capture;
	}
}
