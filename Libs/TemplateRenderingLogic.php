<?php

require_once(__DIR__.'/StoreAbstract.php');
require_once(__DIR__.'/CardGenerator.php');
require_once(__DIR__.'/CardSkeleton.php');
require_once(__DIR__.'/MultiSkeleton.php');

abstract class TemplateRenderingLogic extends StoreAbstract{

	abstract public function getTemplate($oGenerator, $strNote);

	// throws an exception if $oSkeleton is not a MultiSkeleton or CardSkeleton
	protected function _validateSkeleton($oSkeleton, $intNoMulti = false){

		if($intNoMulti){
			if($oSkeleton instanceof MultiSkeleton){
				throw new Exception(get_class($this).' : MultiSkeleton are not supported');
			}
			
			if(!$oSkeleton instanceof CardSkeleton){
				throw new Exception(get_class($this).' : invalid skeleton object found');
			}

			return $this;
		}

		if(!$oSkeleton instanceof MultiSkeleton && !$oSkeleton instanceof CardSkeleton){
			throw new Exception(get_class($this).' : invalid skeleton object found');
		}

		return $this;
	}
}
