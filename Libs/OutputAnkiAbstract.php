<?php

require_once(__DIR__.'/OutputAbstract.php');

abstract class OutputAnkiAbstract extends OutputAbstract{

	// generated card counter
	protected $_intCardCount = 0;

	// list of card entries to keep
	protected $_arrRequiredEntries = array(
			CardGenerator::CARDNAME,
			CardGenerator::CARDCATEGORY,
			CardGenerator::QUESTION,
			CardGenerator::QUESTIONINFO,
			CardGenerator::ANSWER,
			CardGenerator::ANSWERINFO,
			CardGenerator::CARDTITLE,
			CardGenerator::CARDTAGS
	);
	
	// check card fields and values
	protected function _validateCard($arrCard = array()){
	
		// do we have an array
		if(!is_array($arrCard) || empty($arrCard)){
			// no
			return false;
		}
		
		// setting default card result
		$arrFilteredCard = array();
	
		// extracting and checking required fields
		foreach($this->_arrRequiredEntries as $strKey){
	
			if(!array_key_exists($strKey, $arrCard)){
				$this->_cliOutput(__CLASS__.':: WARN : Given card does not contains the field : '.$strKey.' !');
				// replacing value with key name
				$arrFilteredCard[$strKey] = $strKey;
				continue;
			}

			if(!is_string($arrCard[$strKey]) && !is_numeric($arrCard[$strKey])){
				$this->_cliOutput(__CLASS__.':: WARN : the card field : '.$strKey.' does not contain a string nor a number. The value will be replaced !');
				// replacing value with key name
				$arrFilteredCard[$strKey] = $strKey;
				continue;
			}
			
			// extracting card value
			$arrFilteredCard[$strKey] = $arrCard[$strKey];
		}
		
		//returns filtered content
		return $arrFilteredCard;
	}

	// format media for a local usage
	protected function _formatMedia($arrMedia, $strDataType = false){

		// do we have to filter the media
		if(!$strDataType || !$this->hasParam('Media.'.$strDataType)){
			// no
			return $arrMedia;
		}

		// checking media
		if(isset($arrMedia['media'])){
			// only keeping the file name without the path
			if(is_string($arrMedia['media'])){
				$arrMedia['media'] = basename($arrMedia['media']);
			}

			if(is_array($arrMedia['media'])){
				foreach($arrMedia['media'] as $mKey => $strMediaSrc){
					$arrMedia['media'][$mKey] = basename($strMediaSrc);
				}
			}
		}
		// done
		return $arrMedia;
	}
}
