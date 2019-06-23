<?php

require_once(__DIR__.'/MsStyleHandlerAbstract.php');

class MsCssAdapt extends MsStyleHandlerAbstract{
			
	// constructor. 
	// string $strSrc xml file name
	public function __construct($strSrc){
			
		// setting current file name
		$this->_setFileName($strSrc);
		// done
		return $this;
	}
	
	// setting xml shortcuts mapping
	protected $_arrXmlMapping = array(
		'Style'   => 'Style'
	);
}
