<?php

require_once(__DIR__.'/PlaceHolderAbstract.php');

class PlaceHolder_note extends PlaceHolderAbstract{
	
	protected function _getDefaultParamsArray(){
		// no additional params required
		return array();
	}
	
	// returns the translated note name
	protected function _render($intDegre = false){
		
		// getting note
		$strNote = $this->_getNote();
		
		// do we only have to translate note name
		if(!$intDegre){ 
			// yes
			return $this->translateNote($strNote);
		}
		
		// getting scale type
		$intScaleType = $this->_getScaleType();
		// getting scale
		$arrScale = $this->getScaleOf($strNote, $intScaleType);
		// getting note num
		$intNoteNum = $intDegre-1;

		// translating note name
		return $this->translateNote($arrScale[$intNoteNum]);
	}
}
