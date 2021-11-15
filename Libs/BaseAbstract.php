<?php

require_once(__DIR__.'/CardTemplate.php');

abstract class BaseAbstract{

	// is debug activated
	protected $_intDebug = false;
	// debug array - contains all debug trace
	protected $_arrDebug       = array();
	// turn to true when debug message should not be displayed
	protected $_intSilentDebug = false;
	// cli messages array - contains all debug trace
	protected $_arrCliMsgs     = array();

	// activate debug messages
	public function setDebug($intDebug = true){
			$this->_intDebug = $intDebug;
			return $this;
	}

	// checkig if silentDebug is set for all objects
	protected function _checkGlobalSilentDebug(){

		// do we have to update local debug value
		if(isset($_SERVER['silentDebug']) && $_SERVER['silentDebug'] != $this->_intSilentDebug){
			// yes
			$this->setSilentDebug($_SERVER['silentDebug']);
		}

		return $this;
	}
	
	// open or close debug output
	public function setSilentDebug($intSilentDebug = true){
		
			// do we have to sent debut messages to standard output
			if(!$intSilentDebug && $this->_intSilentDebug){
				// yes
				foreach($this->_arrDebug as $strDebug){
						echo $strDebug;
				}
				// cleaning debug
				$this->_arrDebug = array();

				// yes
				foreach($this->_arrCliMsgs as $strCli){
						echo $strCli;
				}
				// cleaning cli messages
				$this->_arrCliMsgs = array();
			}

			// setting global var
			$_SERVER['silentDebug'] = $intSilentDebug;
			// setting flag
			$this->_intSilentDebug = $intSilentDebug;
			return $this;
	}
	
	// displays a debug message
	protected function _debugMessage($strMessage){

		// checking global debug
		$this->_checkGlobalSilentDebug();

		if(!$this->_intDebug){
			return $this;
		}
		
		$strDebug = 'Debug :'.debug_backtrace()[1]['function'].' : '.$strMessage."\n";
		
		if($this->_intSilentDebug){
			$this->_arrDebug[] = $strDebug;
			return $this;
		}
		
		echo $strDebug;
		
		return $this;
	}

	// displays a message on the cli
	protected function _cliOutput($strMessage, $intNoEnding = false){

		// checking global debug
		$this->_checkGlobalSilentDebug();

		if(!$intNoEnding){
			$strMessage.="\n";
		}

		if($this->_intSilentDebug){
			$this->_arrCliMsgs[] = $strMessage;
			return $this;
		}

		echo $strMessage;

		return $this;	
	}

	// throws an exception if $oGenerator is not a generator
	protected function _validateGenerator($oGenerator){

		if(!$oGenerator instanceof CardGenerator){
			throw new Exception(get_class($this).' : invalid generator object found');
		}

		return $this;
	}

	// replace $arrVarParam enclosed by $strDelim in $mHaystack
	protected function _replaceVar($arrVarParam, $mHaystack, $oGenerator = null, $strLDelim = '%', $strRDelim = false){
	
		// do we have multiple haystacks 
		if(is_array($mHaystack)){
			// yes
			foreach($mHaystack as $mKey => $mEntry){
				$mHaystack[$mKey] = $this->_replaceVar($arrVarParam, $mEntry, $oGenerator, $strLDelim, $strRDelim);
			}

			return $mHaystack;
		}

		// do we have to extract the content from a CardText
		if($mHaystack instanceof CardText){
			//yes
			$mHaystack = $mHaystack->getText();
		}

		if(!is_string($mHaystack)){
			// nothing can be changed
			return $mHaystack;
		}

		//is Right delim set
		if(!$strRDelim){
			// no
			$strRDelim = $strLDelim;
		}

		foreach($arrVarParam as $strName => $mValue){

			// do we have en empty value
			// Nb : Empty value should replace a place holder anyway
			/*if(empty($mValue)){
				// yes
				continue;
			}*/

			if(!is_string($mValue) && !is_numeric($mValue)){
				print_r($arrVarParam);
				throw new Exception('invalid value found on '.$strName.' : string or number expected');
			}

			// applying var change
			$mHaystack = str_replace($strLDelim.$strName.$strRDelim, $mValue, $mHaystack);
		}

		return $mHaystack;
	}

	// render $strFile with $arrvars and returns the result
	protected function _renderTemplate($strFile, $arrvars = array()){
		
		// checking if arrVars is an array
		if(!is_array($arrvars)){
			// no
			$arrvars = array($arrvars);
		}
		
		// do we have something usable
		if(!is_string($strFile)){
			// no
			throw new Exception('invalid template file name given : string expected !');
			// nothing more to do
			return $this;
		}
		
		// do we have an existing file
		if(!file_exists($strFile)){
			// no
			// trying to resolv the path
			$strResFile = Bootstrap::getPath($strFile);
			
			// do we now have a file
			if(!file_exists($strResFile)){
				// no
				throw new Exception('invalid template file name : '.$strFile);
			}
			
			// yes. updating file
			$strFile = $strResFile;
		}
		
		// creating template
		$oCardTemplate = new CardTemplate();
		// inserting vars
		$oCardTemplate->setVars($arrvars);
		
		$intSilentDebugStatus = $this->_intSilentDebug;
		
		// avoid datas to be sent to standard output
		$this->setSilentDebug(true);
		
		// rendering template
		$strContent = $oCardTemplate->render($strFile);
		
		// reopening debug
		if(!$intSilentDebugStatus){
			$this->setSilentDebug(false);
		}
		// done
		return $strContent;
	}

	// call $strMethod on each Object in array $arrObjects
	protected function _triggerObjectArray($arrObjects, $strMethod, $arrParams = array(), $intCliOut = false){

		// do we have objects
		if(!is_array($arrObjects) || empty($arrObjects)){
			// no
			return $this;
		}

		// Result of each call
		$arrResult = array();

		foreach($arrObjects as $oObject){
			if($intCliOut){
				$this->_cliOutput('# '.$strMethod.' on '.get_class($oObject).'...', true);
			}
			
			$arrResult[] = call_user_func_array(array($oObject, $strMethod), array_values($arrParams));

			if($intCliOut){
				$this->_cliOutput(' [Ok]');
			}
		}

		return $arrResult;
	}

	// returns true if $strStr is a plural
	protected function _isPlural($strStr){

		if(!is_string($strStr) || empty($strStr)){
			throw new Exception('invalid param given. String expected');
		}

		if(strrpos($strStr, 's') == strlen($strStr) -1){
			return true;
		}

		return false;
	}

	// returns the singular name of a word
	protected function _getSingular($strStr){

		if(!$this->_isPlural($strStr)){
			return $strStr;
		}

		return substr($strStr, 0, -1);
	}

	// returns a clean version of $strFileName
	protected function _getCleanFileName($strFileName){

		if(!is_string($strFileName) || empty($strFileName)){
			throw new Exception('invalid file name given : string expected');
		}

		// removing white spaces
		$strFileName = str_replace(' ', '_', $strFileName);
		// removing sharp
		$strFileName = str_replace('#','sharp', $strFileName);

		// sanitizing file name
		$strFileName = preg_replace('/[^a-zA-Z0-9\-\._]/','', $strFileName);

		return $strFileName;
	}
}
