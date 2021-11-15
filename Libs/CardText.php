<?php

class CardText{

	const ZERO      = 0;
	const SINGULAR  = 1;
	const PLURAL    = 2;
	const PLURALVAL = 3;
	const PLURALPLACE = '__VALUE__';

	// text
	protected $_mText = false;
	// translation domain
	protected $_strDomain = false;
	// text prefix
	protected $_strPrefix = false;
	// translation context
	protected $_strContext = false;
	// list of entities that have to be replaced before
	// translation
	protected $_arrPreEntities = array();
	// does current Text have a plural
	protected $_hasPlural = false;
	// does current Text have a plural with a possible zero value
	protected $_hasZeroPlural = false;
	// default plural value
	protected $_mPluralVal = false;
	// list of change that have to be done post translation
	protected $_arrPostFilters = array();

	// constructor
	// $mText text id
	// $strDomain translation domain
	// $strPrefix string that should be added as a prefix after translation
	// $strContext spécific context translation
	public function __construct($mText, $strDomain = false, $strPrefix = false, $strContext = false){
		// setting text
		$this->_setContent($mText) ;
		// setting domain
		if($strDomain) $this->_setDomain($strDomain);
		// setting prefix
		if($strPrefix) $this->_setPrefix($strPrefix);
		// setting context
		if($strContext) $this->_setContext($strContext);
		// done
		return $this;
	}

	// add one or more pretranslation entities
	public function addPreEntity($strName, $mValue = false){
		
		// do we have an array of entities
		if(is_array($strName)){
			// yes
			// extracting entities
			$arrEntities = $strName;
			// adding entities
			foreach($arrEntities as $strName => $mValue){
				
				// could we insert the entity
				if(!is_string($mValue) && !is_numeric($mValue)){
					// no. Only strings an numbers are allowed.
					// skipping content
					continue;
				}
				// adding preEntity
				$this->addPreEntity($strName, $mValue);
			}
			
			//done
			return $this;
		}
		
		// checking key
		if(!is_string($strName)){
			throw new Exception('String expected !');
		}
		
		// checking value
		if(!is_string($mValue) && !is_numeric($mValue)){
			throw new Exception('String expected !');
		}
		
		// adding entity
		$this->_arrPreEntities[$strName] = $mValue;
		// done
		return $this;
	}

	// add a filter that will be applied post translation, on getText call
	// $mClosure should be a string that can be evaluated as a closure or a closure object
	// the closure must accept a single parameter, and return a string
	public function addPostFilter($strFilterFunc){
		
		// do we have a string
		if(!is_string($strFilterFunc)){
			throw new Exception('String expected ! Do not send closure, function will be evaluated on the fly');
		}
		
		try{
			// getting closure, in order to test the filter
			eval('$mClosure = '.$strFilterFunc);
		}catch(Throwable $oException){
			throw new Exception("Syntax error with evaluting function : $strFilterFunc");
			exit;
		}
				
		// do we have a real closure
		if(!$mClosure instanceof Closure){
			throw new Exception('The evaluated string does not return a closure !');
		}
		
		// checking filter
		$strResult = $mClosure('testString');
		
		// checking result
		if((!is_string($strResult) && !is_numeric($strResult)) || empty($strResult)){
			throw new Exception('Filter does not return a string !');
		}
		
		// adding filter
		$this->_arrPostFilters[] = $strFilterFunc;
		
		// done
		return $this;
	}

	// return the current content
	public function getContent($intType = false){
		
		// do we have a type given
		if($intType === self::ZERO || $intType === self::SINGULAR || $intType === self::PLURAL){
			// yes
			// do we have a plural
			if(!$this->_hasPlural){
				// no
				throw new Exception('CardText has no plural set !');
			}
			// do we have a zero
			if($intType === self::ZERO && !$this->_hasZeroPlural){
				throw new Exception('CardText has no zero plural set !');
			}
			
			// returns value of type $intType
			return $this->_mText[$intType];
		}
		
		return $this->_mText;
	}
	
