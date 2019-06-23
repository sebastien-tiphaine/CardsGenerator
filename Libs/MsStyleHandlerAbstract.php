<?php

require_once(__DIR__.'/XmlHandlerAbstract.php');

abstract class MsStyleHandlerAbstract extends XmlHandlerAbstract{
	
	const UNIT_INCH 	= 'inch';
	const UNIT_POINT 	= 'point';
	const UNIT_MM    	= 'mm';
	const UNIT_SPATIUM 	= 'spatium';
	
	// list of loaded styles
	protected $_arrLoadedStyles = false;
	
	// list of system texts
	protected $_arrSystemTexts = array(
		'default', 'title', 'subTitle', 
		'composer', 'lyricist', 'fingering', 
		'stringNumber', 'measureNumber', 'header', 'footer'
	);
	
	// default text style properties
	protected $_arrTextStylesDefaultProperties = array(
		'Name'			=> false,
		'FontFace' 		=> 'FreeSerif',
		'FontSize' 		=> 10,
		'FontStyle' 	=> 0,
		'FontSpatiumDependent' => 0,
		'Align'			=> 'left',
		'Offset'   		=> array(
							'x' => 0,
							'y' => 0
							), 
		'OffsetType' 	=> 1,
		'FrameType' 	=> 0,
		'FramePadding'	=> 0.1, // marge entre la bordure de text et le texte
		'FrameWidth' 	=> 0.2, // largeur de la bordure de texte
		'FrameRound' 	=> 0,
		'FrameFgColor'  => array(
							'r' => 0, 
							'g' => 0, 
							'b' => 0, 
							'a' => 255
						 ),
		'FrameBgColor'	=> array(
							'r' => 255, 
							'g' => 255, 
							'b' => 255, 
							'a' => 0
						 ),
	);
	
	// index unit of each properties
	protected $_arrPropertiesUnits = array(
		'pageWidth' 			=> self::UNIT_INCH,
		'pageHeight' 			=> self::UNIT_INCH,
		'pagePrintableWidth' 	=> self::UNIT_INCH,
		'pageEvenLeftMargin' 	=> self::UNIT_INCH,
		'pageOddLeftMargin'  	=> self::UNIT_INCH,
		'pageEvenTopMargin'  	=> self::UNIT_INCH,
		'pageEvenBottomMargin' 	=> self::UNIT_INCH,
		'pageOddTopMargin'   	=> self::UNIT_INCH,
		'pageOddBottomMargin' 	=> self::UNIT_INCH,
		'Spatium' 				=> self::UNIT_MM,
		'staffUpperBorder' 		=> self::UNIT_SPATIUM,
		'staffLowerBorder' 		=> self::UNIT_SPATIUM,
		'staffDistance'    		=> self::UNIT_SPATIUM,
		'akkoladeDistance' 		=> self::UNIT_SPATIUM,
		'minSystemDistance' 	=> self::UNIT_SPATIUM,
		'maxSystemDistance' 	=> self::UNIT_SPATIUM,
		'systemFrameDistance' 	=> self::UNIT_SPATIUM,
		'frameSystemDistance' 	=> self::UNIT_SPATIUM,
		'minMeasureWidth' 		=> self::UNIT_SPATIUM,
		'FontSize'        		=> self::UNIT_POINT,
		'Offset'		  		=> self::UNIT_SPATIUM,
		'FramePadding'    		=> self::UNIT_SPATIUM,
		'FrameWidth'	  		=> self::UNIT_SPATIUM,
	);
	
	// spacium value
	protected $_intSpatium = false;
	
	// returns the value of the current spatium
	public function getSpatium($strUnit = false){
		
		// do we already have the spacium value
		if(!is_numeric($this->_intSpatium)){
			// no
			// setting value
			$this->_intSpatium = floatval($this->getProperty('Spatium', 1.764));
		}
		
		// do we have to convert the value
		if($strUnit === false || $strUnit == $this->getPropertyUnit('Spatium')){
			// no
			return $this->_intSpatium;
		}
		
		// do we have a convertion to spatium
		if($strUnit == self::UNIT_SPATIUM){
			throw new Exception('Spatium cannot be converted to spatium !');
		}
		
		// done
		return $this->unitConvert($this->_intSpatium, $this->getPropertyUnit('Spatium'), $strUnit);
	}
	
