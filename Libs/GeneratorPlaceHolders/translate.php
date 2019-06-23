<?php

require_once(__DIR__.'/PlaceHolderAbstract.php');

class PlaceHolder_translate extends PlaceHolderAbstract{
	
	protected function _getDefaultParamsArray(){
		// no additional params required
		return array();
	}
	
	// translate a substring in a given context
	// {translate:context,text,domain}
	protected function _render($strContext = false, $strText = false, $strDomain = false){
		
		// do we have a context
		if(!is_string($strContext) || empty($strContext)){
			return 'translate__MISSING_CONTEXT__';
		}
		
		// do we have a text
		if(!is_string($strText) && !is_numeric($strText)){
			return 'translate__MISSING_TEXT__';
		}
		
		return Bootstrap::getInstance()->i18n()->_t($strText, $strDomain, $strContext);	
	}
}
