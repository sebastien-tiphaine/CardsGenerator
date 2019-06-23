<?php

require_once(__DIR__.'/CardEnv.php');
require_once(__DIR__.'/IniHandler.php');

class CardsStore{

	// static ref of self
	protected static $_oSelf = null;
	// stored cards
	protected $_arrCards = array();

	// protected construtor
	protected function __construct(){}

	// static access to main object
	protected static function _getSelf(){

		// is ref object set
		if(!is_object(self::$_oSelf)){
			// no
			// building object
			self::$_oSelf = new self();
		}
		// return object
		return self::$_oSelf;
	}

	// store a card that can be built on call using the function closure
	public static function storeCard($strId, $oFunction){

		// checking id
		if(!is_string($strId) || empty($strId)){
			throw new Exception('invalid id given ! String expected !');
		}

		// inserting function
		self::_getSelf()->_arrCards[$strId] = $oFunction;
		
		// done
		return true;
	}

	// invoke a card function in oder to build the card
	public static function invoke($strId, $oGenerator, $arrArgs = array()){

		// do we have to invoke many cards at once
		if(is_array($strId)){
			// yes
			// extracting ids
			$arrIds = $strId;
			
			foreach($arrIds as $strId){
				// inserting Generator
				// forwarding call to main object
				self::invoke($strId, $oGenerator, $arrArgs);
			}
			// done
			return true;
		}

		// Inserting params automatically ----
		
		// could we insert a cardId corresponding to loaded file
		if(!isset($arrArgs['CardId'])){
			// yes
			$arrArgs['CardId'] = $strId;	
		}

		// forwarding call to main object with env
		self::_getSelf()->__call($strId, array('oGenerator' => $oGenerator, 'arrArgs' => $arrArgs));
		// done
		return true;
	} 

	// resolve the path where the card file can be found
	// and return the potential file name without extension
	protected function _getCardFileName($strId){

		// checking id
		if(!is_string($strId) || empty($strId)){
			throw new Exception('invalid id given ! String expected !');
		}

		// getting default cards path
		$strCardsPath = Bootstrap::getInstance()->cards;

		// do we have a sub path
		if(Bootstrap::getInstance()->getConfig()->hasVar('Cards.path')){
			// yes
			$strCardsPath = $strCardsPath.'/'.Bootstrap::getInstance()->{'Cards.path'};
		}

		// is the build path a real dir
		if(!is_dir($strCardsPath)){
			throw new Exception('Card.path does not exists : '.$strCardsPath.' !');
		}

		// setting card filename without extension
		return $strCardsPath.'/'.$strId;
	}
	
	// simple replacing entity function
	protected function _replaceVar($arrData, $arrVars){
	
		foreach($arrData as $mKey => $mData){
			
			if(is_array($mData)){
				$arrData[$mKey] = $this->_replaceVar($mData, $arrVars);
				continue;
			}
			
			// do we have a string
			if(!is_string($mData)){
				// no
				continue;
			}
			
			// do we have an automatique copy set
			if(strtolower($mData) == 'auto' && is_string($mKey)){
				// yes
				// do we have a vars with the same name
				if(isset($arrVars[$mKey])){
					// yes
					$arrData[$mKey] = $arrVars[$mKey];
					continue;
				}
				
				throw new Exception('The auto value has no corresponding data : '.$mKey);
			}
			
			foreach($arrVars as $strName => $mValue){
				
				// do we have something replacable ?
				if(!is_string($mValue) && !is_numeric($mValue)){
					// no
					// do we have a single var equality ?
					if($mData == '%'.$strName.'%'){
						// yes, so we can just copy args datas
						$arrData[$mKey] = $mValue;
						// done
						continue;
					}
					// value is not usable
					continue;
				}
				
				$arrData[$mKey] = str_replace('%'.$strName.'%', $mValue, $arrData[$mKey]);
			}
		}
		
		return $arrData;
	}

