<?php

abstract class FilterAbstract{
	
	// list of params
	protected $_arrParams = array();
	
	// filter builder
	public static function getFilter($mFilter = false){
		
		// do we have a filter object
		if($mFilter instanceof FilterAbstract){
			return $mFilter;
		}
		
		// do we have params
		if(!is_array($mFilter) || empty($mFilter)){
			// no
			throw new Exception('No parameters found to build the filter');
		}
		
		// do we have a classname property
		if(!isset($mFilter['classname']) || !is_string($mFilter['classname']) || empty($mFilter['classname'])){
			// no
			throw new Exception('Missing classname parameter');
		}
		
		// extracting class name
		$strClassName = $mFilter['classname'];
		// removing classname from param array
		unset($mFilter['classname']);
		
		// loading class
		require_once(__LIBS__.'/Filters/'.$strClassName.'.php');

		// getting filter object
		$oFilter = new $strClassName($mFilter);
		
		// do we have a filter object
		if(!$oFilter instanceof FilterAbstract){
			// no
			throw new Exception('Filter object is not an instance of FilterAbstract');
		}
		
		// returning filter object
		return $oFilter;
	}
	
	// constructor
	public function __construct($arrParams = array()){
		
		// do we have params
		if(is_array($arrParams) && !empty($arrParams)){
			// yes
			$this->setParam($arrParams);
		}
		
		// done
		return $this;
	}
	
	// abstract filtering method
	protected abstract function _filter($mValue);
	
	// filters $mValue
	public function filter($mValue){
	
		// do we have something to filter
		if(empty($mValue)){
			//no
			return $mValue;
		}
		
		// filtering content
		return $this->_filter($mValue);
	}
	
	// sets param
	public function setParam($strName, $mValue = false){

		// do we have multiple params
		if(is_array($strName)){
			// yes
			$arrParams = $strName;
			
			foreach($arrParams as $strName => $mValue){
				// setting param
				$this->setParam($strName, $mValue);
			}

			return $this;
		}

		// checking name
		if(!is_string($strName) || empty($strName)){
			throw new Exception('invalid param name given : string expected');
		}

		// setting param
		$this->_arrParams[$strName] = $mValue;

		return $this;
	}

	// returns true if param $strName is set
	protected function _hasParam($strName){

		// checking name
		if(!is_string($strName) || empty($strName)){
			throw new Exception('invalid param name given : string expected');
		}

		return array_key_exists($strName, $this->_arrParams);
	}

	// returns the value of param $strName if exists of $mDefault
	protected function _getParam($strName, $mDefault = false){

		if(!$this->_hasParam($strName)){
			return $mDefault;
		}

		// returning default param
		return $this->_arrParams[$strName];
	}
	
}
