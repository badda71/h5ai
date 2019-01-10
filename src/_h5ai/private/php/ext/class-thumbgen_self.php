<?php
// class for capturing an image from an image ;-) only returns the path to the original image
// Parameters in options.json: none

class thumbgen_self extends thumbgen {

	public function capture($source, $type, $capture=null, $minwidth=0, $minheight=0) {
		return $source;
	}
}