<?php

require_once(__DIR__.'/PlaceHolderAbstract.php');

class PlaceHolder_ucfirst extends PlaceHolderAbstract{
	
	protected function _getDefaultParamsArray(){
		// no additional params required
		return array();
	}
	
	// set the first letter to uppercase
	protected function _render($strString){
		return ucfirst($strString);
	}
}