	// convert a value in point to millimeters
	// note : police size is in point
	protected function _pointToMm($intValue){
		return round((floatval($intValue) * 0.352778), 5);
	}
	
	// convert a value in point to millimeters
	// note : police size is in point
	protected function _mmToPoint($intValue){
		return round((floatval($intValue) / 0.352778), 5);
	}
	
	// convert a value in point to inch
	// note : police size is in point
	protected function _pointToInch($intValue){
		return round((floatval($intValue) * 0.013888888888889), 5);
	}
	
	// convert a value in inch to point
	// note : police size is in point
	protected function _inchToPoint($intValue){
		return round((floatval($intValue) * 71.999999999999), 5);
	}
	
	// convert a value in millimeters to inch
	protected function _mmToInch($intValue){
		return round((floatval($intValue) * 0.0393700787), 5);
	}
	
	// convert a value in inch to millimeters
	protected function _inchToMm($intValue){
		return round((floatval($intValue) * 25.4), 2);
	}
	
	// convert a value from a unit to another
	public function unitConvert($mValue, $strFrom = false, $strTo = false){

		// do we have something to convert
		if(!is_numeric($mValue)){
			// no
			return $mValue;
		}
	
		// is value given as a string
		if(is_string($mValue)){
			// yes
			$mValue = $this->_xmlConvertionStringToNumber($mValue);
		}
		
		// list of available units
		$arrAvailUnits = array(self::UNIT_INCH, self::UNIT_POINT, self::UNIT_MM, self::UNIT_SPATIUM);
		
		if(!in_array($strFrom, $arrAvailUnits)){
			throw new Exception('Unknown unit : '.$strFrom);
		}
		
		if(!in_array($strTo, $arrAvailUnits)){
			throw new Exception('Unknown unit : '.$strFrom);
		}
		
		// do we have to convert the value
		if($strFrom == $strTo){
			// no
			return $mValue;
		}
		
		// setting method name
		$strMethod = '_'.$strFrom.'To'.ucfirst($strTo);
		
		// does the method exists to convert the value
		if(method_exists($this, $strMethod)){
			// yes
			return call_user_func(array($this, $strMethod), $mValue);
		}
		
		// do we have a spatium call
		if($strFrom != self::UNIT_SPATIUM && $strTo != self::UNIT_SPATIUM ){
			// no
			throw new Exception('No method found to convert a value from : '.$strFrom.' to '.$strTo);
		}
		
		// do we have to convert a value from spatium to something else
		if($strFrom == self::UNIT_SPATIUM){
			// yes
			return round(floatval($mValue) * $this->getSpatium($strTo), 2);
		}
		
		// we have to convert a value from something to spatium
		return round(floatval($mValue) / $this->getSpatium($strFrom), 2);
	}
	
	// returns true if $strName is a system text
	protected function _isSystemTextStyle($strName){
		
		// do we have something usable
		if(!is_string($strName) || empty($strName)){
			// no
			return false;
		}
		
		return in_array($strName, $this->_arrSystemTexts);
	}
	
