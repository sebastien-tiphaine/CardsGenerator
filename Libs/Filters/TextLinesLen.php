<?php

require_once(__DIR__.'/FilterAbstract.php');

class TextLinesLen extends FilterAbstract{

	// default params
	protected $_arrParams = array(
		'MaxLen' => 100,
		'MinLen' => 30,
		'Spliter' => "\n",
	);

	// filter text
	protected function _filter($mValue){
	
		// do we have multiple values
		if(is_array($mValue)){
			// yes
			// setting default result
			$arrResult = array();
			
			foreach($mValue as $mKey => $mSub){
				$arrResult[$mKey] = $this->_filter($mSub);
			}
			
			return $arrResult;
		}
	
		// do we have an already formated string or an empty text
		if(!is_string($mValue) || strpos($mValue, '<ul>') !== false || (strpos($mValue, '<ol>'))){
			// yes
			// so nothing to do
			return $mValue;
		}
	
		// getting spliter
		$strSpliter    = $this->_getParam('Spliter');
		$intLineMaxLen = intval($this->_getParam('MaxLen'));
		$intLineMinLen = intval($this->_getParam('MinLen'));
	
		// do we have lines that are already splited
		if(strpos($mValue, $strSpliter) !== false ){
			// yes
			// we have to split lines before filtering string
			$arrLines = explode($strSpliter, $mValue);
			// spliting each line
			foreach($arrLines as $intKey => $strLine){
				$arrLines[$intKey] = $this->_linesLen($strLine, $intLineMaxLen, $intLineMinLen, $strSpliter);
			}
			// imploding content
			return implode($strSpliter, $arrLines);
		}
	
		// justifying text
		return $this->_linesLen($mValue, $intLineMaxLen, $intLineMinLen, $strSpliter);
	}
	
	// adjusting lines len
	protected function _linesLen($strText, $intLineMaxLen, $intLineMinLen, $strSpliter = "\n"){
		
		// getting text len
		$intLen = strlen($strText); 
	
		// do we have enough chars
		if($intLen < $intLineMaxLen){
			return $strText;
		}
	
		// how many lines do we have
		$intLines     = $intLen / $intLineMaxLen;
		// how many full lines do we have
		$intFullLines = intval($intLines);
	
		// is the last line long enought
		if($intLines > $intFullLines){
			// no, the last line is shorter
			// do we have enough chars in the last line
			$intLastLineLen = $intLineMaxLen * ($intLines - $intFullLines);
					
			if($intLastLineLen < $intLineMinLen){
				// last line is too short
				// we have to reduce the Max
				$intLineMaxLen = $intLineMaxLen - round(($intLineMinLen - $intLastLineLen) / $intFullLines);
			}
		}
	
		// wrapping words
		$strText = wordwrap($strText, $intLineMaxLen, $strSpliter);	
		return $strText;
	}
}
