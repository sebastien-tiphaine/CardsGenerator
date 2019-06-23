<?php

require_once(__DIR__.'/IniHandler.php');

class GeneratorConfig extends IniHandler{

	// list of magic pathes
	protected $_arrMagicPath = array(
		// default values required by the system
		'source'    => '{base}/source',
		'cards'     => '{base}/Cards',
	);

	// load a config file from a config folder
	public function loadConfigFile($strFileName){

		// checking file name
		if(!is_string($strFileName)){
			throw new Exception(__CLASS__.'::_loadConfigFile : string expected');
		}

		if(!is_file($strFileName)){
			
			if(is_file(parent::getRootFolder().'/Config/'.$strFileName)){
				$strFileName = parent::getRootFolder().'/Config/'.$strFileName;
			}
			else if(is_file(parent::getBaseFolder().'/Config/'.$strFileName)){
				$strFileName = parent::getBaseFolder().'/Config/'.$strFileName;
			}
			else{
				throw new Exception(__CLASS__.'::_loadConfigFile : config file not found : '.$strFileName);
			}
		}
		
		// loading file using parent methode
		parent::loadConfigFile($strFileName);
		// loading magic pathes
		$this->_extractMagicPath();
		// done
		return $this;
	}

	// returns path of a framework folder
	public static function getFrameWorkFolder($strName = false){

		if(!is_string($strName) || empty($strName)){
			return self::getBaseFolder();
		}

		switch(strtolower(trim($strName))){
			case 'libs':
			case 'skeletons':
			case 'config':
				return parent::getBaseFolder().'/'.ucfirst($strName);
		}

		throw new Exception('Unknown framework folder : '.$strName);
	}

	// extract all magic pathes from the config file
	protected function _extractMagicPath(){
		
		// getting all magic pathes defined
		$arrPathes = $this->getVarsStartingWith('MagicPath');
		
		// do we have a magic pathes
		if(!is_array($arrPathes) || empty($arrPathes)){
			// no
			return $this;
		}
		
		// updating pathes
		$this->_arrMagicPath = array_merge($this->_arrMagicPath, $this->_pathFilter($arrPathes));
		// done
		return $this;
	}

	// apply a filter using all magic path on $strPath
	public function magicPath($strPath){
		
		foreach($this->_arrMagicPath as $strName => $strReplace){
			// applying replacement 
			$strPath = str_replace('{'.$strName.'}', $strReplace, $strPath);			
		}
		
		//done
		return $strPath;
	}

	// filter a string and replace all standard pathes
	protected function _pathFilter($strPath){
		
		// do we have an arra of pathes
		if(is_array($strPath)){
			// yes
			// extracting pathes
			$arrPath = $strPath;
			// filtering all datas
			foreach($arrPath as $mKey => $strPath){
				$arrPath[$mKey] = $this->_pathFilter($strPath);
			}
			// done
			return $arrPath;
		}
		
		// do we have something usable
		if(!is_string($strPath) || empty($strPath)){
			// no
			return $strPath;
		}
		
		// filtering
		$strPath = str_replace('{root}', parent::getRootFolder(), $strPath);
		$strPath = str_replace('{base}', parent::getBaseFolder(), $strPath);
		$strPath = str_replace('{libs}', self::getFrameWorkFolder('libs'), $strPath);
		//$strPath = str_replace('{cards}', self::getFrameWorkFolder('cards'), $strPath);
		$strPath = str_replace('{skeletons}', self::getFrameWorkFolder('skeletons'), $strPath);
		//$strPath = str_replace('{source}', self::getFrameWorkFolder('source'), $strPath);
		$strPath = str_replace('{config}', self::getFrameWorkFolder('config'), $strPath);
		//$strPath = str_replace('{cardstemplate}', self::getFrameWorkFolder('cardstemplate'), $strPath);
		// done
		return $strPath;
	}

	// return $strPath resolved if containing any magic or system path shortcut: ex : {root}
	public function getPath($strPath){
		// filtering path
		$strPath = $this->_varFilter($strPath);
		// do we still have a shortcut
		if(strpos($strPath, '{') !== false || strpos($strPath, '}') !== false){
			// yes
			throw new Exception('Path is containg an unresolved shortcut : '.$strPath); 
		}
		// done
		return $strPath;
	}
	
	// returns the value of a system or magic path
	public function getPathValue($strName){
	
		if(!$this->_isPathSortcut($strName)){
			throw new Exception('Unkown pathname : '.$strName);
		}
		// TODO : optimize
		return $this->getPath('{'.$strName.'}');
	}

	// filter content on setVar call
	protected function _varFilter($mValue){
		// filtering with standard vars
		$mValue = $this->_pathFilter($mValue);
		// filtering with magic path and standard path
		return $this->_pathFilter($this->magicPath($mValue));
	}
	
	// sets a variable
	public function setVar($strName, $mValue = NULL){
		
		if(is_array($strName)){
			$arrVars = $strName;
			foreach($arrVars as $strName => $mValue){
				$this->setVar($strName, $mValue);
			}
		}
		
		// is $strName a reserved word
		if($this->_isPathSortcut($strName)){
			// yes
			throw new Exception($strName.' is a reserved word');
		}
		// callinf parent
		return parent::setVar($strName, $mValue);
	}

	// magic setter
	public function __set($strName, $mValue){
		return $this->setVar($strName, $mValue);
	}

	// returns true of $strName is a shortcut path or a magic path
	protected function _isPathSortcut($strName){
		
		// defining system pathes
		$arrSystem = array(
			'root', 'base', 'libs', 'skeletons', 'config'
		);
		
		if(in_array(strtolower(trim($strName)), $arrSystem)){
			return true;
		}

		if(array_key_exists($strName, $this->_arrMagicPath)){
			return true;
		}
		
		return false;
	}

	// returns a var
	public function getVar($strName, $mDefault = null){

		// is var a shortcut path
		if($this->_isPathSortcut($strName)){
			// yes
			return $this->getPathValue($strName);
		}
		
		// calling parent methode
		return parent::getVar($strName, $mDefault);
	}

	// returns true if $strName is an existing var
	public function hasVar($strName, $intNoReserved = false){

		// do we have a string
		if(!is_string($strName) || empty($strName)){
			return false;
		}
		
		if(!$intNoReserved && $this->_isPathSortcut($strName)){
			return true;
		}

		// calling parent
		return parent::hasVar($strName);
	}
}
