<?php
// Class for capturing a still image from movies via "avconv" or "ffmpeg" command
// Parameters in options.json:
//   thumbnails.generator-cfg.still.location: location of still inside the
//     video file. Can be given as seconds (e.g. '10s') or percentage of total
//     video length (e.g. '50%')
// Requires: avconv or ffmpeg (package ffmepg)


class thumbgen_still extends thumbgen {
	
	public function capture($source, $type, $capture=null, $minwidth=0, $minheight=0) {
		
		$t=$this->context->query_option('thumbnails.generator-cfg.still.location', "10s");
		if (!preg_match('/(\d+)(s|%)/',$t,$m)) {error_log("$t: not a valid still location");return null;}
		$c=$this->setup->get('HAS_CMD_AVCONV')?"avconv":($this->setup->get('HAS_CMD_FFMPEG')?"ffmpeg":"dummy");

		// set up the command
		$cmd="$c -ss ".
			($m[2]=='s'?$m[1]:
				"`$c -i ".escapeshellarg($source).
				" 2>&1 | grep Duration | awk '{print \$2}' | tr -d , | awk -F ':' '{print (\$3+\$2*60+\$1*3600)*".
				($m[1]/100)."}'`").
			" -i ".escapeshellarg($source)." -an -vframes 1 ".
			escapeshellarg($capture);
        exec($cmd);
        return file_exists($capture) ? $capture : null;
    }
}