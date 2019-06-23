<?php

require_once(__DIR__.'/MediaAbstract.php');
require_once(__DIR__.'/SvgAdapt.php');

class MediaImage extends MediaAbstract{

	// list of params
	protected $_arrParams  = array(
		'size' 		  => 130,
		'css'         => false,
		'copyright'   => false 
	);

	// list of method that have to be call lately when generating images
	protected $_arrImageAutoParamList = array();

	// if method has an auto param data are store for a late call
	// and the function will return true
	protected function _generateImageHasAutoParam($strKey, $strMethod, $arrParams){
		
		// do we have the auto value set as the first param
		if(!isset($arrParams[0]) || !is_string($arrParams[0]) || !preg_match('/(auto):([a-zA-Z0-9\-\s,]+)|(auto)/', $arrParams[0], $arrMatches)){
			// nop
			return false;
		}
		
		$this->_debugMessage('adding method to auto param list : '.$strMethod);
		
		// checking if key is set
		if(!isset($this->_arrImageAutoParamList[$strKey]) || !is_array($this->_arrImageAutoParamList[$strKey])){
			// nop
			$this->_arrImageAutoParamList[$strKey] = array();
		}
		
		// setting default params array
		$arrParam = array();
		
		// do we have parameters
		if(isset($arrMatches[2]) && !empty($arrMatches[2])){
			// yes
			$arrAutoParam = explode(',', $arrMatches[2]);
			
			if(isset($arrAutoParam[0]) && is_numeric($arrAutoParam[0])){
				$arrParam['deg'] = intval($arrAutoParam[0]);
			}
			
			if(isset($arrAutoParam[0]) && !is_numeric($arrAutoParam[0])){
				$arrParam['style'] = $arrAutoParam[0];
			}
			
			if(isset($arrAutoParam[1]) && !is_numeric($arrAutoParam[1])){
				$arrParam['style'] = $arrAutoParam[1];
			}			
		}
		
		// adding data to AutoParamList array
		$this->_arrImageAutoParamList[$strKey][] = array(
			'method' => $strMethod,
			'params' => $arrParam,
		);
					
		// done
		return true;
	}

	// apply auto param
	protected function _generateImageTriggerAutoParam($strKey, $oAdapt, $strNote, $intScaleType){
		
		// do we have awaiting method calls
		if(!isset($this->_arrImageAutoParamList[$strKey])){
			// nop
			return $this;
		}
		
		// do we have some last call methods
		foreach($this->_arrImageAutoParamList[$strKey] as $arrDatas){
			
			// extracting method name
			$strMethod = $arrDatas['method'];
			// extracting params
			$arrParams = $arrDatas['params'];
			// setting default degre
			$intDegre = false;
			// setting default real params
			$arrCallParams = array();
			
			// setting default calling note
			$strCallingNote = $strNote;
			// setting default calling scale type
			$intCallingScaleType   = $intScaleType;
			
			// setting default value
			$arrNoteOrder = null;
	
			// do we have a degre set
			if(isset($arrParams['deg']) && is_numeric($arrParams['deg'])){
				// yes
				$intDegre = intval($arrParams['deg']);
				// getting scale
				$arrScale = $this->getScaleOf($strNote, $intScaleType);
				// updating calling note
				$strCallingNote = $arrScale[$intDegre-1];
				// updating scale type
				$intCallingScaleType = $this->getScaleModeForDegre($intDegre, $intScaleType);
			}
			
			// finding which real method have to be called
			// NOTE : add a if for each method. Do not convert it to a swicth,
			//        or the continue instruction in the else will not be interpreted correctly
			if($strMethod == 'setNeckPosNum'){
				// setting default style ref
					$strFromExtStyle = false;
				
					if(isset($arrParams['style'])){
						// extracting style ref
						$strFromExtStyle = $arrParams['style'];
					}
				
					$arrCallParams = $this->defineNeckPosFromSvg($oAdapt, $strCallingNote, $intCallingScaleType, $strFromExtStyle);
			}
			else{
				// no method found to define real parameters
				// skipping method
				continue;
			}	
					
			// sending param value to the adapter
			call_user_func_array(array($oAdapt, $strMethod), $arrCallParams);
		}
		
		// removing list
		unset($this->_arrImageAutoParamList[$strKey]);
		
		return $this;
	}
	
