<?php

require_once(__DIR__.'/PlaceHolderAbstract.php');

class PlaceHolder_chord extends PlaceHolderAbstract{
	
	protected function _getDefaultParamsArray(){
		// no additional params required
		return array();
	}
	
	// return chord name
	protected function _render($intDegre = false){
		
		// getting note
		$strNote = $this->_getNote();
		$intScaleType = $this->_getScaleType();
		
		// do we only have to translate chord name
		if(!$intDegre){ 
			// yes
			return $this->translateChord($strNote);
		}
		
		// getting scale
		$arrScale = $this->getScaleOf($strNote, $intScaleType);
		// getting chord num
		$intChordNum = $intDegre-1;

		// translating chord name
		return $this->translateChord($arrScale[$intChordNum]);
	}
}