	// ensure $arrTextStyle to be formated and usable
	protected function _formatTextStyle($arrTextStyle, $strName = false){
		
		// checking given value	
		if(!is_array($arrTextStyle) || empty($arrTextStyle)){
			throw new Exception('Empty style array given');
		}
		
		// is given style a system text style
		$intIsSysText = $this->_isSystemTextStyle($strName);
		
		// setting default value.
		// if strName is a sysText we don't need to add default properties
		// as we should use the internal ones
		$arrFiltered = ($intIsSysText)? array() : $this->_arrTextStylesDefaultProperties;
		
		// global key checking
		foreach($arrTextStyle as $strKey => $mValue){
			
			// do we have a string
			if(!is_string($strKey)){
				// no
				throw new Exception('Style key is expected to be a string!');
			}
				
			// formating Key
			$strKey = ucfirst($strKey);
			
			// is the key allowed
			if(!in_array($strKey, array(
			  'Name', 'FontFace', 'FontSize', 'FontStyle', 'FontSpatiumDependent', 'Align', 'Offset', 'OffsetType',
			  'FrameType', 'FramePadding', 'FrameWidth', 'FrameRound', 'FrameFgColor', 'FrameBgColor'
			     
			))){
				// no
				throw new Exception('Style key is not allowed : '.$strKey);
			}
			
			// is value a required numerical value
			if(in_array($strKey, array(
			  'FontSize', 'OffsetType', 'FramePadding', 'FrameWidth', 'FrameRound'
			     
			)) && !is_numeric($mValue) ){
				// yes
				throw new Exception('Style value '.$strKey.' is expected to be a number !');
			}
			
			$arrFiltered[$strKey] = $mValue;
		}
		
		// for systext, name is not required
		if(!$intIsSysText){
			
			// do we have a name
			if(!isset($arrFiltered['Name']) || !is_string($arrFiltered['Name']) || empty($arrFiltered['Name'])){
				// no
				throw new Exception('Style Name is missing !');
			}
			
			// filtering name
			$arrFiltered['Name'] = trim($arrFiltered['Name']);
		}
		
		// checking FontStyle value
		if(isset($arrFiltered['FontStyle'])){
			
			if(!is_numeric($arrFiltered['FontStyle'])){
				// do we have an array
				if(!is_array($arrFiltered['FontStyle'])){
					// no
					$arrFiltered['FontStyle'] = explode(',', $arrFiltered['FontStyle']);
				}
				
				// setting default value
				$intValue = 0;
				
				foreach($arrFiltered['FontStyle'] as $strFontStyle){
					
					switch(strtolower(trim($strFontStyle))){
						case 'b':
						case 'bold':
							$intValue+=1;
							break;
						
						case 'i':
						case 'italic':
							$intValue+=2;
							break;
						
						case 'u':
						case 'underline':
						case 'underlined':
							$intValue+=4;
							break;
						
						default:
							// nothing to do
					}
				}
				// updating value
				$arrFiltered['FontStyle'] = $intValue;
			}// end numerical check
			
			// ensure value to be a number
			$arrFiltered['FontStyle'] = intval($arrFiltered['FontStyle']);
			// checking value
			if($arrFiltered['FontStyle'] < 0 || $arrFiltered['FontStyle'] > 7){
				throw new Exception('Unexpected FontStyle value : '.$arrFiltered['FontStyle']);
			}
		}
		
		// checking Align value
		if(isset($arrFiltered['Align'])){
			 // possible values
			 // 'h' => array('left', 'center', 'right'),
			 // 'v' => array('', 'center', 'baseline', 'bottom'),			
			if(is_array($arrFiltered['Align'])){
				// setting default value
				$strHalign = 'left';
				$strValign = '';
				
				if(isset($arrFiltered['Align']['h']) && !empty($arrFiltered['Align']['h'])){
					$strHalign = $arrFiltered['Align']['h'];
				}
				else if(isset($arrFiltered['Align']['halign']) && !empty($arrFiltered['Align']['halign'])){
					$strHalign = $arrFiltered['Align']['halign'];
				}
				
				if(isset($arrFiltered['Align']['v']) && !empty($arrFiltered['Align']['v'])){
					$strValign = $arrFiltered['Align']['v'];
				}
				else if(isset($arrFiltered['Align']['valign']) && !empty($arrFiltered['Align']['valign'])){
					$strValign = $arrFiltered['Align']['valign'];
				}
				
				if(empty($strValign)){
					$arrFiltered['Align'] = $strHalign;
				}
				else {
					$arrFiltered['Align'] = $strHalign.','.$strValign;
				}
			}
		}
		
		// checking FrameType
		if(isset($arrFiltered['FrameType']) && is_string($arrFiltered['FrameType'])){
			
			switch(strtolower(trim($arrFiltered['FrameType']))){
				case 'square':
					$arrFiltered['FrameType'] = 1;
					break;
				case 'circle':
					$arrFiltered['FrameType'] = 2;
					break;
				default:
					$arrFiltered['FrameType'] = 0;
			}
		}
		
		// checking FrameFgColor
		if(isset($arrFiltered['FrameFgColor'])){
		
			if(!is_array($arrFiltered['FrameFgColor']) || count($arrFiltered['FrameFgColor']) < 4){
				throw new Exception ('FrameFgColor parameter must be an array containing 4 values (r,g,b,a)');
			}
			
			$arrFiltered['FrameFgColor'] = array_combine(array('r', 'g', 'b', 'a'), $arrFiltered['FrameFgColor']);
		}
		
		// checking FrameBgColor
		if(isset($arrFiltered['FrameBgColor'])){
		
			if(!is_array($arrFiltered['FrameBgColor']) || count($arrFiltered['FrameBgColor']) < 4){
				throw new Exception ('FrameBgColor parameter must be an array containing 4 values (r,g,b,a)');
			}
			
			$arrFiltered['FrameBgColor'] = array_combine(array('r', 'g', 'b', 'a'), $arrFiltered['FrameBgColor']);
		}
		
		// checking Offset
		if(isset($arrFiltered['Offset'])){
		
			if(!is_array($arrFiltered['Offset']) || count($arrFiltered['Offset']) < 2){
				throw new Exception ('Offset parameter must be an array containing 2 values (x,y)');
			}
			
			$arrFiltered['Offset'] = array_combine(array('x', 'y'), $arrFiltered['Offset']);
		}
		
		// done
		return $arrFiltered;
	}
	
