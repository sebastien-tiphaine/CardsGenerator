<?php

require_once(__DIR__.'/MusicLogicExt.php');
require_once(__DIR__.'/CardText.php');
require_once(__DIR__.'/Filters/FilterAbstract.php');

abstract class MediaAbstract extends MusicLogicExt{

	// list of params
	protected $_arrParams = array();

	// list of filters
	protected $_arrFilters = array();

	// outputdir
	protected $_strOutputDir = false;

	// list of media already copied
	protected $_arrCopiedMedia = array();

	// main constructor
	public function __construct($arrParams = array()){

		$this->setParam($arrParams);
		return $this;
	}

	// add a filter
	public function addFilter($mFilter){
		
		// adding filter to the local list
		$this->_arrFilters[] = FilterAbstract::getFilter($mFilter);
		// done 
		return $this;
	}
	
	// filter $mValue using all filters set
	protected function _filterContent($mValue){
		
		// do we have filters
		if(!is_array($this->_arrFilters) || empty($this->_arrFilters)){
			// no
			// nothing to do
			return $mValue;
		}
		
		// filtering content
		foreach($this->_arrFilters as $oFilter){
			$mValue = $oFilter->filter($mValue);
		}
		
		// done
		return $mValue;
	}

	// sets the output directory
	public function setOutputDir($strDir){

		if(!is_string($strDir) || empty($strDir)){
			throw new Exception('invalid dir given : string expected');
		}

		// setting value
		$this->_strOutputDir = $strDir.'/'.get_class($this);
		// done
		return $this;
	}

	// checks dirs. Throws an exception if everything is not ok
	protected function _validatesDirs(){

		if(!is_dir($this->_strOutputDir)){
			mkdir($this->_strOutputDir, 0755, true);
		}

		// done
		return $this;
	}

