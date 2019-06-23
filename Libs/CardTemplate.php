<?php

class CardTemplate{
	
	protected $_intHasVar   = false;
	protected $_arrVarIndex = array();
	
	public function __construct(){}
	
	// sets a variable
	public function setVar($strName, $mValue){
			$this->$strName = $mValue;
			$this->_intHas  = true;
			$this->_arrVarIndex[] = $strName;
			return $this;
	}
	
	// sets many variables at once
	public function setVars($arrVars){
		foreach($arrVars as $strName => $mValue){
				$this->setVar($strName, $mValue);
		}
		return $this;
	}
	
	// returns true if at least one var has been set
	public function hasvar(){
		return $this->_intHas;
	}
	
	// return index pos of a variable
	public function getVarIndex($strName){
		if(!isset($this->$strName)){
				return false;
		}
		
		return array_search($strName, $this->_arrVarIndex); 
	}
	
	// return true if $strName1 is placed before $strName2
	public function isVarBefore($strName1, $strName2){
		
		if(!isset($this->$strName1)){
				return false;
		}
		
		if(!isset($this->$strName2)){
				return true;
		}
		
		$intIndex1 = $this->getVarIndex($strName1);
		$intIndex2 = $this->getVarIndex($strName2);
		
		if($intIndex1 < $intIndex2){
				return true;
		}
		
		return false;
	}
	
	// minify the html		
	protected function _minify($strHTML){
		//remove redundant (white-space) characters
		$replace = array(
			//remove tabs before and after HTML tags
			'/\>[^\S ]+/s'   => '>',
			'/[^\S ]+\</s'   => '<',
			//shorten multiple whitespace sequences; keep new-line characters because they matter in JS!!!
			'/([\t ])+/s'  => ' ',
			//remove leading and trailing spaces
			'/^([\t ])+/m' => '',
			'/([\t ])+$/m' => '',
			// remove JS line comments (simple only); do NOT remove lines containing URL (e.g. 'src="http://server.com/"')!!!
			'~//[a-zA-Z0-9 ]+$~m' => '',
			//remove empty lines (sequence of line-end and white-space characters)
			'/[\r\n]+([\t ]?[\r\n]+)+/s'  => "\n",
			//remove empty lines (between HTML tags); cannot remove just any line-end characters because in inline JS they can matter!
			'/\>[\r\n\t ]+\</s'    => '><',
			//remove "empty" lines containing only JS's block end character; join with next line (e.g. "}\n}\n</script>" --> "}}</script>"
			'/}[\r\n\t ]+/s'  => '}',
			'/}[\r\n\t ]+,[\r\n\t ]+/s'  => '},',
			//remove new-line after JS's function or condition start; join with next line
			'/\)[\r\n\t ]?{[\r\n\t ]+/s'  => '){',
			'/,[\r\n\t ]?{[\r\n\t ]+/s'  => ',{',
			//remove new-line after JS's line end (only most obvious and safe cases)
			'/\),[\r\n\t ]+/s'  => '),',
			//remove quotes from HTML attributes that does not contain spaces; keep quotes around URLs!
			'~([\r\n\t ])?([a-zA-Z0-9]+)="([a-zA-Z0-9_/\\-]+)"([\r\n\t ])?~s' => '$1$2=$3$4', //$1 and $4 insert first white-space character found before/after attribute
		);
		return preg_replace(array_keys($replace), array_values($replace), $strHTML);
	}
	
	// renders the template
	public function render($strTemplateFile){
		if(!file_exists($strTemplateFile)){
				return $this;
		}
		
		ob_start();
		require($strTemplateFile);
		$strContent = ob_get_contents();
		ob_end_clean();
		
		//_minify :)
		return $this->_minify($strContent);
	}
}
