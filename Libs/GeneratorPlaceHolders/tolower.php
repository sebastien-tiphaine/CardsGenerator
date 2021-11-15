<?php

require_once(__DIR__.'/PlaceHolderAbstract.php');

class PlaceHolder_tolower extends PlaceHolderAbstract{
	
	protected function _getDefaultParamsArray(){
		// no additional params required
		return array();
	}
	
	// set the whole string to lowercase
	protected function _render($strString){
		return strtolower($strString);
	}
}
