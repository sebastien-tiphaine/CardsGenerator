<?php

class IniHandler{

	// list of vars
	protected $_arrVars = array();

	// list of primary keys
	protected $_arrPrimaries = array();

	// root folder in which base folder can be found
	protected static $_strRootFolder = null;

	// is current file locked
	protected $_intLocked = false;

	// cache of the toArray function
	protected $_arrToArrayCache = array();

	// conctrutor
	public function __construct($mConfig = null, $intLock = false){

		// do we have an array of vars
		if(is_array($mConfig)){
			$this->setVar($mConfig);
		}

		// do we have an ini file
		if(is_string($mConfig)){
			$this->loadConfigFile($mConfig);
		}

		// ensures root folder to be set
		self::getRootFolder();

		// do we have to lock the file
		if($intLock){
			// yes
			$this->_intLocked = true;
		}

		// done
		return $this;
	}

	// load a config file
	public function loadConfigFile($strFileName){

		// checking file name
		if(!is_string($strFileName)){
			throw new Exception(__CLASS__.'::_loadConfigFile : string expected');
		}

		// do we have to prefix the file with the root folder
		if(!is_file($strFileName)){
			$strFileName = self::getBaseFolder().'/'.$strFileName;
		}
		
		if(!is_file($strFileName)){
			throw new Exception(__CLASS__.'::_loadConfigFile : config file not found : '.$strFileName);
		}
	
		// loading vars
		$arrVars = parse_ini_file($strFileName, false, INI_SCANNER_TYPED);
		// do we have datas
		if($arrVars === false){
			// no, it seems that the ini file has some syntax error
			throw new Exception(__CLASS__.'::_loadConfigFile : Not able to load config file content : '.$strFileName.'. The file may contains some syntax errors.');
		}
		
		// setting vars
		$this->setVar($arrVars);

		// cleaning the cache
		$this->_arrToArrayCache = array();

		// done
		return $this;
	}

	// returns the $strPath with a slash at the end
	protected static function _rmEndSlash($strPath){

		// do we have an end slash
		if(strrpos($strPath, '/') == strlen($strPath) -1){
			return substr($strPath, 0, strlen($strPath) -1);
		}

		return $strPath;
	}

	// return current root folder
	public static function getRootFolder(){

		if(!is_null(self::$_strRootFolder)){
			return self::$_strRootFolder;
		}

		// getting base folder pos
		$intBasePos = strpos(__DIR__,'CardsGenerator');

		// checking pos
		if($intBasePos === false){
			throw new Exception(__CLASS__.'::getRootFolder : CardsGenerator cannot be found in current path');
		}

		// extraction root Folder
		$strRoot = substr(__DIR__, 0, $intBasePos);

		// setting value
		self::$_strRootFolder = self::_rmEndSlash($strRoot);

		// done
		return self::$_strRootFolder;
	}

	// returns base folder
	public static function getBaseFolder(){
		return self::getRootFolder().'/CardsGenerator';
	}

	// sets a var
	public function setVar($strName, $mValue = null){

		// is config locked
		if($this->_intLocked){
			// yes. Nothing can be set
			return $this;
		}

		if(is_array($strName)){
			// extracting vars
			$arrVars = $strName;
			foreach($arrVars as $strName => $mValue){
				$this->setVar($strName, $mValue);
			}
			// done
			return $this;
		}

		// do we have to filter the content
		if(is_string($mValue)){
			// yes
			// do we have a filtering method
			if(method_exists($this, '_varFilter')){
				// yes
				// filtering content
				$mValue = $this->_varFilter($mValue);
			}
		}

		// can we extract the primary
		// first dot pos
		$intPos = strpos($strName, '.');
		// defining default primary name
		$strPrimary = $strName; 
		
		// do we have to extract the primary
		if($intPos !== false){
			// yes
			$strPrimary = substr($strName, 0, $intPos);
		}
		
		// adding primary
		if(!in_array($strPrimary, $this->_arrPrimaries)){
			$this->_arrPrimaries[] = $strPrimary; 
		}

		// setting value
		$this->_arrVars[$strName] = $mValue;

		// done
		return $this;
	}

