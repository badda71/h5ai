<?php
// Base class for all thumbnail generators

abstract class thumbgen {
    protected $context;
	protected $setup;
	protected $mode=null;
	protected $possibleModes=null;

	private static $objcache=array();
	private static $capcache=array();
	
	public static function getGenerator($name,...$stuff){
		if (array_key_exists($name,thumbgen::$objcache)) {
			return thumbgen::$objcache[$name];
		}
		if (class_exists($name)) {
			return (thumbgen::$objcache[$name]=new $name(...$stuff));
		}
		return null;
	}
	
	public function __construct($context) {
        $this->context = $context;
		$this->setup = $this->setup = $context->get_setup();
    }

	public function generate($source,...$s) {
		$c=get_class($this).$source;
		if (array_key_exists($c,thumbgen::$capcache)) {
			return thumbgen::$capcache[$c];
		}
		return (thumbgen::$capcache[$c] = $this->capture($source,...$s));
	}
	
	abstract protected function capture($source, $type, $capture=null, $minwidth=0, $minheight=0);

	public function setMode ($mode=null) {
		if ($this->possibleModes!==null) {
			if (in_array($mode,$this->possibleModes)) $this->mode=$mode;
			else $this->mode=$this->possibleModes[0];
		}
		else $this->mode=$mode;
	}
	
	public function setPossibleModes($modes) {
		if (is_array($modes) && count($modes)>0) {
			$this->possibleModes=$modes;
			$this->setMode();
		} else $this->possibleModes=null;
	}
	
	public function getMode () {return $this->mode;}
}