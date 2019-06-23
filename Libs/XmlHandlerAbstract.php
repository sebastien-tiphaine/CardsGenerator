<?php

//require_once(__DIR__.'/MusicLogicExt.php');
//extends MusicLogicExt
abstract class XmlHandlerAbstract{
	
	// current xml file
	protected $_strFileName = false;
	// current xml object
	protected $_oXml = null;
	// object ident
	protected $_strIdent = false;
	
	// xpath shortcuts
	protected $_arrXmlMapping = array();
	
	// sets local file name
	protected function _setFileName($strFileName){
		
		if(!is_string($strFileName) || empty($strFileName)){
			throw new Exception('Invalid file name given. String expected !'); 
		}
		
		if(!file_exists($strFileName)){
			throw new Exception('The given file does not exists : '.$strFileName.' !'); 
		}
		
		// setting file name
		$this->_strFileName = $strFileName;
		
		// removing xml object if any
		$this->_oXml = null;
		
		// changing object identification
		$this->_ident = md5(uniqid(__CLASS__, true).time());
		
		return $this;
	}
	
	// returns current file name
	public function getFileName(){
		return $this->_strFileName;
	}
	
	// make an xpath query on the current xml object
	public function xpath($strPath, $intSingle = false){
		
		// checking string
		if(!is_string($strPath) || empty($strPath)){
			throw new Exception('Missing path string');
		}
		
		// do we have a shortcut
		if(preg_match('/(#([A-Za-z]+)#)/', $strPath, $arrMatches)){
			// yes
			// do we have a mapping
			if(!is_array($this->_arrXmlMapping) || empty($this->_arrXmlMapping)){
				throw new Exception('_arrXmlMapping is not set or empty in the child class');
			}
			
			// setting default map value
			$strMapValue = false;
			$strRplStr   = $arrMatches[0];
				
			if(isset($arrMatches[1]) && !empty($arrMatches[1]) && 
			   isset($this->_arrXmlMapping[$arrMatches[1]]) && !empty($this->_arrXmlMapping[$arrMatches[1]])){
				 // getting mapped value
				 $strMapValue = $this->_arrXmlMapping[$arrMatches[1]];
			}
			else if(isset($arrMatches[2]) && !empty($arrMatches[2]) && 
			   isset($this->_arrXmlMapping[$arrMatches[2]]) && !empty($this->_arrXmlMapping[$arrMatches[2]])){
				 // getting mapped value
				 $strMapValue = $this->_arrXmlMapping[$arrMatches[2]];
			}
			
			if(!is_string($strMapValue) || empty($strMapValue)){
				throw new Exception('Xml shortcut not is defined in arrXmlMapping : '.$strPath);
			}
			
			// updating path
			$strPath = str_replace($strRplStr, $strMapValue, $strPath);
		}
			
		// getting xml object
		$oXml = $this->getXml();
		
		// do we have something usable
		if(!$oXml instanceof SimpleXMLElement){
			// no
			throw new Exception('No xml file loaded');
		}
		
		// querying
		$mResult = $oXml->xpath($strPath);
		
		// do we have a result
		if(!is_array($mResult) || empty($mResult)){
			// no
			return false;
		}
		
		// do we have to return the result
		if(!$intSingle){
			// yes
			return $mResult;
		}
				
		// do we have a usable content in the result array
		if(!isset($mResult[0]) || !$mResult[0] instanceof SimpleXMLElement){
			// no
			return false;
		}
		
		// returning the first item only as the single flag is set
		return $mResult[0];
	}
	
	// return an object version of the xml file
	public function getXml(){
			
		if(is_null($this->_oXml)){
			$this->loadXml();
		}
		
		return $this->_oXml;
	}
	
	// load main xml file and turn it to an object
	public function loadXml(){
		
		if(!is_null($this->_oXml)){
				return $this;
		}
			
		// loading content
		$strXml = file_get_contents($this->_strFileName);
		// parse xml
		$this->_oXml = simplexml_load_string($strXml);
		//
		return $this;
	}
	
	// returns the parent object of $oXml
	protected function _getParent(SimpleXMLElement $oXml){
		// getting parent object
		$mParent = $oXml->xpath('..');
		
		// do we have a SimpleXml
		if($mParent instanceof SimpleXMLElement){
			// yes
			return $mParent;
		}
		
		// do we have an array
		if(is_array($mParent) && isset($mParent[0]) && $mParent[0] instanceof SimpleXMLElement){
			// yes returning first item only
			return $mParent[0];
		}
		
		// nothing usable
		return false;
	}
	