	// sets contents
	// $strText : text :)
	protected function _setContent($mText, $intType = false){
		
		// do we have a type given
		if($intType === self::ZERO || $intType === self::SINGULAR || $intType === self::PLURAL){
			// yes
			// do we have a plural
			if(!$this->_hasPlural){
				// no
				throw new Exception('CardText has no plural set !');
			}
			
			if($intType === self::ZERO && !$this->_hasZeroPlural){
				throw new Exception('CardText has no zero plural set !');
			}
			
			if(!is_string($mText) && !is_numeric($mText)){
				throw new Exception('String expected !');
			}
			
			// setting value
			$this->_mText[$intType] = $mText;
			
			// done
			return $this;
		}
		
		// do we have a plural by default
		if($this->_hasPlural && !is_array($mText)){
			// yes, but given value is a single text
			throw new Exception('CardText has a plural, but only singular has been set');
		}
		
		// do we have a plural
		if(is_array($mText)){
			// getting number of items
			$intItems = count($mText);
			
			// setting mText default value
			$this->_mText = array();
			// setting plural flag
			$this->_hasPlural = true;
			
			// do we have less than 3 items
			if($intItems < 3){
				// yes. Missing items
				throw new Exception('Plural requires 3 at least parameters : (singular msg, plural msg, value) or (zero msg, singular msg, plural msg, value)');
			}
			
			// do we have too much params
			if($intItems > 4){
				// yes
				throw new Exception('Too much parameters for plural !');
			}
			
			// do we have a plural with the zero option
			if($intItems == 4){
				// yes
				// extracting values
				$this->_mText[self::ZERO]     = $mText[0];
				$this->_mText[self::SINGULAR] = $mText[1];
				$this->_mText[self::PLURAL]   = $mText[2];
				// extracting value
				$this->_mPluralVal            = $mText[3];
				// setting flag for zero
				$this->_hasZeroPlural         = true;
				
				// do we have a text for the Zero option
				if(!is_string($this->_mText[self::ZERO]) || empty($this->_mText[self::ZERO])){
					// no
					throw new Exception('No text given for zero option !');
				}
			}
			else{
				// 3 items. It means no zero option
				// extracting values
				$this->_mText[self::SINGULAR] = $mText[0];
				$this->_mText[self::PLURAL]   = $mText[1];
				// extracting value
				$this->_mPluralVal            = $mText[2];
			}
			
			// do we have a text for the singular
			if(!is_string($this->_mText[self::SINGULAR]) || empty($this->_mText[self::SINGULAR])){
				// no
				throw new Exception('No text given for singular !');
			}
			
			// do we have a text for the plural
			if(!is_string($this->_mText[self::PLURAL]) || empty($this->_mText[self::PLURAL])){
				// no
				throw new Exception('No text given for plural !');
			}
			
			// done
			return $this; 
		}
		
		// do we have a string
		if(!is_string($mText) && !is_numeric($mText)){
			// no
			throw new Exception('String expected !');
		}
		
		// updating text
		$this->_mText = $mText;
		// done
		return $this;
	}
	
	// sets Domain
	protected function _setDomain($strDomain){
		
		// do we have a string
		if(!is_string($strDomain) && !is_numeric($strDomain)){
			// no
			throw new Exception('String expected !');
		}
		
		// updating domain
		$this->_strDomain = $strDomain;
		// done
		return $this;
	}
	
	// sets Prefix
	protected function _setPrefix($strPrefix){
		
		// do we have a string
		if(!is_string($strPrefix) && !is_numeric($strPrefix)){
			// no
			throw new Exception('String expected !');
		}
		
		// updating prefix
		$this->_strPrefix = $strPrefix;
		// done
		return $this;
	}
	
	// sets Context
	protected function _setContext($strContext){
		
		// do we have a string
		if(!is_string($strContext) && !is_numeric($strContext)){
			// no
			throw new Exception('String expected !');
		}
		
		// updating prefix
		$this->_strContext = $strContext;
		// done
		return $this;
	}
	
	// return true if object has a plural set
	public function hasPlural(){
		return $this->_hasPlural;
	}
	
