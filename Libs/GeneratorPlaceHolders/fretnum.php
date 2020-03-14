<?php

require_once(__DIR__.'/PlaceHolderAbstract.php');

class PlaceHolder_fretnum extends PlaceHolderAbstract{
	
	protected function _getDefaultParamsArray(){
		// no additional params required
		return array();
	}
	
	// return the fret num for current the note
	// Params : 
	// $intString : (int) string number
	// $intAdjust : (int) adjust value : add or remove the given value from the fret number. Mainly used when the note is not on the first fret of the img
	// $intDegre : (int) degre : do we have to search the pos for the main note or another relative note ?
	// $intMinFret : (int) min fret : min fret on which the note could be placed
	protected function _render($intString = 6, $intAdjust = 0, $intDegre = 0, $intMinFret = 0, $intMinFretFromRootString = false, $intMinFretRootMargin = false){
		
		// getting note
		$strNote = $this->_getNote();
		$intScaleType = $this->_getScaleType();
		
		$this->_debugMessage('Params : '.$strNote. ' -> '.$intDegre);
		
		// checking degree value
		if($intDegre < 0) $intDegre = 0;
					
		if($intDegre){
				// getting current scale
				$arrScale = $this->getScaleOf($strNote, $intScaleType);
				// setting new note name
				$strNote = $arrScale[$intDegre-1];
		}
		
		$this->_debugMessage('Real Note : '.$strNote.' -> '.$intDegre);
		
		// do we have a min fret to be calculated
		if($intMinFretFromRootString){
			// yes
			// getting root note fret number
			$intRootFret = intval($this->_render($intMinFretFromRootString, $intAdjust, 0, $intMinFret));
			// setting new min fret value
			$intMinFret  = $intRootFret+$intMinFretRootMargin;
		}
		
		// getting fret array
		$arrFret = $this->getFretNumberForNote($strNote, $intString, true, $intMinFret);
		
		// getting default fret
		$intFret = intval($arrFret[0]);
	
		foreach($arrFret as $intFretNum){
			$intFretNum = intval($intFretNum);
			
			if($intFretNum >= $intMinFret){
				$intFret = $intFretNum;
				break;
			}
		}
		
		$this->_debugMessage('Fret number found : '.$intFret.' -> '.$strNote);
		
		if(is_numeric($intAdjust) && $intAdjust){
			// updating fret number
			$intFret = $intFret+intval($intAdjust);
			$this->_debugMessage('Applying adjust : '.intval($intAdjust).' -> '.$intFret);
		}
		
		// setting default result string
		$strResult = $intFret.'';
		return $strResult;
	}
}
