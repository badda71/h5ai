<?php
// class for capturing an image from another file (e.g. within a directory)
// Parameters in options.json:
//   thumbnails.generator-cfg.file.files : Array of filenames that will be
//     checked and used as thumbnail if they exist. Filenames can be relative
//     to the path of the file/folder to be thumbnailed or absolute. Filenames
//     can contain wildcards - if they do, the first matching file is used.

class thumbgen_file extends thumbgen {
	private static $default_files = ["Folder.jpg","Cover.jpg"];
	
	public function capture($source, $type, $capture=null, $minwidth=0, $minheight=0) {
		if (!is_dir($source)) $source=dirname($source);
		foreach($this->context->query_option('thumbnails.generator-cfg.file.files',thumbgen_file::$default_files) as $x) {
			$dir=(substr($x,0,1)=='/'?"":"$source/");
			$file = $dir.$x;
			if (preg_match('/[\*\?\[\{]/',$x) &&
				($i=array_values(array_filter(glob(preg_replace('/(\*|\?|\[)/', '[$1]', $dir).$x,GLOB_BRACE),'is_file')))) {
				$file=$i[0];
			}
			if (!is_file($file)) continue;
			$thumb=new Thumb($this->context);
			$capture=$thumb->capture(null,$file,$minwidth,$minheight);
			if ($capture==null) continue;
			return $capture;
		}
		return null;
	}
}