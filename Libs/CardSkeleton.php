<?php

require_once(__DIR__.'/StoreAbstract.php');
require_once(__DIR__.'/CardGenerator.php');
require_once(__DIR__.'/SvgAdapt.php');
require_once(__DIR__.'/CardText.php');
require_once(__DIR__.'/IniHandler.php');

class CardSkeleton extends StoreAbstract{

	// setting default card array
	// please do not change data order, as params are sent to the
	// addTemplate function in the following order
	protected $_arrCard = array(
		CardGenerator::CARDID 				=> false,
		CardGenerator::CARDNAME 			=> false,
		CardGenerator::CARDCATEGORY 		=> false,
		CardGenerator::SCALETYPE 			=> false,
		CardGenerator::QUESTION 			=> false,
		CardGenerator::QUESTIONINFO 		=> false,
		CardGenerator::ANSWER 				=> false,
		CardGenerator::ANSWERINFO 			=> false,
		CardGenerator::CALLBACKCONDITION 	=> false,
		CardGenerator::CARDDISPLAYCATEGORY 	=> false,
		CardGenerator::CARDTAGS 			=> false
	);

	// list of cards that are rendered using this skeleton
	protected $_arrCardRendered = array();

	// is the object locked for modification
	protected $_intIsLocked = false;

	// list of basescale for rendering
	protected $_arrBaseScaleSvg = array();

	// stores a skeleton object
	public static function storeSkeleton($strId, $oSkeleton){
		return parent::storeObject($strId, $oSkeleton);
	}

	// returns true if skeleton $strId exists
	public static function hasSkeleton($strId){
		return parent::hasObject($strId);
	}

	//returns skeleton id
	public static function getSkeleton($strId){

		// do we have a skel loaded
		if(!self::hasSkeleton($strId)){
			// no
			$strFile = Bootstrap::getInstance()->skeletons.'/'.$strId;

			// setting default result object
			$oSkeleton = null;

			// do we have an ini file
			if(is_file($strFile.'.ini')){
				// yes
				// getting skelton object
				$oSkeleton = self::_loadFromIniFile($strFile.'.ini');
			} // do we have a php file
			else if(is_file($strFile.'.php')){
				// yes
				// getting skelton object
				$oSkeleton = self::_loadFromPhpFile($strFile.'.php');
			}
			else{
				throw new Exception('Not able to find skeleton : '.$strFile.'.[ini|php] File does not exists !');
			}

			// do we have a skeleton
			if(!$oSkeleton instanceof CardSkeleton){
				// no
				throw new Exception('Invalid Skeleton object found !');
			}

			// can we set the card id automatically ?
			if(!$oSkeleton->isLocked()){
				// yes object is not locked
				// do we already have a card id
				if(!$oSkeleton->getValue(CardGenerator::CARDID)){
					// no
					// setting id
					$oSkeleton->setValue(CardGenerator::CARDID, $strId);
				}
			}

			// storing object
			self::storeSkeleton($strId, $oSkeleton);
		}
		
		return parent::getObject($strId);
	}

	// load a php file and returns a skeleton object
	protected static function _loadFromPhpFile($strFile = false){
		
		// checking file
		if(!is_file($strFile)){
			throw new Exception('File not found : '.$strFile);
		}
		
		// getting file content
		$strLogic = file_get_contents($strFile);

		// do we have the php open tag on file start
		if(strpos($strLogic, '<?php') === 0){
			// removing first chars
			$strLogic = substr($strLogic, 6);
		}

		try{
			// setting closure to extract skeleton logic
			eval('$oSkeletonFunc = function(){'."\n".$strLogic."\n".'};');
		}catch(Throwable $oException){
			throw new Exception("Syntax error with evaluting file : $strFile");
			exit;
		}

		// calling function in order to extract skeleton object
		$oSkeleton = $oSkeletonFunc();

		if(!$oSkeleton instanceof CardSkeleton){
			throw new Exception('Skeleton file : '.$strFile.' does not return any skeleton object !');
		}

		// done
		return $oSkeleton;
	}
	
	// loads skeleton datas from an ini file
	protected static function _loadFromIniFile($strFile = false){
		
		// checking file
		if(!is_file($strFile)){
			throw new Exception('File not found : '.$strFile);
		}
		
		// getting ini file
		$oIni = new IniHandler($strFile);
		// do we have the Card primary entry
		if(!$oIni->hasPrimary('Card')){
			// no
			throw new Exception('The file does not contains the Card entry : '.$strFile);
		}
		
		// getting cards
		return new self($oIni->toArray('Card'));
	}

