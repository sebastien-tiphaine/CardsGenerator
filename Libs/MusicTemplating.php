<?php

require_once(__DIR__.'/MusicLogicExt.php');
require_once(__DIR__.'/CardText.php');
require_once(__DIR__.'/GeneratorPlaceHolders/PlaceHolderAbstract.php');

abstract class MusicTemplating extends MusicLogicExt{

	// template for info
	protected $_strInfoTemplate = false;

	// sub function of place holders replacement for cardText content
	protected function _placeHoldersGetStringFromCardText($strNote, $intScaleType, $mString){
		
		// do we have to extract the text from a CardText
		if(!$mString instanceof CardText){
			// no
			return $mString;
		}
		// yes
		// getting a copy
		$oCardText = clone $mString;
		// do we have a card with a plural
		if($oCardText->hasPlural()){
			// yes
			// getting plural value
			$mPluralValue = $this->_replacePlaceholders($strNote, $this->_filterScaleType($intScaleType), $oCardText->getPluralValue());
			// updating plural
			$oCardText->setPluralValue($mPluralValue); 
		}
		// getting translated text
		return $oCardText->getText();		
	}

	// find the place holders and call the appropriate function to get the result string
	// if $mString is an array
	protected function _replacePlaceholders($strNote, $intScaleType, $mString, $intRecall = 0){

		// do we have to much recalls
		if($intRecall > 5){
			// yes
			return $mString;
		}

		// filtering scaletype
		$intScaleType = $this->_filterScaleType($intScaleType);

		// ensure $mString not to be a cardText object
		$mString = $this->_placeHoldersGetStringFromCardText($strNote, $intScaleType, $mString);

		// checking string
		if(!is_string($mString)){
			// do we have an array
			if(is_array($mString)){
				// yes
				// self recall iteration
				foreach($mString as $mKey => $strString){
					$mString[$mKey] = $this->_replacePlaceholders($strNote, $intScaleType, $strString);
				}
				// returns parsed array
				return $mString;
			}
			
			// not a string, nothing can be done
			return $mString;
		}
		
		// extracting string value
		$strString = $mString;
		
		// parsing content
		$strNewSting = $this->_placeHoldersParseContent($strNote, $intScaleType, $strString);
		
		// do we have to reparse the string
		while($strString != $strNewSting){
			// updating string content
			$strString = $strNewSting;
			$strNewSting = $this->_placeHoldersParseContent($strNote, $intScaleType, $strString);
		}
		
		// debug
		$this->_debugMessage('Finale String : '.$strNewSting);
		// returning the new string
		return $strNewSting;
	}

	// parse content of string and call all mapped functions 
	protected function _placeHoldersParseContent($strNote, $intScaleType, $strString){
		
		// do we have a string
		if(!is_string($strString)){
			// no
			// nothing to parse
			$this->_debugMessage('Nothing to parse, given content is not a string !');
			return $strString;
		}
		
		// filtering scaletype
		$intScaleType = $this->_filterScaleType($intScaleType);
		
		// definig regexp :
		$strReg = '/\{([a-zA-Z\-]+):([a-zA-Z0-9\-\s,]+)\}|\{([a-zA-Z\-]+)\}/';
		
		// debug
		$this->_debugMessage('Analysing : '.$strNote.' -> '.$strString);
		
		// looking for placeholders
		if(!preg_match_all($strReg, $strString, $arrMatches)){
				$this->_debugMessage('  :: Nothing found !');
				// nothing to replace
				return $strString;
		}
		
		// rolling over parsed content
		foreach($arrMatches[1] as $intMatchKey => $strFunc){
						
			// setting default Args
			$arrArgs  = array();
			
			// do we have a simple call
			if(!is_string($strFunc) || empty($strFunc)){
				// yes
				// extracting function 
				$strFunc = $arrMatches[3][$intMatchKey];
				// debug
				$this->_debugMessage('  :: simple call found : '.$strFunc);
			}
			else{
				// extracting args
				$arrArgs = explode(',',$arrMatches[2][$intMatchKey]);
				$this->_debugMessage('  :: call with args : '.$strFunc.' -> '.$arrMatches[2][$intMatchKey]);
			}
			
			// do we have a placeHolder
			if(!PlaceHolderAbstract::PlaceHolderExists($strFunc)){
				// no
				$this->_debugMessage('  :: No class mapped for placeHolder : '.$strFunc);
				continue;
			}	
			
			// ensures numerical value not be strings
			foreach($arrArgs as $intPKey => $mValue){
				if(is_numeric($mValue)){
					$arrArgs[$intPKey] = intval($mValue);
				}
			}
																							
			// getting replace string
			$strReplace = PlaceHolderAbstract::RenderPlaceHolder($strFunc, $strNote, $intScaleType, $arrArgs, array());
			// ensure result not to be a cardText object
			$strReplace = $this->_placeHoldersGetStringFromCardText($strNote, $intScaleType, $strReplace);
				
			// updating string
			$strString  = str_replace($arrMatches[0][$intMatchKey], $strReplace, $strString);
			$this->_debugMessage('  :: output string : '.$strString);
		}
		
		// done
		return $strString;
	}
}
