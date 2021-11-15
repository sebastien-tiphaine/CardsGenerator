<?php

require_once(__DIR__.'/../MusicLogic.php');

abstract class PlaceHolderAbstract extends MusicLogic{
	
	// prefix of the class
	const CLASSPREFIX = 'PlaceHolder_';
	
	// list of params
	protected $_arrParams = array();

	// cache for the render fonction
	protected $_strRenderMethod = false;

	// list of checked placeholders
	protected static $_arrPlaceHolderExists = array();

	// returns true if $strName is an existing place holder
	public static function PlaceHolderExists($strName){
		
		// do we have a valid name
		if(!is_string($strName) || empty($strName)){
			throw new Exception('Invalid name given. String expected !');
		}
		
		// do we have the value cached
		if(array_key_exists($strName, self::$_arrPlaceHolderExists)){
			// yes
			return self::$_arrPlaceHolderExists[$strName];
		}
		
		// no
		// is the class already loaded or does the file exists
		if(class_exists(self::CLASSPREFIX.$strName) || is_file(self::_getFileName($strName))){
			// yes
			// caching result
			self::$_arrPlaceHolderExists[$strName] = true;
			// done
			return true;
		}
		
		// no file and no class
		// caching result
		self::$_arrPlaceHolderExists[$strName] = false;
		// done
		return false;
	}

	// Returns file name of the place holder strName
	// even if it does not exists
	protected static function _getFileName($strName){
		
		// do we have a valid name
		if(!is_string($strName) || empty($strName)){
			throw new Exception('Invalid name given. String expected !');
		}
		// setting path
		return __DIR__.'/'.$strName.'.php';
	}
	
	// Loads a place holder object and apply the rendering 
	public static function RenderPlaceHolder($strName, $strNote, $intScaleType, $arrArgs = array(), $arrParams = array()){
		
		// do we have a valid name
		if(!is_string($strName) || empty($strName)){
			throw new Exception('Invalid name given. String expected !');
		}
		
		// do we have a valid note
		if(!is_string($strNote) || empty($strNote)){
			throw new Exception('Invalid note given. String expected !');
		}
		
		// does the place holder exists
		if(!self::PlaceHolderExists($strName)){
			// no
			throw new Exception('{'.$strName.'} : is not a place holder !');
		}
		
		// setting class name
		$strClass = self::CLASSPREFIX.$strName;
		
		// is the class loaded
		if(!class_exists($strClass)){
		
			// loading class
			require_once(self::_getFileName($strName));
			// checking class
			if(!class_exists($strClass)){
				throw new Exception('Loaded file does not contains the required class : '.$strClass);
			}
		}
		
		// ok the class exists
		$oPlaceHolder = new $strClass($arrParams);
		// setting required params
		$oPlaceHolder->setParam('Note', $strNote);
		$oPlaceHolder->setParam('ScaleType', $intScaleType);
		
		// do we have the right class
		if(!$oPlaceHolder instanceof PlaceHolderAbstract){
			// no
			throw new Exception('Loaded class does not extends : PlaceHolderAbstract');
		}
		
		// rendering datas
		return $oPlaceHolder->render($arrArgs);
	}

	// constructor
	public function __construct($arrParams = array()){
		
		// setting defaults params
		$this->_arrParams = array_merge(
			array(
				'Note'      => false,
				'ScaleType' => false
			),
			$this->_getDefaultParamsArray()
		);
		
		// do we have to set params
		if(is_array($arrParams) && !empty($arrParams)){
			// yes
			$this->setParam($arrParams);
		}
		// done
		return $this;
	}

	// a method that should return default params
	abstract protected function _getDefaultParamsArray();

	// sets a param
	public function setParam($strName, $mValue = false){
		
		// do we have many params at once
		if(is_array($strName)){
			// yes
			// extracting params
			$arrParams = $strName;
			
			foreach($arrParams as $strName => $mValue){
				$this->setParam($strName, $mValue);
			}
			
			// done
			return $this;
		}
		
		// can we set the param
		if(!is_string($strName) || !array_key_exists($strName, $this->_arrParams)){
			// no
			throw new Exception('Invalid param set : '.$strName.'. Param is not allowed or given name is not a string');
		}
		
		// setting value
		$this->_arrParams[$strName] = $mValue;
		
		// done
		return $this;
	}

	// returns the note params
	protected function _getNote(){
	
		// getting param value
		$strNote = $this->_getParam('Note');
		// do we have a valid note
		if(!is_string($strNote) || empty($strNote)){
			// no
			throw new Exception('The Note param is not set. This is a mandatory param');
		}
		
		return $strNote;
	}

	// returns the ScaleType params
	protected function _getScaleType(){
	
		// getting param value
		$mScaleType = $this->_getParam('ScaleType');
		// do we have a valid scale type
		if((!is_string($mScaleType) && !is_numeric($mScaleType)) || empty($mScaleType)){
			// no
			throw new Exception('The ScaleType param is not set. This is a mandatory param');
		}
		// returns the filtered value
		return $this->_filterScaleType($mScaleType);
	}

	// returns param value for $strName
	protected function _getParam($strName){
		
		// checking param
		if(!is_string($strName) || empty($strName) || !array_key_exists($strName, $this->_arrParams)){
			throw new Exception('Invalid param given : '.$strName.'. Param is not allowed or given name is not a string');
		}
		
		return $this->_arrParams[$strName];
	}

	// render the placeHolder
	public function render($arrArgs = array()){
		// calling user method
		return call_user_func_array(array($this, '_render'), array_values($arrArgs));
	}
}