	// convert an xml numerical xml string into an php number
	protected function _xmlConvertionStringToNumber($mValue){
		
		// do we have a numerical value that have to be converted
		if(preg_match('/[0-9]+\.[0-9]+/', $mValue)){
			// yes
			return floatval($mValue);
		}
		
		// do we have a simple integer
		if(is_numeric($mValue)){
			// yes
			return intval($mValue);
		}
		
		// nothing to do
		return $mValue;
	}
	
	// extract property value
	protected function _extractPropertyValue($oProperty, $strPrefix = false){
	
		// do we have a valide property
		if(!$oProperty instanceof SimpleXMLElement){
			// no
			throw new exception('Invalide object given. SimpleXMLElement expected !');
		}
	
		// extracting property name
		$strName = $oProperty->getName();
	
		// do we have a prefix
		if(is_string($strPrefix) && !empty($strPrefix)){
			// yes
			// removing prefix
			$strName = substr($strName, strlen($strPrefix));
		}
	
		// do we have the "Name" property
		if($strName == 'Name'){
			// yes
			return array('Name' => $strPrefix);
		}
		
		// do we have attributes that should replace the value
		if(count($oProperty->attributes()) === 0){
			// no
			return array($strName => $this->_xmlConvertionStringToNumber($oProperty->__toString()));
		}
		
		// setting default value
		$arrDatas = array();
		
		// value have to be extracted from attributes
		foreach($oProperty->attributes() as $strAttrName => $mAttrVal){
			// extracting value
			$arrDatas[$strAttrName] = $this->_xmlConvertionStringToNumber($mAttrVal->__toString());
		}
		
		// done
		return array($strName => $arrDatas);
	}
	