	// build a card skeleton
	public function __construct($arrParams =  array()){

		// adding values
		foreach($arrParams as $strKey => $mValue){
			$this->setValue($strKey, $mValue);
		}

		return $this;
	}

	// lock the skeleton
	public function setLocked(){
		$this->_intIsLocked = true;
		return $this;
	}

	// returns true if object is locked
	public function isLocked(){
		return $this->_intIsLocked;
	}

	// format a media
	public function addMedia($strKey, $strType, $mParams = false){
		
		// is media allowed
		if(!in_array($strKey, array(CardGenerator::QUESTIONINFO, CardGenerator::ANSWERINFO))){
			// no
			throw new Exception('Medias are not allowed in '.$strKey);
		}
		
		// do we have a simple text
		if($strType instanceof CardText){
			// yes. Setting it as a text
			return $this->addMedia($strKey, 'text', $strType);
		}
		
		// do we have params
		if($mParams === false){
			// do we have a simple text key
			if(is_string($strType) || is_numeric($strType)){
				// yes
				return $this->addMedia($strKey, 'text', $strType);
			}
			
			// do we have an array
			if(!is_array($strType)){
				// no
				throw new Exception('Array or string expeced !');
			}
			
			// yes
			
			// extracting params
			$mParams = $strType;
			
			// do we have the type set into the param array		
			if(!isset($mParams['type']) || empty($mParams['type'])){
				// no
				throw new Exception('Missing media type !');
			}
			
			// yes
			// extracting type
			$strType = $mParams['type'];
			// removing type key
			unset($mParams['type']);
		}
		
		// do we have a valid type
		if(!is_string($strType) || empty($strType)){
			throw new Exception('Invalid media type ! String Expected');
		}
				
		// formatting media array
		$arrMedia = array(
			'type' => $strType,
			'params' => $mParams
		);
				
		// do we already have a value for the given key
		if(!$this->hasValue($strKey)){
			// no
			$this->_set($strKey, array($arrMedia));
			// done
			return $this;
		}
		
		// yes so we have to change the value and may be it contents
		$arrValue = $this->getValue($strKey);
		
		// do we have an array
		if(!is_array($arrValue)){
			// no
			// removing content
			$this->setValue($strKey, false);
			// updating content type
			$this->addMedia($strKey, $arrValue);
			// getting formated content
			$arrValue = $this->getValue($strKey);
		}
		
		// inserting media
		$arrValue[] = array($arrMedia);
		
		// done
		return $this;
	}

	// shortcut to set a value
	public function __set($strKey, $mValue){
		$this->setValue($strKey, $mValue);
	}
	
	// add a value to the card
	public function setValue($strKey, $mValue, $strIdentKey = false){

		// is object locked
		if($this->_intIsLocked && !$strIdentKey){
			return $this;
		}

		// could we modify current object
		if(!$this->_intIsLocked){
			// yes
			// do we have an ident key
			if(is_string($strIdentKey)){
				throw new Exception('object is not locked.');
			}
			
			if(!$this->hasValue($strKey)){
				throw new Exception('invalid card key : '.$strKey);
			}

			// adding value
			$this->_arrCard[$strKey] = $mValue;
			// done
			return $this;
		}

		if(!$this->hasValue($strKey, $strIdentKey)){
			throw new Exception('invalid card key : '.$strKey);
		}
		
		$this->_arrCardRendered[$strIdentKey][$strKey] = $mValue;

		return $this;
	}

	// returns true if skeleton has value $strKey
	public function hasValue($strKey, $strIdentKey = false){

		if(!$this->_intIsLocked){
			return array_key_exists($strKey, $this->_arrCard);
		}

		if(!isset($this->_arrCardRendered[$strIdentKey])){
			throw new Exception('invalid rendering key given');
		}

		return array_key_exists($strKey, $this->_arrCardRendered[$strIdentKey]);
	}

	// returns a property value
	public function getValue($strName, $strIdentKey = false){

		// is object locked
		if($this->_intIsLocked && !$strIdentKey){
			throw new Exception('object is locked. Missing identKey to get the property');
		}

		// is current object locked
		if(!$this->_intIsLocked){
			// no
					
			if(!array_key_exists($strName, $this->_arrCard)){
				throw new Exception('invalid card property : '.$strName);
			}

			// adding value
			return $this->_arrCard[$strName];
		}

		// object is locked
		if(!isset($this->_arrCardRendered[$strIdentKey])){
			throw new Exception('invalid rendering key given');
		}

		if(!array_key_exists($strName, $this->_arrCardRendered[$strIdentKey])){
			throw new Exception('invalid card property : '.$strName);
		}
		
		return $this->_arrCardRendered[$strIdentKey][$strName];
	}

