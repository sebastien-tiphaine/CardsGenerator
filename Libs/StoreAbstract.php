<?php

require_once(__DIR__.'/BaseAbstract.php');

abstract class StoreAbstract extends BaseAbstract{

	// object store
	protected static $_arrObjectStore = array();

	// stores a skeleton object
	public static function storeObject($strId, $oObj){

		if(!is_string($strId) || empty($strId)){
			throw new Exception('invalid id given');
		}

		if(!$oObj instanceof self){
			throw new Exception('invalid object given');
		}

		// storing object
		self::$_arrObjectStore[$strId] = $oObj;
		// done
		return true;
	}

	// returns true if skeleton $strId exists
	public static function hasObject($strId){

		if(!is_string($strId) || empty($strId)){
			throw new Exception('invalid id given');
		}

		return isset(self::$_arrObjectStore[$strId]);
	}

	//returns object id
	public static function getObject($strId){

		if(!self::hasObject($strId)){
			throw new Exception('no object found for id '.$strId);
		}

		return self::$_arrObjectStore[$strId];
	}
}
