<?php

require_once(__DIR__.'/StoreAbstract.php');
require_once(__DIR__.'/CardGenerator.php');
require_once(__DIR__.'/Renderer.php');

abstract class OutputAbstract extends StoreAbstract{

	// root outputdir
	protected $_strRootDir = false;

	// outputdir relative to root 
	protected $_strOutputDir = false;

	// ref of the current generator
	protected $_oGenerator = null;

	// object params
	protected $_arrParams = array();

	// main constructor
	public function __construct($strOutputDir = false , $arrParams = array()){

		if(!$strOutputDir){
			$strOutputDir = get_class($this);
		}

		$this->setOutputDir($strOutputDir);
		$this->setParam($arrParams);

		$this->_init();

		return $this;
	}

	// sets project root dir
	public function setRootDir($strDir){

		if(!is_string($strDir) || !is_dir($strDir)){
			throw new Exception('invalid root dir set : '.$strDir);
		}

		// setting value
		$this->_strRootDir = Bootstrap::getPath($strDir);
		// done
		return $this;
	}

	// orders media
	protected function _orderMediasCmp($arrA, $arrB){
		
		// do we have enough data to compare
		if(!is_array($arrA) || !is_array($arrB)){
			   // no
			   return 0;
		}
		
		// do we miss order data for both
		if(!isset($arrA['order']) && !isset($arrB['order'])){
			// yes
			return 0;
		}
		
		// getting order values
		$intOrderA = (isset($arrA['order']) && is_numeric($arrA['order']))? intval($arrA['order']) : 1;
		$intOrderB = (isset($arrB['order']) && is_numeric($arrB['order']))? intval($arrB['order']) : 1;
		
		if($intOrderA == $intOrderB){
			return 0;
		}
		
		return ($intOrderA < $intOrderB)? -1 : 1;
	}

	// build the media tree for rendering
	protected function _getMediasTree($arrDatas){
	
		if(!is_array($arrDatas) || empty($arrDatas)){
			return $arrDatas;
		}
		
		// setting default result
		$arrResult = array(
			'medias' => array()
		);
		
		// list of datas to be sorted
		$arrToSortRef = array();
		
		foreach($arrDatas as $strKey => $arrMediaData){
			
			// do we have a media
			if(!is_array($arrMediaData) || !isset($arrMediaData['media'])){
				// no, so just getting a copy of the item
				$arrResult[$strKey] = $arrMediaData;
				continue;
			}
			
			// do we have a condition
			if(isset($arrMediaData['condition']) && !empty($arrMediaData['condition'])){
				
				// ensure condition to be an array
				if(!is_array($arrMediaData['condition'])){
					$arrMediaData['condition'] = array($arrMediaData['condition']);
				}
				
				// setting defaut result
				$intResult = false;
				
				// rolling over all conditions
				foreach($arrMediaData['condition'] as $strCondition){
					
					// do we have a valid condition
					if(!strpos($strCondition, 'if(') === 0){
						// no
						throw new Exception('invalid condition found : '.$strCondition);
					}
					
					// is the condition already satisfied
					if($intResult){
						// yes
						// ok so we do not need to check other conditions
						continue;
					}
					
					try{
						// evaluating condition
						eval($strCondition.'{ $intResult = true; };');
					}catch(Throwable $oException){
						throw new Exception("Syntax error with evaluting condition : $strCondition");
						exit;
					}
				}
				
				// do we have a negative result ?
				if(!$intResult){
					// yes.
					// item must be skipped
					continue;
				}
				
				// ok we can go on with this media
			}
			
			// setting the default ref
			$arrRef = &$arrResult['medias'];
			// setting sort index
			$strSortIndex = '';
			
			// do we have the zone 
			if(isset($arrMediaData['zone']) && $arrMediaData['zone']){
				// yes
				// extracting zone
				$intZone = $arrMediaData['zone'];
				// is the zone set on the result array
				if(!isset($arrRef[$intZone])){
					// no
					$arrRef[$intZone] = array(
						'type' => 'zone',
						'zone' => $intZone,
						'data' => array()
					);
				}
				
				// updating ref
				$arrRef = &$arrRef[$intZone]['data'];
				// updating sort index
				$strSortIndex.='_'.$intZone;
			}
			
			// do we have a group
			if(isset($arrMediaData['group']) && !empty($arrMediaData['group'])){
				// setting groups list
				$arrGroups = $arrMediaData['group'];
				
				//do we have a list of groups with only name given
				if(!is_array($arrGroups)){
					// yes
					$arrGroups = array($arrGroups);
				}
				else{
					// we have an array. Is it a list of groups or a single group with properties
					if(isset($arrGroups['name'])){
						// it should be a single group
						$arrGroups = array($arrGroups);
					}
				}
				
				foreach($arrGroups as $strGroupKey => $mGroup){
					
					// setting default group name
					$strGroup = $mGroup;
					// setting default group properties
					$arrProperties = array();
					
					// do we have a group name
					if(is_array($mGroup)){
						// no
						// do we have a valid group name
						if(!isset($mGroup['name']) || !is_string($mGroup['name']) || empty($mGroup['name'])){
							// no
							// can we use the group key
							if(!is_numeric($strGroupKey) && is_string($strGroupKey) && !empty($strGroupKey)){
								// adding group name
								$mGroup['name'] = $strGroupKey;
							}
							else{
								// no group name at all
								throw new Exception('Invalid group name found or missing property : name.');
							}
						}
						// extracting group name
						$strGroup = $mGroup['name'];
						// setting properties
						$arrProperties = $mGroup;
						// removing name from properties
						unset($arrProperties['name']);
					}
					
					// do we have the group set on the result array
					if(!isset($arrRef[$strGroup])){
						// no
						$arrRef[$strGroup] = array(
							'type' => 'group',
							'group' => $strGroup,
							'properties' => $arrProperties,
							'data' => array()
						);
					}
				
					// updating ref
					$arrRef = &$arrRef[$strGroup]['data'];
					// updating sort index
					$strSortIndex.='_'.$strGroup;
				}
			}
			
			// adding item 
			$arrRef[$strKey] = $arrMediaData;
			
			if(empty($strSortIndex)){
				$strSortIndex = 'main';
			}
			
			// is the sort index set
			if(!isset($arrToSortRef[$strSortIndex])){
				// no
				$arrToSortRef[$strSortIndex] = &$arrRef;
			}
		}
		
		// sorting data
		foreach($arrToSortRef as $strKey => $arrSortData){
			// sorting datas
			uasort($arrSortData, array($this, '_orderMediasCmp'));
			// updating ref
			$arrToSortRef[$strKey] = $arrSortData;
		}
		
		// done
		return $arrResult;
	}