	// set a var value over all card skeleton data
	// $mValue is expected to be s string or a number. All other value will be skipped. 
	// $mValue does not support CardText
	protected function _setVar($strVarName, $mValue, $arrCard = false){

		// do we have an usable value
		if(!is_string($mValue) && !is_numeric($mValue)){
			// no. Skipping data
			return $this;
		}

		// flag to identifiate the root call of this method
		$intIsRootCall = false;
		// default key value
		$strKey        = false;

		// do we have a key instead of a card
		if(is_string($arrCard) && isset($this->_arrCardRendered[$arrCard])){
			// yes
			// setting key
			$strKey = $arrCard;
			// setting flag
			$intIsRootCall = true;
			// extracring card
			$arrCard = $this->_arrCardRendered[$strKey];
		}

		if(!is_array($arrCard)){
			// no card found
			throw new Exception('arrCard is expected to be an array');
		}

		foreach($arrCard as $mEntryName => $mEntry){
			// do we have to go inside an array
			if(is_array($mEntry)){
				// yes
				$arrCard[$mEntryName] = $this->_setVar($strVarName, $mValue, $mEntry);
				continue;
			}

			// do we have a cardText
			if(!$mEntry instanceof CardText){
				// no
				// do we have a string for both entry and value
				if(!is_string($mEntry) && !is_numeric($mValue)){
					// no
					// nothing can be changed
					continue;
				}
				
				// applying var change on entry
				$arrCard[$mEntryName] = str_replace('%'.$strVarName.'%', $mValue, $mEntry);
				// done
				continue;
			}

			// yes we have a card text
			// getting a working copy
			$oCardText = clone $mEntry;
			// adding filter
			$oCardText->addPostFilter('function($strData){ return str_replace(\'%'.$strVarName.'%\', \''.$mValue.'\', $strData); };');
			// updating cardText
			$arrCard[$mEntryName] = $oCardText;
		}

		// do we have to return the card
		if(!$intIsRootCall){
			// yes
			return $arrCard;
		}

		// no
		// updating card
		$this->_arrCardRendered[$strKey] = $arrCard;
		// done
		return $this;
	}

	// init rendering
	protected function _initRendering($strKey){

		// is rendering initiated
		if(isset($this->_arrCardRendered[$strKey]) && is_array($this->_arrCardRendered[$strKey])){
			return $this;
		}

		// init rendering
		$this->_arrCardRendered[$strKey] = $this->_arrCard;
		// done
		return $this;
	}

	// prepare the object for rendering
	// and returns a content identification key
	public function prepareRendering($mBaseScale = false){

		// ensure object to be locked
		if(!$this->_intIsLocked){
			$this->setLocked();
		}

		// setting key
		$strKey = md5(microtime().mt_rand());
		// initialize rendering
		$this->_initRendering($strKey);

		// do we have to set the basescale
		if($mBaseScale instanceof SvgAdapt || (is_string($mBaseScale) && !empty($mBaseScale))){
			// yes
			$this->setBaseScaleSvg($mBaseScale, $strKey);
		}
		
		// return the key
		return $strKey;
	}

	// sets the basescale that could be use as scale reference
	public function setBaseScaleSvg($mBaseScale, $strKey){

		// checking if object is locked
		if(!$this->_intIsLocked){
			throw new Exception('object is not locked.');
		}

		// checking key
		if(!is_string($strKey) || empty($strKey) || !isset($this->_arrCardRendered[$strKey])){
			throw new Exception('invalid rendering key given');
		}

		// object is locked

		// do we have an object
		if($mBaseScale instanceof SvgAdapt){
			// yes
			$this->_arrBaseScaleSvg[$strKey] = $mBaseScale;
			// done
			return $this;
		}

		if(!is_string($mBaseScale)){
			throw new Exception('string expected for basescale');
		}

		// getting path
		$mBaseScale = Bootstrap::getPath($mBaseScale);

		// setting object
		$this->_arrBaseScaleSvg[$strKey] = new SvgAdapt($mBaseScale);
		// done
		return $this;
	}

	// return current base scale image
	public function getBaseScaleSvg($strKey = false){

		// checking key
		if(!is_string($strKey) || empty($strKey) || !isset($this->_arrCardRendered[$strKey])){
			throw new Exception('invalid rendering key given');
		}

		if(isset($this->_arrBaseScaleSvg[$strKey])){
			return $this->_arrBaseScaleSvg[$strKey];
		}

		return false;
	}