	// returns all childs with tag name $strName
	protected function _getAllChilds($mXml, $strName){
		
		if(is_array($mXml) && !empty($mXml)){
			// setting default result
			$arrResult = array();
			
			foreach($mXml as $oXml){
				// checking object
				if(!$oXml instanceof SimpleXMLElement){
					throw new Exception('invalid array given. Expected : array of SimpleXMLElement');
				}
				// merging results
				$arrResult = array_merge($arrResult, $this->_getAllChilds($oXml, $strName));
			}
			// done
			return $arrResult;
		}
		
		// checking object
		if(!$mXml instanceof SimpleXMLElement){
			throw new Exception('SimpleXMLElement Expected');
		}
		
		// list of childs
		$arrResult = array();
		
		foreach($mXml as $strTag => $oChild){
			// do we have a valid tag name
			if($strTag == $strName){
				// yes
				$arrResult[] = $oChild;
			}
		}
		
		// done
		return $arrResult;
	}
	
	// inserte $oNewNode before $oBeforeNode in the Dom
	protected function _insertBefore(SimpleXMLElement $oNewNode, SimpleXMLElement $oBeforeNode){
	
		// inserting NewNode using Dom
		$oTargetDom = dom_import_simplexml($oBeforeNode);
		$oInsertDom = $oTargetDom->ownerDocument->importNode(dom_import_simplexml($oNewNode), true);
		$oTargetDom->parentNode->insertBefore($oInsertDom, $oTargetDom);
		// done
		return $this;
	}
	
	// inserte $oNewNode after $oBeforeNode in the Dom
	protected function _insertAfter(SimpleXMLElement $oNewNode, SimpleXMLElement $oAfterNode){
	
		// inserting NewNode using Dom
		$oTargetDom = dom_import_simplexml($oAfterNode);
		$oInsertDom = $oTargetDom->ownerDocument->importNode(dom_import_simplexml($oNewNode), true);
		$oTargetDom->parentNode->insertBefore($oInsertDom, $oTargetDom->nextSibling);
		// done
		return $this;
	}
	
	
	// insert $oNewNode as the first child of $oParent
	protected function _insertAsFirstChild($oNewNode, $oParent){
		
		// getting the fist node
		$oBeforeNode = $oParent->xpath('./*')[0];
		// can we insert the node before the first child
		if($oBeforeNode instanceof SimpleXMLElement){
			// yes
			$this->_insertBefore($oNewNode, $oBeforeNode);
			// done
			return true;
		}
		
		// no
		// TODO : insert a node when no child is present
		return false;
	}
	
	// add a new note to $oParent. Node is added as the last child
	protected function _addNode(SimpleXMLElement $oNewNode, SimpleXMLElement $oParent){
		// getting dom version of parent node
		$oParentDom = dom_import_simplexml($oParent);
		// getting dom version of the new node
		$oNewNodeDom = $oParentDom->ownerDocument->importNode(dom_import_simplexml($oNewNode), true);
		$oParentDom->appendChild($oNewNodeDom);
		// done
		return true;
	}
	
	// remove a node
	protected function _removeNode(SimpleXMLElement $oNode){
			
		$oDom = dom_import_simplexml($oNode);
		$oDom->parentNode->removeChild($oDom);
		// done
		return true;
	}
	
	// get the first (or last if flag set) child $strName of the $oXml object with id $strId
	protected function _getChild(SimpleXMLElement $oXml, $strName, $strId = false, $intLast = false){
		
		// setting vars	
		$intCount = 0;
		$intIndex = 0;
		
		// checking strid
		if($strId instanceof SimpleXMLElement){
			$strId = $strId->__toString();	
		}
		
		// checking strname
		if($strName instanceof SimpleXMLElement){
			$strName = $strName->__toString();	
		}
		
		// do we have to get the last child
		if($intLast){
			$intCount = count($oXml);
		}	
		
		foreach($oXml as $oChild){
			// updating index
			$intIndex++;
			
			if($oChild->getName() != $strName){
				continue;
			}
			
			if(!is_string($strId) || empty($strId)){
				// do we have to get the last child
				if($intLast && $intIndex < $intCount){
					continue;
				}
				// returing child object
				return $oChild;
			}
			
			if(isset($oChild->attributes()->id) && $oChild->attributes()->id == $strId){
				return $oChild;
			}
		}

		return null;
	}
}