	// loads all userText styles
	public function getUserTextStyles(){
		
		// is mapping defined
		if(!isset($this->_arrXmlMapping['Style'])){
			// no
			throw new Exception('XmlMapping for Style is not set.');
		}
		
		// getting a list of declared text styles
		$arrStylesNames = $this->xpath('#Style#/*[substring(name(), string-length(name()) - string-length(\'Name\') +1) = \'Name\']');
					
		// do we have text styles in the file
		if(!is_array($arrStylesNames) || empty($arrStylesNames)){
			// no
			return array();
		}
		
		// setting result array
		$arrResult = array();
		
		foreach($arrStylesNames as $oName){
		
			// do we have a valid name
			if(!$oName instanceof SimpleXMLElement){
				// no
				continue;
			}
			
			// getting object name
			$strName = $oName->getName();
			// getting properties
			$arrResult[$strName] = $this->getPropertiesStartingWith($strName, false);
		}
		
		// done
		return $arrResult;
	}

	// loads all userText styles
	public function getSystemTextStyles(){
		
		// is mapping defined
		if(!isset($this->_arrXmlMapping['Style'])){
			// no
			throw new Exception('XmlMapping for Style is not set.');
		}
		
		// setting result array
		$arrResult = array();
		
		foreach($this->_arrSystemTexts as $strName){
			// getting properties
			$arrResult[$strName] = $this->getPropertiesStartingWith($strName, false);
		}
		
		// done
		return $arrResult;
	}
	
	// loads all text style into the cache
	public function loadTextStyles($intReload = false){
		
		// are the styles already loaded
		if(!$intReload && is_array($this->_arrLoadedStyles)){
			// yes
			return $this;
		}

		// getting styles
		$this->_arrLoadedStyles = array_merge(
			$this->getSystemTextStyles(),
			$this->getUserTextStyles()
		);
			
		// done
		return $this;
	}
	
	// returns true if $strName is an existing style
	public function hasTextStyle($strName){
		
		// ensures styles to be loaded
		$this->loadTextStyles();
		// do we have a style name $strName
		return array_key_exists($strName, $this->_arrLoadedStyles);
	}
	
	// returns styles data for text style $strName
	// returns false if style does not exists
	public function getTextStyle($strName){
		
		// do we have a style name $strName
		if(!self::hasTextStyle($strName)){
			// no
			return array();
		}
		
		// done
		return $this->_arrLoadedStyles[$strName];
	}
	
	// add a complete style array to current xml
	public function setTextStyle($strName, $arrStyle){
	
		// ensure style to be in the right format
		$arrStyle = $this->_formatTextStyle($arrStyle, $strName);
		
		foreach($arrStyle as $strProp => $mValue){
			$this->setTextStyleProp($strName, $strProp, $mValue);
		}
		
		// reloading styles
		$this->loadTextStyles(true);
		// done
		return $this;
	}
	
	// returns a property of a given style
	// returns $mDefault if style does not exists or $strProp is not set
	public function getTextStyleProp($strName, $strProp, $mDefault = false){
		
		// getting style datas
		$arrStyle = self::getTextStyle($strName);
		
		if(!is_array($arrStyle) || !array_key_exists($strProp, $arrStyle)){
			return $mDefault;
		}
		// done
		return $arrStyle[$strProp];
	}
	
	// add a property for a text style
	public function setTextStyleProp($strName, $strProp, $mValue){
		return $this->setProperty($strName.$strProp, $mValue);
	}
	
	// returns true if current css file has a propertie $strName
	public function hasProperty($strName){
		
		// is mapping defined
		if(!isset($this->_arrXmlMapping['Style'])){
			// no
			throw new Exception('XmlMapping for Style is not set.');
		}
		
		// getting style
		$oStyle = $this->xpath('#Style#/'.$strName, true); 
		
		if(!$oStyle instanceof SimpleXMLElement){
			return false;
		}
		
		return true;
	}
	