	// set entities that should be replaced before translation
	protected function _setCardTextEntities($mDatas, $arrVars = array()){
		
			// do we have an array
			if(is_array($mDatas)){
				// yes. Going inside to find all CardText objects
				foreach($mDatas as $mKey => $mData){
					$mDatas[$mKey] = $this->_setCardTextEntities($mData, $arrVars);
				}
				// done
				return $mDatas;
			}
		
			// do we have a cardText
			if(!$mDatas instanceof CardText){
				// non
				return $mDatas;
			}
			
			// getting a copy of the cardText
			$oCardText = clone $mDatas;
		
			// do we have a cardEnv object given a vars
			if($arrVars instanceof CardEnv){
				// yes
				// turning object to array
				$arrVars = $arrVars->toArray();
			}
		
			// adding vars
			$oCardText->addPreEntity($arrVars);
			
			// returning object
			return $oCardText;
	}

	// render the skeleton and returns the rendered key that can be used to get the template
	public function render($arrValues = false, $arrVars = false, $mBaseScale = false){

			// init required var
			$strKey = false;

			// do we have $arrValues given as a key
			if(is_string($arrValues) && isset($this->_arrCardRendered[$arrValues])){
				// yes. So rendering is already prepared;
				// extracting key
				$strKey = $arrValues;
				// ensure that $arrValues cannot be used
				$arrValues = false;
			}
			else{
				// object has to be prepared
				$strKey = $this->prepareRendering();
			}

			// do we have to set BaseScale
			if($mBaseScale){
				// yes
				$this->setBaseScaleSvg($mBaseScale, $strKey);
			}

			// do we have to add values
			if(is_array($arrValues)){
				// yes
				foreach($arrValues as $strValName => $mValue){
					$this->setValue($strValName, $mValue, $strKey);
				}
			}

			// do we have a cardEnv object given a vars
			if($arrVars instanceof CardEnv){
				// yes
				// turning object to array
				$arrVars = $arrVars->toArray();
			}

			// converting all CardText object into translated strings
			$this->_arrCardRendered[$strKey] = $this->_setCardTextEntities($this->_arrCardRendered[$strKey], $arrVars);
		
			// do we have vars
			if(is_array($arrVars) && !empty($arrVars)){
				// setting vars
				foreach($arrVars as $strVarName => $mValue){
					$this->_setVar($strVarName, $mValue, $strKey);
				}
			}

			return $strKey;
	}

	// returned an already rendered skeleton
	public function getRenderedTemplate($strKey){

		if(!is_string($strKey) || empty($strKey) || !isset($this->_arrCardRendered[$strKey])){
			throw new Exception('invalide template key given');
		}

		return $this->_arrCardRendered[$strKey];
	}

	// render the skeleton with the given parameters and returns an array
	public function toArray($arrValues = false, $arrVars = false, $mBaseScale = false){

		// render the skeleton with the given values
		$strKey = $this->render($arrValues, $arrVars, $mBaseScale);
		// return an array of the rendered skeleton
		return $this->getRenderedTemplate($strKey);
	}

	// insert a the skeleton as a template into the card generator
	// object $oGenerator - instance of CardGenerator or CardEnv if it contains a CardGenerator instance.
	//                      In the case of using a CardEnv as $oGenerator, the variables will be extracted too and
	//						set as array for $arrVars. When $arrVars is also set, data of both array will be merged
	//						considering the $arrVars param as the principal array, able to overrite data of CardEnv
	// array $arrValues     list values for the predefined key of the card template as required by the generator
	// array $arrVars		
	// mixed $mBaseScale
	public function insertAsTemplate($oGenerator, $arrValues = false, $arrVars = false, $mBaseScale = false){

			// CardEnv shorcut
			if($oGenerator instanceof CardEnv){
				// getting a copy of the object
				$oCardEnv = clone $oGenerator;
				
				// ensure $arrVars to be an array
				if(!is_array($arrVars)){
					$arrVars = array();
				}
				// merging variables
				$arrVars = array_merge($oCardEnv->toArray(), $arrVars);
				
				// extracting generator
				$oGenerator = $oCardEnv->{'Generator'};
			}

			// getting an array rendered version of the skeleton
			$arrCard = $this->toArray($arrValues, $arrVars, $mBaseScale);

            // adding template to the generator
			call_user_func_array(array($oGenerator, 'addCardTemplate'), array_values($arrCard));

			// done
			return $this;
	}
}
