<?php

require_once(__DIR__.'/GeneratorConfig.php');
require_once(__DIR__.'/i18nTranslations.php');

class Bootstrap{

	// bootstrap object instance
	protected static $_oInstance = null;

	// main config
	protected $_oConfig = null;

	// main translationg
	protected $_oTranslation = null;

	// constructor protected
	protected function __construct(){
		// framework init

		// loading main config file
		$this->_oConfig = new GeneratorConfig('main.ini');
	} 

	// returns bootstrap instance
	public static function getInstance(){

		if(!self::$_oInstance instanceof Bootstrap){
			self::$_oInstance = new Bootstrap();
		}

		return self::$_oInstance;
	}

	// returns configuration object
	public function getConfig(){
		return $this->_oConfig;
	}

	// magic getter for retreiving config vars
	public function __get($strName){
		// return config var
		return $this->_oConfig->$strName;
	}
	
	// return translation object
	public function i18n(){
		
		// id object built
		if(!$this->_oTranslation instanceof i18nTranslations){
			// no
			// setting instance
			$this->_oTranslation = new i18nTranslations();
		}
	
		// returns object
		return $this->_oTranslation;
	}
	
	// shortcut to translation method
	public function _t($mText, $strDomain){
		return $this->i18n()->_t($mText, $strDomain);
	}
	
	// short call to get path
	public static function getPath($strFile){
		return self::getInstance()->_getPath($strFile);
	}
	
	// adds the correct absolute path to a file
	protected function _getPath($strFile){
		
		// do we have a string
		if(!is_string($strFile) || empty($strFile)){
			throw new Exception('Invalid file name given. String expected ! Given : '.print_r($strFile, true));
		}
			
		// ensure path not to contains any shortcut
		$strFile = $this->getConfig()->getPath($strFile);
				
		// do we have an absolute path
		if(strpos($strFile, '/') === 0){
			// yes, so there is nothing more to do
			return $strFile;
		}
				
		// we have a relative path, so we will
		// consider that the file should be into the base folder
		if(!defined('__BASE__')){
			throw new Exception('__BASE__ is not defined');
		}
		
		// returning path
		return __BASE__.'/'.$strFile;
	}
	
}