	// returns all properties starting with a $strName
	public function getPropertiesStartingWith($strName, $intKeepName = true){
		
		// do we have a valid name
		if(!is_string($strName) || empty($strName)){
			throw new Exception('Properties name is expected to be a string');
		}
		
		// getting properties
		$arrProps = $this->xpath('#Style#/*[starts-with(name(), \''.$strName.'\')]');
		
		// do we have a result	
		if(!is_array($arrProps) || empty($arrProps)){
			// no
			return false;
		}
			
		// setting default style datas
		$arrDatas = array();
			
		foreach($arrProps as $oData){
			
			// do we have a valid name
			if(!$oData instanceof SimpleXMLElement){
				// no
				continue;
			}
			
			// setting prefix
			$strPrefix = ($intKeepName)? false : $strName;
			
			// getting property value
			$arrProperty = $this->_extractPropertyValue($oData, $strPrefix);
			// adding property to main data array
			$arrDatas = array_merge($arrDatas, $arrProperty);
		}
	
		// done
		return $arrDatas;
	}
	
	// returns unit of a property if applicable
	public function getPropertyUnit($strProperty){
		
		if(!is_string($strProperty) || !array_key_exists($strProperty, $this->_arrPropertiesUnits)){
			return false;
		}
		
		return $this->_arrPropertiesUnits[$strProperty];
	}
	
	// returns the value of a property
	public function getProperty($strName, $mDefault = false, $strUnit = false){
		
		//do we have a property named $strName
		if(!$this->hasProperty($strName)){
			// no
			return $mDefault;
		}
		
		// getting style
		$oStyle = $this->xpath('#Style#/'.$strName, true); 
		
		if(!$oStyle instanceof SimpleXMLElement){
			// humm we should never be there
			throw new Exception('no able to extact property : '.$strName);
		}
		
		// getting datas
		$arrDatas = $this->_extractPropertyValue($oStyle);
		// extracting datas
		$mDatas = current($arrDatas);
			
		// do we have to convert the result
		if($strUnit == false || !is_numeric($mDatas)){
			// no
			return $mDatas;
		}
	
		// do we have a unit for this property
		$strPropUnit = $this->getPropertyUnit($strName);
		
		if(!$strPropUnit){
			throw new Exception('Unit is not defined for property : '.$strName);
		}
		
		// returning a converted data
		return $this->unitConvert($mDatas, $strPropUnit, $strUnit);
	}
	
	// sets or adds a property
	public function setProperty($strName, $mValue, $strUnit = false){
		
		// checking name
		if(!is_string($strName) || empty($strName)){
			throw new Exception('Invalid property name. String expected.');
		}
		
		// getting style
		$oStyle = $this->xpath('#Style#', true);
		
		// adapting number
		if(is_numeric($mValue)){
			// do we have to convert the value
			if($strUnit != false){
				// yes
				// getting unit in with value have to be saved
				$strPropUnit = $this->getPropertyUnit($strName);
				// do we have a unit for the property
				if(!$strPropUnit){
					// no
					throw new Exception('Unit is not defined for property : '.$strName);
				}
				
				// do we have to convert the value
				if($strPropUnit != $strUnit){
					// yes
					// converting value
					$mValue = $this->unitConvert($mValue, $strUnit, $strPropUnit);
				}
			}
			
			// applying replace if required
			$mValue = str_replace(',', '.', $mValue); 
		}
		
		// do we have to make a simple set
		if(!is_array($mValue)){
			// yes
			$oStyle->children()->{$strName} = $mValue;
			// done
			return $this;
		}
		
		// no we have an array so data may have to be set as attributes
		// does the property already exists
		$oProperty = $this->xpath('#Style#/'.$strName, true); 
		
		if($oProperty instanceof SimpleXMLElement){
			// yes
			// removing property
			$this->_removeNode($oProperty);
		}
		
		// setting default attributes string
		$strAttributes = '';
				
		foreach($mValue as $strValKey => $mValVal){
			if(!empty($strAttributes)){
				$strAttributes.=' ';
			}
			$strAttributes.= $strValKey.'="'.$mValVal.'"';
		}
		
		// setting xml string
		$this->_addNode(simplexml_load_string('<'.$strName.' '.$strAttributes.'/>'), $oStyle);
		// done
		return $this;
	}
}