	// render all card entries with a template if given
	protected function _renderCardEntries($arrCard, $strNote, $arrVars){
		
		// do we have params for the tree renderer
		if(!$this->hasParam('renderer')){
			// no
			throw new Exception('No params set for the renderer. Required entry : Renderer.');
		}

		// getting renderer object
		$oRenderer = new Renderer($this->getParam('renderer'));
				
		// rendering all cards entries
		foreach($arrCard as $strKey => $mValue){

			// filtering media
			$mValue = $this->_filterMedia($mValue);

			// ensure that given value is an array
			if(!is_array($mValue)){
				$mValue = array('content' => $mValue);
			}

			// including vars
			$arrTplDatas = array_merge($mValue, $arrVars, $this->getParam($strKey.'.template.vars', array()), array('strNote' => $strNote));
			// organize all media correctly
			$arrTplDatas = $this->_getMediasTree($arrTplDatas);
												
			// rendering datas
			$arrCard[$strKey] = $oRenderer->render($arrTplDatas, $strKey);
						
			// do we have a content that was not rendered (possible cases : no template or content has not to be renderer)
			if(isset($arrCard[$strKey]['content'])){
				// getting clean content
				$arrCard[$strKey] = $arrCard[$strKey]['content'];
			}
		}
		
		// done
		return $arrCard;
	}

	// filters all medias
	protected function _filterMedia($arrDatas){

		// checking if datas are usable
		if(!is_array($arrDatas) || empty($arrDatas)){
			// no
			return $arrDatas;
		}

		foreach($arrDatas as $strKey => $arrMedia){

			// is data usable
			if(!is_array($arrMedia)){
				// no
				throw new Exception('Found a media which is not an array : '.$strKey);
			}

			// is the type set
			if(!isset($arrMedia['type']) || !is_string($arrMedia['type']) || empty($arrMedia['type'])){
				throw new Exception('Found a media with no type set ['.$strKey.']: '.print_r($arrMedia, true));
			}
			
			// do we have a sub array of medias
			if(!isset($arrMedia['media'])){
				throw new Exception('Found a media with no media param set ['.$strKey.']: '.print_r($arrMedia, true));
			}

			// do we have params for this media
			if(!$this->hasParam('Media.'.$arrMedia['type'])){
				// no, so just calling user filter
				$arrDatas[$strKey] = $this->_formatMedia($arrMedia, $arrMedia['type']);
				// done
				continue;
			}

			// getting Media params
			$arrMediaParams = $this->getParam('Media.'.$arrMedia['type']);

			// case : media is a file and a dir is set
			if(isset($arrMediaParams['dir'])){

				// getting target dir
				$strTargetDir = $this->getOutputDir($arrMediaParams['dir']);
				// copying media
				$arrMedia['media'] = $this->_mediaCopy($strTargetDir, $arrMedia['media']);
				// formating media
				$arrDatas[$strKey] = $this->_formatMedia($arrMedia, $arrMedia['type']);
				// done
				continue;
			}

			// case : nothing to change, but just filtering
			$arrDatas[$strKey] = $this->_formatMedia($arrMedia, $arrMedia['type']);
		}

		return $arrDatas;
	}

