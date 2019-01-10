<?php
// Class for capturing a movie poster from tmdb.org
// Modes:
//   mov: Capture a movie poster
//   tvs: Capture a TV-series poster
//   tve: Capture a TV-series episode still
// Parameters in options.json:
//   thumbnails.generator-cfg.tmdb.api_key
//     tmdb.org API-key
//   thumbnails.generator-cfg.tmdb.language
//     default locale to use for querying tmdb (default: en-EN)
//   thumbnails.generator-cfg.tmdb.regex_<mode>
//     Regex that extracts needed information from the source pathname into
//     named subpatterns. These are different for each mode because each mode
//     requires different input into the tmdb-queries. These subpatterns are:
//       mov: query (mandatory), year (optional)
//       tvs: query (mandatory), year (optional)
//       tve: query (mandatory), year (optional), season (mandatory),
//            episode (mandatory)

class thumbgen_tmdb extends thumbgen {
    private static $baseurl="https://api.themoviedb.org/3/";
	private static $api_key_default='296d4c7bcf2b240a42cb1b64400cd176';
	private static $lang_default='en-EN';
	private static $regex_default= array(
		"mov"=> '_/(?<query>[^/.(]*)(\s*\((?<year>\d\d\d\d)\))?[^/]*$_',
		"tvs"=> '_/(?<query>[^/.(]*)(\s*\((?<year>\d\d\d\d)\))?[^/]*$_',
		"tve"=> array('_/(?<query>[^/.(]*)(\s*\((?<year>\d\d\d\d)\))?[^/]*/[^/]*S(?<season>\d+)[^/]*E(?<episode>\d+)[^/]*$_',
			'_/(?<query>[^/.(]*)(\s*\((?<year>\d\d\d\d)\))?[^/]*/[^/]*(?<season>\d?\d)(?<episode>\d\d)[^/]*$_')
	);

	private $tmdb_config=null;
	private $api_key;
	private $lang;
	
	public function __construct($context) {
		parent::__construct($context);
		$this->api_key=$this->context->query_option('thumbnails.generator-cfg.tmdb.api_key',thumbgen_tmdb::$api_key_default);
		$this->lang=$this->context->query_option('thumbnails.generator-cfg.tmdb.language',thumbgen_tmdb::$lang_default);
		$this->setPossibleModes(["mov","tvs","tve"]);
	}	
	
	public function capture($source, $type, $capture=null, $minwidth=0, $minheight=0) {
		if ($this->tmdb_config===null)
			$this->tmdb_config=json_decode(file_get_contents(thumbgen_tmdb::$baseurl."configuration?api_key=".$this->api_key));

		$regex=$this->context->query_option('thumbnails.generator-cfg.tmdb.regex_'.$this->mode,
			thumbgen_tmdb::$regex_default[$this->mode]);

		if (!is_array($regex)) $regex=array($regex);
		$regex[]=null;
		foreach ($regex as $r)
			if ($r==null) return null;
			elseif (preg_match($r,$source,$m)) break;

		switch ($this->mode) {
		case "mov":
			$movie=json_decode(@file_get_contents(thumbgen_tmdb::$baseurl."search/movie?".
				"api_key=".$this->api_key.
				"&language=".$this->lang.
				"&query=".urlencode(trim($m["query"]))."&page=1&include_adult=true".
				(array_key_exists("year",$m)?"&year=".$m["year"]:"")));
			if ($movie->total_results<1) return null;
			$size=$this->whichsize($this->tmdb_config->images->poster_sizes,$minwidth,$minheight);
			$imgurl=$this->tmdb_config->images->base_url.$size.$movie->results[0]->poster_path;
			break;
		case "tvs":
		case "tve":
			$tv=json_decode(@file_get_contents(thumbgen_tmdb::$baseurl."search/tv?".
				"api_key=".$this->api_key.
				"&language=".$this->lang.
				"&query=".urlencode(trim($m["query"]))."&page=1".
				(array_key_exists("year",$m)?"&first_air_date_year=".$m["year"]:"")));
			if ($tv->total_results<1) return null;
			if ($this->mode == "tvs") {
				$size=$this->whichsize($this->tmdb_config->images->poster_sizes,$minwidth,$minheight);
				$imgurl=$this->tmdb_config->images->base_url.$size.$tv->results[0]->poster_path;
			} else {
				$size=$this->whichsize($this->tmdb_config->images->still_sizes,$minwidth,$minheight);
				$ep=json_decode(@file_get_contents(thumbgen_tmdb::$baseurl.
					"tv/".$tv->results[0]->id.
					"/season/".$m["season"].
					"/episode/".$m["episode"].
					"?api_key=".$this->api_key.
					"&language=".$this->lang));
				if (!is_object($ep) || !property_exists($ep,"still_path")) return null;
				$imgurl=$this->tmdb_config->images->base_url.$size.$ep->still_path;
			}
		}
		@copy ($imgurl,$capture);
		return file_exists($capture) ? $capture : null;
	}

	protected function whichsize ($sizes, $minwidth=null, $minheight=null) {
		foreach($sizes as $s) {
			if ((substr($s,0,1)==="w" && substr($s,1)+0>=$minwidth) || 
				(substr($s,0,1)==="h" && substr($s,1)+0>=$minheight)) return $s;
		}
		return $sizes[count($sizes)-1];
	}
}