	// load a card from an ini file
	protected function _loadFromIniFile($strFile){
		
		if(!is_file($strFile)){
			throw new Exception('File not found : '.$strFile);
		}
		
		// getting ini file
		$oIni = new IniHandler($strFile);
		
		// do we have a skeleton name given
		if(!$oIni->hasVar('Skeleton')){
			// no
			throw new Exception('The file does not contains the Skeleton entry : '.$strFile);
		}
		
		// do we have the Card primary entry
		if(!$oIni->hasPrimary('Card')){
			// no
			throw new Exception('The file does not contains the Card entry : '.$strFile);
		}
		
		// getting cards values
		$arrValues = $oIni->toArray('Card');
		
		// setting default vars
		$arrVars   = false;
		
		// do we have vars
		if($oIni->hasPrimary('Vars')){
			// yes
			$arrVars = $oIni->toArray('Vars');
		}
		
		// done
		return array(
			'skeleton' => $oIni->getVar('Skeleton', false),
			'values'   => $arrValues, 
			'vars'     => $arrVars
		);
	}
	
	// loads a card from a php file
	protected function _loadFromPhpFile($strFile){
		
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
			// extracting closure
			eval('$oClosure = function($oCardEnv){'."\n".$strLogic."\n".'};');
		}catch(Throwable $oException){
			throw new Exception("Syntax error with evaluting file : $strFile");
			exit;
		}

		// done
		return $oClosure;
	}

	// magic caller user to invoke stored closures on main object
	public function __call($strId, array $arrArgs){

		// checking id
		if(!is_string($strId) || empty($strId)){
			throw new Exception('invalid id given ! String expected !');
		}

		// is the card loaded
        if(!array_key_exists($strId, $this->_arrCards)){
			// getting file name
			$strFile = $this->_getCardFileName($strId);

			if(is_file($strFile.'.ini')){
				// setting card datas
				$this->_arrCards[$strId] = $this->_loadFromIniFile($strFile.'.ini', $arrArgs['arrArgs']);
			}
			else if(is_file($strFile.'.php')){
				// setting card datas
				$this->_arrCards[$strId] = $this->_loadFromPhpFile($strFile.'.php');
			}
			else{
				throw new Exception('No file found for card : '.$strId);
			}
        }
        
		// do we have a closure
        if($this->_arrCards[$strId] instanceof Closure){
			// yes
			// setting card Env
			$oCardEnv = new CardEnv($arrArgs['oGenerator'], $arrArgs['arrArgs']);
		
			 // adding new bind reference to ensure context to be right
			$this->_arrCards[$strId]->bindTo($this);
		
			// calling function
			call_user_func_array($this->_arrCards[$strId], array($oCardEnv));
			
			// done
			return $this;
		}
        
        // do we have enougt data to inject a card into the generator
        if(!is_array($this->_arrCards[$strId]) || 
           !isset($this->_arrCards[$strId]['skeleton']) ||
           !isset($this->_arrCards[$strId]['values'])){
			   // no
			   throw new Exception('Card has not enought datas : '.$strId.' ! Missing Skeleton path and/or card values.');
		}
		
		// extracting card datas
		$arrCard = $this->_arrCards[$strId];
		
		// do we have vars
		if($arrCard['vars'] === false){
			// we do not have vars. 
			// using the full arg array as vars
			$arrCard['vars'] = $arrArgs['arrArgs'];
		}
		else{
			// yes
			// is it an array
			if(is_array($arrCard['vars'])){
				// yes
				// replacing datas
				$arrCard['vars'] = $this->_replaceVar($arrCard['vars'], $arrArgs['arrArgs']);
			}
			else{
				// no. Ensure that we have an array
				$arrCard['vars'] = array();
			}
		}
		
		// updating values
		$arrCard['values'] = $this->_replaceVar($arrCard['values'], $arrArgs['arrArgs']);
				
		// inserting card into the generator
		CardSkeleton::getSkeleton($arrCard['skeleton'])->insertAsTemplate(
			$arrArgs['oGenerator'],
			$arrCard['values'],
			$arrCard['vars']	
		);

		// done
		return $this;
    }
}
