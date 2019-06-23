<?php

require_once(__DIR__.'/PlaceHolderAbstract.php');

class PlaceHolder_scalealtsnum extends PlaceHolderAbstract{
	
	protected function _getDefaultParamsArray(){
		// no additional params required
		return array();
	}
	
	// return the number of alterations for scale $strNote of type $intScaleType
	protected function _render(){
		
		// getting note
		$strNote = $this->_getNote();
		$intScaleType = $this->_getScaleType();
		
		if($intScaleType == self::MAJOR){
			// getting alterations
			return count($this->getAltForMajorScaleOf($strNote));
		}
		
		return 0;
	}
}