	// generate an image from a svg source and return the generated filename
	protected function _generateImage($strNote, $intScaleType, $arrImage = array(), $arrParams = array()){

		// getting real params array
		$arrParams = array_merge($this->_arrParams, $arrParams);
		// setting image indentKey
		$strImgIdent = md5(serialize(func_get_args()).serialize($this->_arrParams));
		// setting default flag
		$strAutoParamKey = md5($strImgIdent.time());

		// checking image source
		if(!isset($arrImage['src']) || !is_string($arrImage['src']) || empty($arrImage['src'])){
			throw new Exception(__CLASS__.':: given image has no valid src param.');
		}

		// extracting source
		$strSource = Bootstrap::getPath($arrImage['src']);
		// removing entry from main array
		unset($arrImage['src']);

		// setting outputfile name
		$strPngFile = Bootstrap::getPath($this->_getOutputDir().'/'.$strImgIdent.'.png');

		// checking if file has already been generated
		if(file_exists($strPngFile)){
			// yes
			return $strPngFile;
		}
		
		// setting new svg adapter object
		$oAdapt = new SvgAdapt($strSource);
		
		// checking called methods
		foreach($arrImage as $strMethod => $mParam){
			
			// do we have to extract method name in case of multiple calls
			if(preg_match('/([a-zA-Z]+)[0-9]+/', $strMethod, $arrMatches)){
					// yes
					$strMethod = $arrMatches[1];
			}
			
			// checking method
			if(!method_exists($oAdapt, $strMethod)){
					continue;
			}
			
			// ensure params to be an array
			if(!is_array($mParam)) $mParam = array($mParam);
			
			// updating params
			foreach($mParam as $intParamKey => $strParamValue){
				if(is_numeric($strParamValue)) $mParam[$intParamKey] = intval($strParamValue);
			}
			
			// do we have a an auto value as param
			if($this->_generateImageHasAutoParam($strAutoParamKey, $strMethod, $mParam)){
				// yes
				// method has to be triggered later
				continue;
			}
			
			// sending param value to the adapter
			call_user_func_array(array($oAdapt, $strMethod), $mParam);
		}
		
		// calling methods that have been set with the auto param
		$this->_generateImageTriggerAutoParam($strAutoParamKey, $oAdapt, $strNote, $intScaleType);
		
		// do we have a style
		if(!isset($arrParams['css']) || empty($arrParams['css']) || !is_string($arrParams['css'])){
			// no
			throw new Exception(__CLASS__.':: Missing css style sheet.');
		}
		
		// inserting style
		$oAdapt->insertStyle(Bootstrap::getPath($arrParams['css']));
		// setting copyright
		if(isset($arrParams['copyright']) && !empty($arrParams['copyright'])){
			// checking value
			if(is_string($arrParams['copyright']) && !empty($arrParams['copyright'])){
				// setting value
				$oAdapt->setCopyright($arrParams['copyright']);
			}
		}

		// finalizing image
		$oAdapt->setBackground();
		$oAdapt->fixCirclesText();
		$oAdapt->fixCirclesText('neckInfoArea');

		// setting default size
		$intSize = 130;

		if(isset($arrParams['size']) && is_numeric($arrParams['size'])){
			$intSize = $arrParams['size'];
		}
		
		// generating image
		$oAdapt->convert($strPngFile, $intSize);
		
		// done
		return $strPngFile;
	}


	// apply rendering
	protected function _render($arrMedia, $arrParams, $strNote, $intScaleType){

		// generating image
		return $this->_generateImage($strNote, $intScaleType, $arrMedia, $arrParams);
	}
}