	// magic setter
	public function __set($strName, $mValue){
		return $this->setVar($strName, $mValue);
	}

	// aggregate content of other config key
	protected function _filterAggregate($mValue){

		// do we have an array
		if(is_array($mValue)){
			// yes
			// init result
			$arrResult = array();

			foreach($mValue as $mkey => $mSubVal){
				$arrResult[$mkey] = $this->_filterAggregate($mSubVal);
			}
			// done
			return $arrResult;
		}

		if(!is_string($mValue)){
			return $mValue;
		}

		// do we have to aggregate another var by ref
		if(strpos($mValue, '::') === 0){
			// extracting subname
			$strSubName = substr($mValue,2);
		
			if(isset($this->_arrVars[$strSubName])){
				return $this->_filterAggregate($this->_arrVars[$strSubName]);
			}
		}

		// do we have to aggregate by startingWith Name
		if(strpos($mValue, '^') === 0){
			// yes
			return $this->_filterAggregate($this->getVarsStartingWith(substr($mValue,1)));
		}

		// do we have a php array
		if(strpos($mValue, 'array(') === 0){
			// yes
			return $this->_filterAggregate(eval('return '.$mValue.';'));
		}
		
		// do we have a cardText
		if(strpos($mValue, 'CardText(') === 0){
			// yes
			// loading required lib
			require_once(__DIR__.'/CardText.php');
			// getting cardText object
			return $this->_filterAggregate(eval('return new '.$mValue.';'));
		}
		
		// no aggregation found
		return $mValue;
	}

	// returns a var
	public function getVar($strName, $mDefault = null){

		if(!$this->hasVar($strName)){
			return $mDefault;
		}

		return $this->_filterAggregate($this->_arrVars[$strName]);
	}

	// returns all primary keys
	public function getPrimaries(){
		return $this->_arrPrimaries;
	}

	// returns true if a primary exists
	public function hasPrimary($strName){
		
		if(!is_string($strName) || empty($strName)){
			return false;
		}
		
		return in_array($strName, $this->_arrPrimaries);
	}

	// returns all vars starting with : $strPrefix
	// prefix will be removed from result array
	public function getVarsStartingWith($strPrefix){

		if(!is_string($strPrefix) || empty($strPrefix)){
			throw new Exception('Invalid prefix given ! String expected');
		}

		// setting result array
		$arrResult = array();

		foreach($this->_arrVars as $strName => $mValue){
			// do we have a matching var
			if(strpos($strName, $strPrefix) !== 0){
				// no
				continue;
			}
			// yes
			// removing prefix
			$strVName = substr($strName, strlen($strPrefix));

			// do we have a dot as fisrt char ?
			if(strpos($strVName, '.') === 0){
				// yes
				// we need to remove it
				$strVName = substr($strVName, 1);
			}
			
			// extrating result
			$arrResult[$strVName] = $this->_filterAggregate($mValue);
		}

		return $arrResult;
	}

	// magic getter
	public function __get($strName){

		if(!is_string($strName) || empty($strName)){
			throw new Exception('Invalid variable name given ! String expected');
		}

		// do we have a prefix given
		if(strpos($strName, '^') === 0){
			// yes
			// getting param list
			return $this->getVarsStartingWith(substr($strName,1));
		}

		// checking if var exists
		if(!$this->hasVar($strName)){
			throw new Exception('No variable '.$strName.' found in loaded config !');
		}
		
		return $this->getVar($strName);
	}

	// returns true if $strName is an existing var
	public function hasVar($strName){

		// do we have a string
		if(!is_string($strName) || empty($strName)){
			return false;
		}

		return array_key_exists($strName, $this->_arrVars);
	}
	
