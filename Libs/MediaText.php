<?php

require_once(__DIR__.'/MediaAbstract.php');
require_once(__DIR__.'/SvgAdapt.php');

class MediaText extends MediaAbstract{

	// apply rendering
	protected function _render($arrMedia, $arrParams, $strNote, $intScaleType){

		if(!isset($arrMedia['text'])){
			throw new Exception('Media type Text has no text entry as value');
		}
		
		return $arrMedia['text'];
	}
}
