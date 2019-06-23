<?php

class CardEnv{

	// list of vars
	protected $_arrVars = array();

	// generator object
	protected $_oGenerator = null;

	// constructor
	public function __construct($oGenerator, $arrVars = array()){

		if(!$oGenerator instanceof CardGenerator){
			throw new Exception(get_class($this).' : invalid generator object found');
		}

		// setting generator
		$this->_oGenerator = $oGenerator;

		// do we have some vars
		if(is_array($arrVars) && !empty($arrVars)){
			// yes
			$this->_setVar($arrVars);
		}

		// done
		return $this;
	}

	// sets one or more vars to current object
	protected function _setVar($strName, $mValue = false){

		if(is_array($strName)){
			// extracting vars
			$arrVars = $strName;

			foreach($arrVars as $strName => $mValue){
				$this->_setVar($strName, $mValue);
			}

			// done
			return $this;
		}

		if(!is_string($strName) || empty($strName)){
			throw new Exception('invalid var name. String Expected !');
		}

		// setting var
		$this->_arrVars[$strName] = $mValue;

		// done
		return $this;
	}

	// returns true if current object has a var $strName
	public function hasVar($strName){

		if(!is_string($strName) || empty($strName)){
			throw new Exception('invalid var name. String Expected !');
		}

		return array_key_exists($strName, $this->_arrVars);
	}

	// returns a var if exists or default value
	public function getVar($strName, $mDefault = false){

		if(!$this->hasVar($strName)){
			return $mDefault;
		}

		return $this->_arrVars[$strName];
	}

	// magic getter
	public function __get($strName){

		// do we have a shortcut call
		if(strtolower($strName) == 'generator'){
			// yes
			return $this->_oGenerator;
		}

		if(!$this->hasVar($strName)){
			throw new Exception('No var named : '.$strName);
		}

		return $this->_arrVars[$strName];
	}
	
	// returns all vars of the CardEnv Object as an array
	public function toArray(){
		return $this->_arrVars;
	}

}