	// turns the ini content into an array
	// if $arrVars is an array in the ini type format, it will be parsed and returned as an array
	// if $arrVars is a string and a primary key, the function will return all the subvalues of the primary as an array
	public function toArray($arrVars = null){
	
		// setting cache key
		$strCacheKey = md5(serialize(func_get_args())); 
		
		// do we have a cache
		if(isset($this->_arrToArrayCache[$strCacheKey])){
			// yes
			return $this->_arrToArrayCache[$strCacheKey];
		}
	
		// do we have to use the main array
		if(is_null($arrVars)){
			// yes
			$arrVars = $this->_arrVars;
		}
		
		if(is_string($arrVars)){
			// extracting var name
			$strVarName = $arrVars;
			
			// is string an existing var
			if($this->hasVar($strVarName)){
				// yes
				// setting cache
				$this->_arrToArrayCache[$strCacheKey] = $this->toArray($this->getVar($strVarName));
				// done
				return $this->_arrToArrayCache[$strCacheKey];
			}
			
			// is the given string a primary
			if($this->hasPrimary($strVarName)){
				// yes
				// setting cache
				$this->_arrToArrayCache[$strCacheKey] = $this->toArray($this->getVarsStartingWith($strVarName));
				// done
				return $this->_arrToArrayCache[$strCacheKey];
			}
			
			// could the given string be used as a query
			if(strpos($strVarName, '^') === 0){
				//yes
				// setting cache
				$this->_arrToArrayCache[$strCacheKey] = $this->toArray($this->{$strVarName});
				// done
				return $this->_arrToArrayCache[$strCacheKey];
			}
			
			// can we explode the name and build a query
			if(strpos($strVarName, '.') !== false){
				// yes
				// getting list of keys
				$arrKey = explode('.', $strVarName);
				// shortening the array key with removing the last item
				$strCurKey = array_pop($arrKey);
				// querying datas
				$arrResult = $this->getVarsStartingWith(implode('.', $arrKey));
				
				// do we have the default result
				if($arrResult !== array()){
					// no
					// turning result into a usable array
					$arrResult = $this->toArray($arrResult);
					// is the expected key in the result array
					if(isset($arrResult[$strCurKey])){
						// yes
						$this->_arrToArrayCache[$strCacheKey] = $arrResult[$strCurKey];
						// done
						return $this->_arrToArrayCache[$strCacheKey];
					}
					// the key does not exists
				}
			}
			
			// nothing can be done with the given string
			// using it as the result
			$this->_arrToArrayCache[$strCacheKey] = $strVarName;
			// done
			return $this->_arrToArrayCache[$strCacheKey];			
		}
		
		// do we have to return given param
		if(!is_array($arrVars)){
			// yes
			// setting cache
			$this->_arrToArrayCache[$strCacheKey] = $arrVars;
			// done
			return $this->_arrToArrayCache[$strCacheKey];
		}
	
		// setting default result
		$arrResult = array();
		
		foreach($arrVars as $strKey => $mContent){
			
			if(!is_string($strKey)){
				$arrResult[$strKey] = $this->toArray($this->_filterAggregate($mContent));
				continue;
			}
			
			// getting key
			$arrKey = explode('.', $strKey);
			$intNum = count($arrKey);
			
			$mRef = &$arrResult;
			
			foreach($arrKey as $intKey => $strSubKey){
				
				// is current key the last key
				$intLast = ($intKey == $intNum -1);
				
				if(!isset($mRef[$strSubKey])){
					if($intLast){
						// filtering content
						$mContent = $this->_filterAggregate($mContent);
						
						if(is_array($mContent)){
							$mContent = $this->toArray($mContent);
						}
						
						$mRef[$strSubKey] = $mContent;
					}
					else{
						$mRef[$strSubKey] = array();
					}
					 
				}
				
				// changing reference
				if(!$intLast) $mRef = &$mRef[$strSubKey];
			}				
		}
		
		// setting cache
		$this->_arrToArrayCache[$strCacheKey] = $arrResult;
		// done
		return $this->_arrToArrayCache[$strCacheKey];
	}
}