	// copy a media in a local folder
	protected function _mediaCopy($strTargetDir, $mDataMedia){

		// do we have an array of media
		if(is_array($mDataMedia)){
			// yes
			// setting result
			$arrResult = array();
			// checking all sources
			foreach($mDataMedia as $mSubMedia){
				$arrResult[] = $this->_mediaCopy($strTargetDir, $mSubMedia);
			}
			// done
			return $arrResult;
		}

		// no, we should have a simple string
		// do we have a valid file and string
		if(!is_string($mDataMedia) || !is_file($mDataMedia)){
			// no
			return false;
		}

		$strTarget = Bootstrap::getPath($strTargetDir.'/'.$this->_getCleanFileName(basename($mDataMedia)));
		// copying file
		copy($mDataMedia, $strTarget);
		// updating media
		// removing root path to only have relative path
		$strTarget = str_replace($this->getOutputDir().'/', '', $strTarget);
		// done
		return $strTarget;
	}

	// format media for a local usage
	abstract protected function _formatMedia($arrMedia, $strDataType = false);

	// apply rendering
	public final function render($strGroup, $arrCard, $strNote, $strCardRdrId = false, $arrVars = array()){

		// checking generator
		$this->_validateGenerator($this->_oGenerator);
		// rendering each card entries
		$arrCard = $this->_renderCardEntries($arrCard, $strNote, $arrVars);
		// moving all media to a local dir
		// rendering
		$this->_render($strGroup, $arrCard, $strNote, $strCardRdrId, $arrVars);
		// done
		return $this;
	}

	// sets the output directory
	public function setOutputDir($strDir){

		if(!is_string($strDir) || empty($strDir)){
			throw new Exception('invalid dir given : string expected');
		}

		// setting value
		$this->_strOutputDir = $strDir;
		// done
		return $this;
	}

	// checks dirs. Throws an exception if everything is not ok
	protected function _validatesDirs(){

		// checking root dir
		if(!is_dir($this->_strRootDir)){
			throw new Exception('Root dir is not set');
		}

		if(!is_string($this->_strOutputDir) || empty($this->_strOutputDir)){
			throw new Exception('OutputDir is not set');
		}

		// setting realdir
		$strRelOutdir = $this->_strRootDir.'/'.$this->_strOutputDir;

		if(!is_dir($strRelOutdir)){
			mkdir($strRelOutdir, 0755, true);
		}

		// do we have subdirs
		$arrSubDirs = $this->_getSubDirs();

		if(!is_array($arrSubDirs) || empty($arrSubDirs)){
			return $this;
		}

		// do we have to build sub dirs
		foreach($arrSubDirs as $strSub){
			if(!is_dir($strRelOutdir.'/'.$strSub)){
				mkdir($strRelOutdir.'/'.$strSub, 0755, true);
			}
		}

		// done
		return $this;
	}

	// returns current outputdir
	public function getOutputDir($strSub = false){

		// checking dir
		$this->_validatesDirs();

		// setting realdir
		$strRelOutdir = $this->_strRootDir.'/'.$this->_strOutputDir;

		// do we have a sub given
		if($strSub){
			// yes
			$arrSubs = $this->_getSubDirs();

			if(!in_array($strSub, $arrSubs)){
				throw new Exception('unauthorized or invalid sub dir given : '.$strSub);
			}

			return $strRelOutdir.'/'.$strSub;
		}

		// done
		return $strRelOutdir;
	}

	// sets the generator
	public function setGenerator($oGenerator){

		// checking generator
		$this->_validateGenerator($oGenerator);
		// setting generator
		$this->_oGenerator = $oGenerator;
		// done
		return $this;
	}

	// return current generator
	protected function _getGenerator(){
		return $this->_oGenerator;
	}

	// sets param
	public function setParam($strName, $mValue = false){

		// do we have multiple params
		if(is_array($strName)){
			// yes
			$arrParams = $strName;
			
			foreach($arrParams as $strName => $mValue){
				$this->setParam($strName, $mValue);
			}

			return $this;
		}

		// checking name
		if(!is_string($strName) || empty($strName)){
			throw new Exception('invalid param name given : string expected');
		}

		// setting param
		$this->_arrParams[$strName] = $mValue;

		return $this;
	}

	// returns true if param $strName is set
	public function hasParam($strName){

		// checking name
		if(!is_string($strName) || empty($strName)){
			throw new Exception('invalid param name given : string expected');
		}

		return array_key_exists($strName, $this->_arrParams);
	}

	// returns the value of param $strName if exists of $mDefault
	public function getParam($strName, $mDefault = false){

		if(!$this->hasParam($strName)){
			return $mDefault;
		}

		return $this->_arrParams[$strName];
	}
	
	// returns subdirs
	abstract protected function _getSubDirs();

	// initialize object
	abstract protected function _init();

	// apply rendering
	abstract protected function _render($strGroup, $arrCard, $strNote, $arrCardIdent, $arrVars);

	// finalize rendering
	abstract public function finalize();
}