	// returns current outputdir
	protected function _getOutputDir(){

		// checking dir
		$this->_validatesDirs();

		// done
		return $this->_strOutputDir;
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

	// apply a filter is a magic method is set to $mValue
	protected function _callMagicFilter($strName, $mValue = false){
		
		// do we have multiple params
		if(is_array($strName)){
			// yes
			$arrParams = $strName;
			
			foreach($arrParams as $strPName => $mPValue){
				// setting param
				$arrParams[$strPName] = $this->_callMagicFilter($strPName, $mPValue);
			}

			// done
			return $arrParams;
		}
		
		if(!is_string($strName) || empty($strName)){
			throw new Exception('invalid param name given : string expected');
		}
		
		// do we have a magic filter
		$strMagicFilter = '_filterSet'.ucFirst($strName);
		
		if(method_exists($this, $strMagicFilter)){
			// yes
			// filtering the value
			$mValue = $this->$strMagicFilter($mValue);
		}
		
		//returning the value
		return $mValue;
	}

	// sets param
	public function setParam($strName, $mValue = false){

		// do we have multiple params
		if(is_array($strName)){
			// yes
			$arrParams = $strName;
			// list of extracted filters params
			$arrFilters = array();
			
			foreach($arrParams as $strName => $mValue){
				
				if(strpos(strtolower($strName), 'filter.') === 0){
					// getting params
					$arrFilterParams = explode('.', $strName);
					// removing first entry
					array_shift($arrFilterParams);
					
					// setting default pointer
					$arrFiltersPointer = &$arrFilters;
					
					foreach($arrFilterParams as $strFilterPName){
						// do we have an entry
						if(!array_key_exists($strFilterPName, $arrFiltersPointer)){
							$arrFiltersPointer[$strFilterPName] = array();
						}
						
						// updating pointer
						$arrFiltersPointer = &$arrFiltersPointer[$strFilterPName];
					}
					
					// setting value 
					$arrFiltersPointer = $mValue;
					continue;
				}
				
				// setting param
				$this->setParam($strName, $mValue);
			}

			// do we have filters
			if(!empty($arrFilters)){
				// adding filters
				foreach($arrFilters as $arrFilterDatas){
					$this->addFilter($arrFilterDatas);
				}
			}

			return $this;
		}

		// checking name
		if(!is_string($strName) || empty($strName)){
			throw new Exception('invalid param name given : string expected');
		}

		// filtering filter
		if(strtolower($strName) == 'filter'){
			// adding a filter
			$this->addFilter($mValue);
			// done
			return $this;
		}

		if(strpos(strtolower($strName), 'filter.') === 0){
			throw new Exception('Filters properties may not be set this way. Please use a correctly formatted array, or use the addFilter method.');
		}

		// setting param
		$this->_arrParams[$strName] = $this->_callMagicFilter($strName, $mValue);
		// done
		return $this;
	}

	// returns true if param $strName is set
	// $arrUserParams can be used to extends the search. It will overwrite key in the default param array
	protected function _hasParam($strName, $arrUserParams = array()){

		// checking name
		if(!is_string($strName) || empty($strName)){
			throw new Exception('invalid param name given : string expected');
		}

		if(!is_array($arrUserParams)){
			throw new Exception('invalid param given : arrUserParams is expected to be an array');
		}

		return array_key_exists($strName, array_merge($this->_arrParams, $arrUserParams));
	}

	// returns the value of param $strName if exists of $mDefault
	// $arrUserParams can be used to extends the search. It will overwrite key in the default param array
	protected function _getParam($strName, $mDefault = false, $arrUserParams = array()){

		if(!$this->_hasParam($strName, $arrUserParams)){
			return $mDefault;
		}

		// do we have to extract the value from user params
		if(array_key_exists($strName, $arrUserParams)){
			// yes
			return $this->_callMagicFilter($strName, $arrUserParams[$strName]);
		}
		
		// returning default param
		return $this->_arrParams[$strName];
	}

	// copy a media
	protected function _mediaCopy($strTargetDir, $mSrc, $intUniqName = false){

		// do we have an array of media
		if(is_array($mSrc)){
			// yes
			// setting result
			$arrResult = array();
			// checking all sources
			foreach($mSrc as $mSubSrc){
				$arrResult[] = $this->_mediaCopy($strTargetDir, $mSubSrc, $intUniqName);
			}
			// done
			return $arrResult;
		}

		// no, we should have a simple string
		// do we have a valid file and string
		if(!is_string($mSrc)){
			// no			
			return false;
		}

		// testing file
		if(!is_file($mSrc)){
			// ok, trying to clean file
			$mSrc = Bootstrap::getPath($this->_getCleanFileName($mSrc));

			if(!is_file($mSrc)){
				// no way
				return false;
			}
		}

		// setting default target
		$strTarget = Bootstrap::getPath($strTargetDir.'/'.$this->_getCleanFileName(basename($mSrc)));

		// setting target ref
		$strTargetRef = md5(serialize(func_get_args()));

		// is file already copied
		if(isset($this->_arrCopiedMedia[$strTargetRef])){
			// yes
			return $this->_arrCopiedMedia[$strTargetRef];
		}

		// do we have to set a uniq name for the ressource
		if($intUniqName){
			// yes
			$arrPathInfo = pathinfo($mSrc);
			$strTarget   = Bootstrap::getPath($strTargetDir.'/'.md5(uniqid(basename($mSrc), true)).'.'.$arrPathInfo['extension']);
		}

		// adding ref to copied media list
		$this->_arrCopiedMedia[$strTargetRef] = $strTarget;
		
		// copying file
		copy($mSrc, $strTarget);
		// done
		return $strTarget;
	}

	// returns the type of media
	protected function _getMediaType($mMedia){
	
		// keeping only the first media as
		// there only should be one type of media by array
		if(is_array($mMedia)){
			$mMedia = array_shift($mMedia);
		}
		
		// do we have a unknown media
		if(!is_string($mMedia) || empty($mMedia)){
			// yes
			return 'unknown';
		}
		
		// do we have a file
		if(preg_match('/[\w]+\.([\w]{3,})$/', $mMedia, $arrMatches)){
			// yes
			// getting details
			$arrDetails = explode('/', mime_content_type($mMedia));
			
			// getting global media type only
			$strShortMime = array_shift($arrDetails);
			
			if($strShortMime == 'application'){
				if(in_array($arrMatches[1], array('m4a'))){
					return 'audio';
				}
			}
			
			return $strShortMime;
		}
		
		// returning default value
		return 'text';
	}

	// apply rendering
	public final function render($arrMedia, $arrParams, $strNote = false, $intScaleType = false){

		// checking generator
		$this->_validateGenerator($this->_oGenerator);

		if(!is_string($this->_strOutputDir) || empty($this->_strOutputDir)){
			throw new Exception('outputdir is not set');
		}
		
		// rendering media
		$arrMedia['media'] = $this->_render($arrMedia, $arrParams, $strNote, $intScaleType);

		// do we have an array of media that only contains one item
		if(is_array($arrMedia['media']) && count($arrMedia['media']) == 1){
			// yes
			$arrMedia['media'] = array_shift($arrMedia['media']);
		}
		
		// filtering media content
		$arrMedia['media'] = $this->_filterContent($arrMedia['media']);
		
		// setting default result array
		$arrDefault = array(
			'handle' 	=> get_class($this),
			'media'  	=> false,
			'title'  	=> false,
			'label'  	=> false,
			'css'    	=> false,
			'zone'   	=> 1,
			'order'	 	=> 1,
			'id'	 	=> md5(serialize(func_get_args()).uniqid()),
			'group'  	=> md5(serialize(func_get_args()).uniqid()),
			'linkedTo' 	=> false,
			'display'  	=> $this->_getMediaType($arrMedia['media']),
			'condition'	=> false,
			'root'		=> false
		);
		
		// inserting media datas
		$arrMedia = array_merge($arrDefault, $arrMedia);
		
		// do we have cardText objects
		foreach($arrMedia as $strKey => $mContent){
			// is the content a cardText
			if($mContent instanceof CardText){
				// yes
				$arrMedia[$strKey] = $mContent->getText();
			}
		}

		// do we have a css
		if(isset($arrMedia['css']) && !empty($arrMedia['css'])){
			if(is_string($arrMedia['css'])){
				// yes
				$arrMedia['css'] = array($arrMedia['css']);
			}
		}

		// do we have a media with an src
		if(isset($arrMedia['src'])){

			// do we have a list of media
			if(is_array($arrMedia['src'])){
				// yes extracting name
				$arrMedia['src'] = array_shift($arrMedia['src']);
			}

			if(is_string($arrMedia['src']) && !empty($arrMedia['src'])){
				$arrMedia['css'] = substr(basename($arrMedia['src']), 0, strrpos(basename($arrMedia['src']), '.'));
			}
		}

		// filtering keys to only keep the required ones
		$arrMedia = array_intersect_key($arrMedia, $arrDefault);
		
		// ensure css to be a string
		if(is_array($arrMedia['css'])){
			$arrMedia['css'] = implode(' ', $arrMedia['css']);
		}

		// done
		return $arrMedia;
	}

	// apply rendering
	abstract protected function _render($arrMedia, $arrParams, $strNote, $intScaleType);
}