	// sets plural number
	public function setPluralValue($mVal){
				
		// setting value
		$this->_mPluralVal = $mVal;
		
		// done
		return $this;
	}
	
	// returns plural value
	public function getPluralValue(){
	
		// do we have a plural
		if(!$this->hasPlural()){
			// no
			return false;
		}
	
		// do we already have an int
		if(is_int($this->_mPluralVal)){
			// yes
			return $this->_mPluralVal;
		}
	
		// do we have to extract int value
		if(is_numeric($this->_mPluralVal)){
			// yes
			return intval($this->_mPluralVal);
		}
	
		// no
		// do we have a string
		if(!is_string($this->_mPluralVal)){
			throw new Exception('String expected !');
		}
		
		// yes
		// extracting value
		$mPlural = $this->_mPluralVal;
		// updating plural val with pre translation changes
		$mPlural = $this->_replaceEntities($mPlural);
		// applying post filters on plural val
		$mPlural = $this->_applyPostfilters($mPlural);
		// done
		return $mPlural;
	}
	
	// replace predefined entities in strText
	protected function _replaceEntities($strText){
	
		// do we have a plural text
		if(is_array($strText)){
			// yes
			// setting default result
			$arrResult = array();
			// make changes on all textes
			foreach($strText as $strSubTxt){
				$arrResult[] = $this->_replaceEntities($strSubTxt);
			}
			// done
			return $arrResult;
		}
	
		// do we have some entites that have to applied before transaltion
		if(!is_array($this->_arrPreEntities) || empty($this->_arrPreEntities)){
			//no
			return $strText;
		}
	
		foreach($this->_arrPreEntities as $strEntity => $strValue){
			// yes
			// updating text
			$strText = str_replace('%%'.$strEntity.'%%', $strValue, $strText);
		}
				
		// turning %% into % to ensure vars to be replacable by skeletons
		$strText = str_replace('%%', '%', $strText);
		// done
		return $strText;
	}
	
	// apply all post filters on $strText
	protected function _applyPostfilters($strText){
	
		// do we have filters
		if(!is_array($this->_arrPostFilters) || empty($this->_arrPostFilters)){
			// no
			return $strText;
		}
		
		// yes
		foreach($this->_arrPostFilters as $strFunc){
			
			try{
				// getting closure
				eval('$oClosure = '.$strFunc);
			}catch(Throwable $oException){
				throw new Exception("Syntax error with evaluting post filter : $strFunc");
				exit;
			}
			
			// filtering and updatig content
			$strText = $oClosure($strText);
		}
		
		return $strText;
	}
	
	// returns the text translated
	public function getText(){
		
		// extracting text with pre translation changes made
		$mText = $this->_replaceEntities($this->_mText);
		
		// do we have to insert the plural value
		if($this->_hasPlural){
			// extracting plural value
			$mPlural = $this->getPluralValue();
			
			// checking value
			if(!is_numeric($mPlural)){
				throw new Exception('Plural value is not an int');
			}
			
			// getting plural value as int
			$intPlural = intval($mPlural);
			
			// do we have a zero plural option
			if($intPlural === 0 && $this->_hasZeroPlural){
				// yes
				// keeping only the id for zero, as it will be a simple translation
				$mText = $mText[self::ZERO];
			}
			else{
				// do we have a zero plural option
				if($this->_hasZeroPlural){
					// yes
					// so we have to remove the zero entry
					unset($mText[self::ZERO]);
					// updating mText
					$mText = array($mText[self::SINGULAR], $mText[self::PLURAL], $intPlural);
				}
			} 
		}		
	
		// retreives the translation
		$strTrs = Bootstrap::getInstance()->i18n()->_t($mText, $this->_strDomain, $this->_strContext);
		
		// do we have a prefix
		if($this->_strPrefix){
			$strTrs = $this->_strPrefix.$strTrs;
		}
		
		// do we have to replace the pluralval	
		if($this->_hasPlural){
			// yes
			$strTrs = str_replace(self::PLURALPLACE, $mText[2], $strTrs);
		}
		// returns translated string
		return $this->_applyPostfilters($strTrs);
	}
}
