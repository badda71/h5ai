<?php
// Class for capturing an image from id3 tags
// Parameters in options.json: none
// Requires: php-getid3

class thumbgen_id3 extends thumbgen {
	
	protected $getID3=null;

	public function __construct($context) {
		parent::__construct($context);
		if (include_once("getid3/autoload.php")) {
			$this->getID3=new getID3;
			$this->getID3->setOption(
				array("option_tag_id3v1" => false,
				"option_tag_id3v2" => true,
				"option_tag_lyrics3" => false,
				"option_tag_apetag" => false,
				"option_tags_process" => false,
				"option_tags_html" => false,
				"option_extra_info" => false,
				"option_save_attachments" => true,
				"option_md5_data" => false,
				"option_md5_data_source" => false,
				"option_sha1_data" => false));
		}
	}
	
	public function capture($source, $type, $capture=null, $minwidth=0, $minheight=0) {
		if (!$this->getID3) return null;
		
		$info = $this->getID3->analyze($source);
		
		$imgs = Util::array_query($info,"id3v2.APIC",null);
		if (!$imgs) $imgs = Util::array_query($info,"id3v2.PIC",null);
		if (!$imgs) return null;
		$img=$imgs[0];
		for($i=count($imgs)-1;$i>0;$i--)
			if ($imgs[$i]["picturetypeid"]==3) $img=$imgs[$i];
		if ($img["data"]) file_put_contents($capture,$img["data"]);
		return file_exists($capture) ? $capture : null;
	}
}