<?php
// class for capturing an image from another file (e.g. within a directory)
// Parameters in options.json:
//   thumbnails.generator-cfg.file.files : Array of filenames that will be
//     checked and used as thumbnail if they exist. Filenames can be relative
//     to the path of the file/folder to be thumbnailed or absolute.

class thumbgen_file extends thumbgen {
	private static $default_files = ["Folder.jpg","Cover.jpg"];
	
	public function capture($source, $type, $capture=null, $minwidth=0, $minheight=0) {
		if (!is_dir($source)) $source=dirname($source);
		foreach($this->context->query_option('thumbnails.generator-cfg.file.files',thumbgen_file::$default_files) as $x) {
			$capture=(substr($x,0,1)=='/'?"":"$source/").$x;
			if (file_exists($capture)) return $capture;
		}
		return null;
	}
}