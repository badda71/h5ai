<?php
// Class for capturing an image from pdf/gs documents via "convert" or "gm" command
// Parameters in options.json: none
// Requires: convert or gm (package graphicsmagick and ghostscript)

class thumbgen_docthumb extends thumbgen {

    private static $CONVERT_CMD = 'convert -density 100 -quality 100 -strip [SRC][0] [DEST]';
    private static $GM_CONVERT_CMD = 'gm convert -density 100 -quality 100 [SRC][0] [DEST]';
	
	public function capture($source, $type, $capture=null, $minwidth=0, $minheight=0) {

		if ($this->setup->get('HAS_CMD_CONVERT')) $cmd=thumbgen_docthumb::$CONVERT_CMD;
		elseif ($this->setup->get('HAS_CMD_GM')) $cmd=thumbgen_docthumb::$GM_CONVERT_CMD;
		else return null;

		$cmd = str_replace('[SRC]', escapeshellarg($source), $cmd);
		$cmd = str_replace('[DEST]', escapeshellarg($capture), $cmd);

        exec($cmd);

		return file_exists($capture) ? $capture : null;
    }
}
