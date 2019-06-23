<?php

require_once(__DIR__.'/BaseAbstract.php');

class Renderer extends BaseAbstract{
	
	// default renderer params
	protected $_arrParams = array(
		'name' 			 => false,
		'elementsFolder' => 'Elements',
		'baseFolder'     => false
	);
	
	// folder in which templates can be found
	protected $_strRdrFolder = false;
	
	// constructor
	public function __construct($arrParams = array()){
		
		// do we have params to set
		if(!empty($arrParams)){
			// yes
			$this->setParam($arrParams);
		}
		
		// done
		return $this;
	}
	
	// simply returns the file path of the template for type $strType
	protected function _getTemplateFileName($strType){
		// do we have a valid type
		if(!is_string($strType) || empty($strType)){
			// no
			throw new Exception('Invalid type given. String expected');
		}
		
		// done
		return $this->_getTemplatesFolder().'/'.$this->_getCleanFileName($strType).'.php';
	}
	
	// returns true if the is a template for $strType
	public function hasTemplate($strType){
	
		// getting file name
		$strFile = $this->_getTemplateFileName($strType);
		// does the file exists
		return is_file($strFile);
	}
	
	// apply rendering for data $arrData using template named $strType
	// when no type is given, only the tree is rendered
	public function render($arrDatas, $strType = false){
		
		// setting default flags
		$intHasType  = false;
		$intFlatTree = true;
			
		// do we have a template for rendering datas
		if($strType && $this->hasTemplate($strType)){
			// yes. Updating flags
			$intHasType  = true;
			$intFlatTree = false;
		}
			
		// rendering data 
		$arrDatas = $this->_renderTree($arrDatas, $intFlatTree);
		
		// do we have to returned datas without rendering
		if(!$intHasType){
			// yes
			// ensure data to have at least a content entry
			if(!isset($arrDatas['content'])){
				$arrDatas['content'] = '';
			}
			// do we have to tranfert media to content
			if(isset($arrDatas['medias']) && is_string($arrDatas['medias']) && empty($arrDatas['content'])){
				$arrDatas['content'] = $arrDatas['medias'];
				$arrDatas['medias']  = '';
			}
			
			return $arrDatas;
		}
		
		// rendering the main template
		return $this->_renderTemplate($this->_getTemplateFileName($strType), array('content' => $arrDatas));
	}
	
	// render the content of the array $arrDatas
	protected function _renderTree($arrDatas, $intFlatTree = false){
		
		// do we have something to do
		if(!is_array($arrDatas)){
			// no
			return $arrDatas;
		}
		
		// setting default result
		$arrResult = array();
		
		foreach($arrDatas as $mKey => $arrData){
		
			// are data usable ?
			if(!is_array($arrData)){
				// no
				// addin data to the result array
				$arrResult[$mKey] = $arrData;
				continue;
			}
		
			// do we have a type
			if(!isset($arrData['type'])){
				// no
				// this should be a container that may not be rendered
				// going inside anyway
				$arrResult[$mKey] = $this->_renderTree($arrData, $intFlatTree);
				// do we have to flatten the result
				if($intFlatTree && is_array($arrResult[$mKey])){
					// yes
					$arrResult[$mKey] = current($arrResult[$mKey]);
				}
				// done
				continue;
			}
			
			// do we have container
			if(!isset($arrData['data'])){
				// no
				// rendering item
				$arrResult[$mKey] = $this->_renderTemplate($this->_getElementFileName($arrData['type']), array('content' => $arrData));
				// done
				continue;
			}
			
			// ok we have a container
			// getting the content rendered
			$arrData['data']  = $this->_renderTree($arrData['data'], $intFlatTree);
			
			// do we have a flat tree
			if($intFlatTree){
				// yes. Flattening data
				$arrResult[$mKey] = current($arrData['data']);
				// done
				continue;
			}
			
			// should we set the result to the result array directly
			if($arrData['type'] == 'zone'){
				// yes, zone have to be managed in the higher template level
				$arrResult[$mKey] = $arrData;
				// done
				continue;
			}
			
			// rendering the container
			$arrResult[$mKey] = $this->_renderTemplate($this->_getElementFileName($arrData['type']), array('content' => $arrData));
		}
		
		// done
		return $arrResult;
	}
	
	// returns the folder in which all templates can be found
	protected function _getTemplatesFolder(){
		
		// do we already have a folder set
		if($this->_strRdrFolder){
			// yes
			return $this->_strRdrFolder;
		}
		// no
		// getting base folder
		$strRdrFolder = Bootstrap::getPath($this->getParam('baseFolder'));
		// checking dir
		if(!is_dir($strRdrFolder)){
			throw new Exception('Renderer base folder does not exists : '.$strRdrFolder);
		}
		
		// getting template folder
		$strRdrFolder = $strRdrFolder.'/'.$this->getParam('name');
		// checking dir
		if(!is_dir($strRdrFolder)){
			throw new Exception('Renderer template folder does not exists : '.$strRdrFolder);
		}
		
		// caching value
		$this->_strRdrFolder = $strRdrFolder;
		// done
		return $strRdrFolder;
	}
	
	// returns the file name for element $strName
	protected function _getElementFileName($strName){
		
		// setting file name
		$strFile = $this->_getTemplatesFolder().'/'.$this->getParam('elementsFolder').'/'.$this->_getCleanFileName($strName).'.php';
		// do we have a valid file
		if(!is_file($strFile)){
			// no
			throw new Exception('No template found for element '.$strName.'. File does not exists : '.$strFile);
		}
		
		// done
		return $strFile;
	}
	
	// returns the value of a parameter
	public function getParam($strName){
		
		// do we have a valid key
		if(!is_string($strName) || !array_key_exists($strName, $this->_arrParams)){
			// no
			throw new Exception('unknown parameter : '.$strName);
		}
		
		// yes
		return $this->_arrParams[$strName];
	}
	
	// sets a param
	public function setParam($strName, $mValue = false){
		
		// do we have many params
		if(is_array($strName)){
			// yes
			// extracting params
			$arrParams = $strName;
			
			foreach($arrParams as $strName => $mValue){
				$this->setParam($strName, $mValue);
			}
			// done
			return $this;
		}
		
		// do we have a valid key
		if(!is_string($strName) || !array_key_exists($strName, $this->_arrParams)){
			// no
			throw new Exception('invalid parameter : '.$strName);
		}
		
		// setting value
		$this->_arrParams[$strName] = $mValue;
		// done
		return $this;
	}
	
}
