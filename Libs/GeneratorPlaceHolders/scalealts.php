<?php

require_once(__DIR__.'/PlaceHolderAbstract.php');

class PlaceHolder_scalealts extends PlaceHolderAbstract{
	
	protected function _getDefaultParamsArray(){
		// no additional params required
		return array();
	}
	
	// return a list of alterations for scale $strNote of type $intScaleType
	protected function _render(){
		
		// getting note
		$strNote = $this->_getNote();
		$intScaleType = $this->_getScaleType();
		
		// default scale alts
		$arrAlts = array();
		$strResult = '';
		
		if($intScaleType == self::MAJOR){
			// getting alterations
			$arrAlts = $this->getAltForMajorScaleOf($strNote);
		}
		
		foreach($arrAlts as $strAltNote => $strAlt){
			if(!empty($strResult)){
				$strResult.=', ';
			}
			
			$strResult.=$this->translateNote($strAltNote).$strAlt;
		}
		
		return $strResult;
	}
}
