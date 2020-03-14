<?php

require_once(__DIR__.'/XmlHandlerAbstract.php');

class SvgAdapt extends XmlHandlerAbstract{
	
		protected $_intHoriz = null;
		protected $_intHeight = null;
		protected $_intWidth  = null;
	
		public function __construct($strFileName, $strCssFileName = false, $strCopyright = false){
			
			// setting current file name
			$this->_setFileName($strFileName);
			
			// do we have a css file given
			if(is_string($strCssFileName)){
					$this->insertStyle($strCssFileName);
			}
			
			if(is_string($strCopyright)){
					$this->setCopyright($strCopyright);
			}
			
			return $this;
		}

		// returns true if svg is horizontal
		public function isHoriz(){
			
			if(!is_null($this->_intHoriz)){
					return $this->_intHoriz;
			}
			
			// getting xml object
			$oXml = $this->getXml();
			
			// setting default value to vertic
			$this->_intHoriz = false;
			
			if($oXml->attributes()->class == 'landscape'){
					$this->_intHoriz = true;
			}
			
			return $this->_intHoriz;			
		}
		
		public function getHeight(){
			
			if(!is_null($this->_intHeight)){
					return $this->_intHeight;
			}
			
			// getting xml object
			$oXml = $this->getXml();
			// getting value
			$this->_intHeight = intval($oXml->attributes()->height);
			
			return $this->_intHeight;
		
		}
		
		public function getWidth(){
			
			if(!is_null($this->_intWidth)){
					return $this->_intWidth;
			}
			
			// getting xml object
			$oXml = $this->getXml();
			// getting value
			$this->_intWidth = intval($oXml->attributes()->width);
			
			return $this->_intWidth;
			
		}
		
		// insert a css string into the xml
		public function insertStyleString($strCss, $intBefore = false){
			
			if(is_array($strCss)){
					$strCss = implode("\n", $strCss); 
			}
			
			// turn it into xml
			
			// loading svg xml		
			$oXml   = $this->getXml();
			$oStyle = $this->_getChild($oXml, 'style');
			
			// checking is style object exists
			if(!is_object($oStyle)){
				// no
				$oXmlCss = simplexml_load_string('<style type="text/css"><![CDATA['."\n".$strCss."\n".']]></style>');
				$oNeckArea = $this->_getChild($oXml, 'svg', 'neckArea');
				
				if(!$oNeckArea){
                    $oNeckArea = $this->_getChild($oXml, 'g', 'neckArea');
				}
				
				// inserting css
				$oTargetDom = dom_import_simplexml($oNeckArea);
				$oInsertDom = $oTargetDom->ownerDocument->importNode(dom_import_simplexml($oXmlCss), true);
				$oTargetDom->parentNode->insertBefore($oInsertDom, $oTargetDom);
				// done
				return $this;
			}
			
			// getting style as dom object
			$oDomStyle = dom_import_simplexml($oStyle);
			
			if(!$intBefore){
				// inserting content after existing css
				$oDomStyle->nodeValue.= "\n".$strCss;
			}
			else{
				// inserting content before existing css
				$oDomStyle->nodeValue = "\n".$strCss."\n".$oDomStyle->nodeValue;
			}
			
			// done
			return $this;
		}
		
		// import a css file into the svg
		public function insertStyle($strCssFile, $intBefore = true){
			
			if(!is_string($strCssFile) || empty($strCssFile)){
				throw new Exception("Invalid file name given. String expected !"); 
			}
			
			if(class_exists('Bootstrap', false)){
                // filtering path
                $strCssFile = Bootstrap::getPath($strCssFile);
			}
			
			if(!file_exists($strCssFile)){
					throw new Exception("The given file does not exists."); 
			}
			
			// loading Css
			$strCss  = file_get_contents($strCssFile);
			// inserting string into xml
			$this->insertStyleString($strCss, $intBefore);
			// done
			return $this;	
		}
		
		public function setBackground(){
		
			// loading svg xml		
			$oXml   = $this->getXml();
			// getting child
			$oNeckArea = $this->_getChild($oXml, 'svg', 'neckArea');
			
			if(!$oNeckArea){
                $oNeckArea = $this->_getChild($oXml, 'g', 'neckArea');
			}
			
			// getting the first horizontal line
			$oLineH1 = $this->_getChild($oNeckArea, 'line', 'hline1');
			// defining rect default properties
			$intRectX = 63;
			$intRectY = 85;
			$intRectWidth  = 177;
			$intRectHeight = 225; 
			
			// getting x1 of the first h line
			if(isset($oLineH1->attributes()->x1)){
					// updating X pos
					$intRectX = intval($oLineH1->attributes()->x1);
					if(!$this->isHoriz()){
						$intRectX-=2;
					}
			}
			
			// getting y1 of the first h line
			if(isset($oLineH1->attributes()->y1)){
					// updating Y pos
					$intRectY = intval($oLineH1->attributes()->y1);
			}
			
			// getting y2 of the first h line to find rect width
			if(isset($oLineH1->attributes()->x2)){
					// updating Wicth pos
					$intRectWidth = intval($oLineH1->attributes()->x2) - $intRectX;
			}
			
			// setting default last V line
			$oLastLineV = null;
			$intLastVlineIndex = 0;
			
			foreach($oNeckArea as $oLineChild){
				// only check lines objects
				if($oLineChild->getName() != 'line'){
						continue;
				}
				// checking if id exists and if it contains vline
				if(!isset($oLineChild->attributes()->id) || !$oLineChild->attributes()->id ||
				   strpos($oLineChild->attributes()->id, 'vline' ) === false){
						continue;
				}
				
				// extracting index;
				$intVlineIndex = intval(str_replace('vline', '' ,$oLineChild->attributes()->id));
				
				// extracting line  object
				if($intVlineIndex > $intLastVlineIndex){
						$oLastLineV = $oLineChild;
						$intLastVlineIndex = $intVlineIndex;
				}				
			}
			
			// could we extract x2 attribute and set rect height
			if($oLastLineV && isset($oLastLineV->attributes()->y2)){
				// getting y2 of the last v line to find rect height
				// updating Height
				$intRectHeight = intval($oLastLineV->attributes()->y2) - $intRectY;
			}
					
			// setting rect
			$oXmlRect = simplexml_load_string('<rect class="neckbackground" width="'.$intRectWidth.'" height="'.$intRectHeight.'" x="'.$intRectX.'" y="'.$intRectY.'"></rect>');
			
			// getting first line object
			$oLine = $this->_getChild($oNeckArea, 'line');
			// inserting rect
			$oTargetDom = dom_import_simplexml($oLine);
			$oInsertDom = $oTargetDom->ownerDocument->importNode(dom_import_simplexml($oXmlRect), true);
			$oTargetDom->parentNode->insertBefore($oInsertDom, $oTargetDom);
									
			return $this;
		}
		
		public function fixCirclesText($strArea = 'noteArea'){
				
			$oXml       = $this->getXml();
			$oNoteArea  = $this->_getChild($oXml, 'svg', $strArea);
			
			if(!$oNoteArea){
                $oNoteArea  = $this->_getChild($oXml, 'g', $strArea);
			}
			
			if(!$oNoteArea){
				// area is not set
				return $this;
			}
			
			foreach($oNoteArea as $oG){
			
				// only check g objects
				if($oG->getName() != 'g'){
						continue;
				}
			
				foreach($oG as $oChild){
					// only check text objects
					if($oChild->getName() != 'text'){
							continue;
					}
					
					// getting item as dom object
					$oDomChild = dom_import_simplexml($oChild);
					
					// setting adjust default value
					$intAdjustY  = -0.6;
					$intFontSize = 18;
					
					$intStringLen = strlen($oDomChild->textContent);
					$intHasSharp  = strpos($oDomChild->textContent, '#');
					
					if($intStringLen == 3 && !$intHasSharp){
						$intFontSize = 16;
						$intAdjustY  = -1.8; 
					}
					
					if($intStringLen > 3 || ($intStringLen > 2 && $intHasSharp)){
						$intFontSize = 12;
						$intAdjustY  = -3;
					}

					// updating Y
					$oChild->attributes()->y = round(floatval($oChild->attributes()->y) + $intAdjustY);
					// updating font-size
					//$oChild->attributes()->{'font-size'} = $intFontSize;
					// removing styling attributes
					unset($oChild->attributes()->{'font-family'});
					//unset($oChild->attributes()->{'fill'});
					//unset($oChild->attributes()->{'stroke'});
				}
			}
			
			return $this;
				
		}
		
		// extract string and fret pos data from circle container id,
		// and return an array or false
		protected function _extractPosFromCircleContainerId($strCtnId, $intIsHoriz = null){
			
				// checking strid
				if($strCtnId instanceof SimpleXMLElement){
					$strCtnId = $strCtnId->__toString();	
				}
				
				// checking of horiz param is set
				if(is_null($intIsHoriz)){
						// nop, using current image value
						$intIsHoriz = $this->isHoriz();
				}
				
				// extracting string and fret number
				if(preg_match('/Sn([0-9]+)Fn([0-9]+)/', $strCtnId, $arrMatches)){
					// vertic : f = 7-string && s = fret
					// horiz  : f = fret   && s = string
					return array(
						's' => (($intIsHoriz)? intval($arrMatches[1]) : 7-intval($arrMatches[2])),
						'f' => (($intIsHoriz)? intval($arrMatches[2]) : intval($arrMatches[1]))
					);	
				}
				
				return false;
		}
		
		// change text of a circle by css class
		public function setCircleTextByCssClass($mCssClass, $strText){
			
			// getting data of all current circles
			$arrCirclesDatas = $this->extractCirclesData();
			// checking class
			if(is_string($mCssClass)){
				$mCssClass = array($mCssClass);
			}
			
			// do we have some classes
			if(!is_array($mCssClass)){
				// nothing to do
				return $this;
			}
			
			// getting noteArea container
			$oXml = $this->getXml();
			$oNoteArea = $this->_getChild($oXml, 'svg', 'noteArea');
			
			if(!$oNoteArea){
                $oNoteArea = $this->_getChild($oXml, 'g', 'noteArea');
			}
			
			if(!$oNoteArea){
				return $this;
			}
						
			foreach($arrCirclesDatas as $arrCircleData){
				// default flag value
				$intChangeText = false;
			
				foreach($mCssClass as $strClass){
					if(in_array($strClass, $arrCircleData['class'])){
						$intChangeText = true;
						break;
					}
				}
				// can we change the text
				if(!$intChangeText){
					// no
					continue;
				}
				
				// getting noteArea container	
				$oCtn = $this->_getChild($oNoteArea, 'g', $arrCircleData['id']);	
				
				if(!$oCtn){
					continue;
				}
				
				// can we just change the text
				if(isset($oCtn->children()->text)){
					// yes
					$oCtn->children()->text = $strText;
					continue;
				}
				
				// no we have to create a new text object
				$oText = $oCtn->addChild('text', $strText);
				$oText->addAttribute('class', 'txt_note');
				
				// getting coords
				$arrCoords = $this->_getCircleCoords($arrCircleData['s'], $arrCircleData['f'], $this->isHoriz());
				// setting coords
				$oText->addAttribute('x', $arrCoords['textx']);
				$oText->addAttribute('y', $arrCoords['texty']);
				// setting other attributes
				//$oText->addAttribute('text-anchor', 'middle');
				//$oText->addAttribute('font-size', '22');
				//$oText->addAttribute('fill', 'white');
				//$oText->addAttribute('stroke', 'white');
				//$oText->addAttribute('font-family', 'Arial');
			}
			
			return $this;
		}
		
		// returns an array of circles datas (text, string, fret, css class:as array, id)
		public function extractCirclesData($oXml = null, $intIsHoriz = null){
			
			// do we have to use local xml object			
			if(is_null($oXml) ||!$oXml instanceof SimpleXMLElement){
				// yes
				// loading svg xml		
				$oXml     = $this->getXml();
			}
			
			// getting noteArea 
			$oNoteArea = $this->_getChild($oXml, 'svg', 'noteArea');
			
			if(!$oNoteArea){
                $oNoteArea = $this->_getChild($oXml, 'g', 'noteArea');
			}
			
			// getting noteArea children
			$arrCntns = $oNoteArea->children();
			
			
			// setting default result array
			$arrData  = array();
			
			foreach($arrCntns as $oCircleContainer){
				
				// do we have to skip the fretPos
				if(isset($oCircleContainer->attributes()->id) && $oCircleContainer->attributes()->id->__toString() == 'fretPos'){
					// yes
					continue;
				}
				
				//setting default datas array
				$arrCircleDatas = array(
					'text'  => false,
					'class' => false,
					's'		=> false,
					'f'		=> false,
					'id'	=> false,
				);
				
				// getting circle
				$oCircle  = $this->_getChild($oCircleContainer, 'circle');
				
				// getting attributes
				$oAttribs = $oCircle->attributes();
				// setting text flag
				$intHasText = isset($oCircleContainer->children()->text);
				
				// do we have a text
				if(isset($oCircleContainer->children()->text)){
					// yes
					$arrCircleDatas['text'] = $oCircleContainer->children()->text->__toString();
				}	
				
				// setting id
				$arrCircleDatas['id'] = $oCircleContainer->attributes()->id->__toString();
				
				// extracting pos
				$arrPos = $this->_extractPosFromCircleContainerId($oCircleContainer->attributes()->id, $intIsHoriz);
				// inserting data into result array
				$arrCircleDatas = array_merge($arrCircleDatas, $arrPos);
				
				if(isset($oCircle->attributes()->{'class'})){
					//echo "old classes : ".$oCircle->attributes()->{'class'}."\n";
					// getting object css classes
					$arrCircleDatas['class'] = explode(' ', $oCircle->attributes()->{'class'}->__toString());
				}	
				
				// adding datas
				$arrData[] = $arrCircleDatas;		
			}
			
			return $arrData;
		}
		
		// simple import of circle to have a simple subtram
		public function importSubTramCircles($strFileName){
			return $this->importCircles($strFileName, 'subTram', false, true, 0, true);
		}
		
		// import circles of $strFileName
		// $strCssClass = class to be applied to elements
		// $intKeepText = do we have to keep text with imported circles
		// $intBefore   = do we have to add circles before existing ones
		public function importCircles($strFileName, $strCssClass = 'subTram', $intKeepText = false, $intBefore = true, $intMoveFret = 0, $intKeepDuplicate = false){
			
			// filtering path
			$strFileName = Bootstrap::getPath($strFileName);
			
			if(!file_exists($strFileName)){
					echo 'Error : importCircles : File does not exist : '.$strFileName;
					return $this;
			}
			
			//echo "------------ new file import -------------\n";
			//echo "file : $strFileName\n";
			
			// loading content
			$strXmlGN = file_get_contents($strFileName);
			// parse xml
			$oXmlGN = simplexml_load_string($strXmlGN);
			// GN Horiz default value
			$intGNHoriz = false;
			// getting display type from main container
			if($oXmlGN->attributes()->class == 'landscape'){
					// imported file is horiz
					$intGNHoriz = true;
			}
						
			// getting parent container
			$oNoteAreaChildsGN = $this->_getChild($oXmlGN, 'svg', 'noteArea')->children();
			
			$oNoteArea = $this->_getChild($oXmlGN, 'svg', 'noteArea');
			
			if(!$oNoteArea){
                $oNoteArea = $this->_getChild($oXmlGN, 'g', 'noteArea');
			}
			
			$oNoteAreaChildsGN = $oNoteArea->children();
			
			// loading svg xml		
			$oXml       = $this->getXml();
			//$oNoteArea  = $this->_getChild($oXml, 'svg', 'noteArea');
			$oG         = $this->_getChild($oNoteArea, 'g');
			// does target has already some circles ?
			$intHasTargetCircles = (is_null($oG)) ? false : true;
			// setting target Dom
			$oTargetDom = ($intHasTargetCircles && $intBefore)? dom_import_simplexml($oG) : dom_import_simplexml($oNoteArea);
			
			// are canvas differents ?
			$intCanvasDiff = ($this->isHoriz() != $intGNHoriz) ? true : false;
			
			// list of childs to remove
			$arrChildsToRemove =  array();
			// checking Css
			$strCssClass = (is_string($strCssClass))? $strCssClass : '';
						
			foreach($oNoteAreaChildsGN as $oCircleContainer){
				
				//echo "--- new circle import -------------\n";
				
				// getting circle
				$oCircle  = $this->_getChild($oCircleContainer, 'circle');
				// getting attributes
				$oAttribs = $oCircle->attributes();
				// setting text flag
				$intHasText = isset($oCircleContainer->children()->text);
				// init local css
				$arrCircleCss = array($strCssClass);
								
				// do we have a circle that indicate fretpos
				if($oCircleContainer->attributes()->id->__toString() == 'fretPos'){
						// yes. This one should not be imported
						continue;
				}
				
				// setting default 
				$intString = false;
				$intFret   = false;
				
				// getting pos datas
				$arrContainerPosDatas = $this->_extractPosFromCircleContainerId($oCircleContainer->attributes()->id, $intGNHoriz);
				
				if(is_array($arrContainerPosDatas)){
						$intString = $arrContainerPosDatas['s'];
						$intFret   = $arrContainerPosDatas['f'];	
				}
				
				// are circle in a different conf
				if($intCanvasDiff && is_numeric($intString) && is_numeric($intFret)){
					// yes. We have to translate pos
					// getting coords for current svg
					$arrCoords = $this->_getCircleCoords($intString, $intFret, $this->isHoriz());
							
					$oAttribs->{'cx'} = $arrCoords['x'];
					$oAttribs->{'cy'} = $arrCoords['y'];
							
					if($intKeepText && $intHasText){
						$oTextAttribs = $oCircleContainer->children()->text->attributes();
						$oTextAttribs->{'x'} = $arrCoords['textx'];
						$oTextAttribs->{'y'} = $arrCoords['texty'];
					}
				}
					
				if($intMoveFret){
					// setting params
					$strCAttribs  = ($this->isHoriz())? 'cx':'cy';
					$strTAttribs  = ($this->isHoriz())? 'x':'y';
					$intMoveValue = ($this->isHoriz())? ($intMoveFret * 50) : ($intMoveFret * 45);
					// updating attrib value
					$oAttribs->{$strCAttribs} = floatval($oAttribs->{$strCAttribs}) + $intMoveValue;
					
					if($intKeepText && $intHasText){
						$oTextAttribs = $oCircleContainer->children()->text->attributes();
						$oTextAttribs->{$strTAttribs} = floatval($oTextAttribs->{$strTAttribs}) + $intMoveValue;
					}
					
					if(is_numeric($intFret)){
						// updating fret id
						$intFret+= $intMoveFret;
					}
				}
				
				// can we use text to set some specialize css class
				if($intHasText){	
					// getting circle text
					$strCircleText = $oCircleContainer->children()->text->__toString();
					// setting default prefix
					$strRelTextPrefix = 'd';
					// setting file Ref
					$strCssFileRef = str_replace('-', '', trim(basename($strFileName, '.svg')));				
										
					// getting clean text value
					$strCleanText = trim(strtolower(str_replace('#', '', $strCircleText)));
					$strCleanText = str_replace('b', '', $strCleanText);
					$strCleanText = str_replace(' ', '', $strCleanText);
					
					if(!is_numeric($strCleanText)){
							// do we have single note
							if(preg_match('/^[a-zA-Z]{1}$/', $strCleanText)){
								// yes
								$strRelTextPrefix = 'n';
							}
							else{
								// we may have a standard txt
								$strRelTextPrefix = 't';
							}
					}
					
					// adding new classes
					$arrCircleCss[] = $strRelTextPrefix.$strCleanText;
					$arrCircleCss[]	= $strRelTextPrefix.$strCleanText.'-'.$strCssFileRef;
					// could we had the string info
					if(is_numeric($intString)){
						// yes
						$arrCircleCss[] = $strRelTextPrefix.$strCleanText.'s'.$intString;
						$arrCircleCss[]	= $strRelTextPrefix.$strCleanText.'s'.$intString.'-'.$strCssFileRef;
					}												
				}
								
				// do we have to remove the fill attribute.
				if(is_string($strCssClass) && !empty($strCssClass)){
						// yes
						// removing color
						if(isset($oAttribs->fill)){
							unset($oAttribs->fill);
						}
				}
				
				// do we have to keep text
				if(!$intKeepText){
					// no
					if(isset($oCircleContainer->children()->text)){
							// removing child
							unset($oCircleContainer->children()->text);
					}
				}
				
				// do we have a local container with the same name
				$oLocalCircleContainer = $this->_getChild($oNoteArea, 'g', $oCircleContainer->attributes()->id);
				
				if(!is_null($oLocalCircleContainer)){
						//echo "local circle add to remove list\n";
						// adding object to the remove list
						$arrChildsToRemove[] = $oLocalCircleContainer;
						// getting local circle
						$oLocalCircle = $this->_getChild($oLocalCircleContainer, 'circle');
						
						if(isset($oLocalCircle->attributes()->{'class'}) && !empty($oLocalCircle->attributes()->{'class'})){
							//importing css coords classes from old child
							$arrCssCoordsOld = explode(' ', $oLocalCircle->attributes()->{'class'});
				
							foreach($arrCssCoordsOld as $strOldCssClass){
									if(!preg_match('/^[d][1-7]/', $strOldCssClass)){
											continue;
									}
									
									// adding old class
									$arrCircleCss[] = $strOldCssClass;
							}
						}
				}
							
				// could we update the id and the css classes
				if(is_numeric($intString) && is_numeric($intFret)){
					// yes
					//echo "old Id : ".$oCircleContainer->attributes()->id."\n";
					// updating id
					$oCircleContainer->attributes()->id = $this->_formatId($intString, $intFret);
					//echo "new Id : ".$oCircleContainer->attributes()->id."\n";
					// updating css
					$arrCircleCss[] = 's'.$intString;
					$arrCircleCss[] = 's'.$intString.'f'.$intFret;
				}
							
				// updating css
				if(isset($oCircle->attributes()->{'class'})){
					//echo "old classes : ".$oCircle->attributes()->{'class'}."\n";
					// getting object css classes
					$arrImportCss = explode(' ', $oCircle->attributes()->{'class'}->__toString());
					
					// merging with the existing and defined ones
					$arrCircleCss = array_keys(array_flip(array_merge($arrCircleCss, $arrImportCss)));					
				}
				
				// updating css
				$oCircle->attributes()->{'class'} = implode(' ', $arrCircleCss);

				// preparing node
				$oInsertDom = $oTargetDom->ownerDocument->importNode(dom_import_simplexml($oCircleContainer), true);
				
				if($intHasTargetCircles && $intBefore){
					// inserting node before other circles
					$oTargetDom->parentNode->insertBefore($oInsertDom, $oTargetDom);
				}
				else{
					// adding circles
					$oTargetDom->appendChild($oInsertDom);
				}
			}
			
			if(!$intKeepDuplicate && !empty($arrChildsToRemove)){
				$oDomNoteArea = dom_import_simplexml($oNoteArea);
				
				foreach($arrChildsToRemove as $oChildToRemove){
					
					if(is_null($oChildToRemove)){
							continue;
					}
					
					// converting to dom
					$oDomToRemove = $oDomNoteArea->ownerDocument->importNode(dom_import_simplexml($oChildToRemove), true);
					$oDomNoteArea->removeChild($oDomToRemove);
				}
			}
			
			return $this;
		}
		
		// add a neck pos number $strNum above fret $intFretPos
		// if the flag $intAddVisualMarks is set to true, the visual marks will be set automatically
		public function setNeckPosNum($strNum, $intFretPos = 1, $intAddVisualMarks = true, $strVisualMarksColor = 'white'){
		
			// loading svg xml		
			$oXml   = $this->getXml();
			// getting child
			$oChild = $this->_getChild($oXml, 'svg', 'neckInfoArea');
			
			if(!$oChild){
                $oChild = $this->_getChild($oXml, 'g', 'neckInfoArea');
			}
			
			// adding child
			if(!$oChild){
				$oChild = $oXml->addChild('svg');
				$oChild->addAttribute('id', 'neckInfoArea');				
			}
			
			if($intAddVisualMarks){
				$this->setVisualMarksFromNeckPos(((intval($strNum) - $intFretPos) + 1), $strVisualMarksColor);
			}
				
			$oG = $oChild->addChild('g');
			$oG->addAttribute('id', 'fretPos');
			$oG->addAttribute('class', 'noteGroup fretPos');
			
			// getting coords
			$arrCoords = $this->_getCircleCoords(6, $intFretPos, $this->isHoriz());
			
			$intX = $arrCoords['x'];
			$intY = $arrCoords['y'];
			
			if($this->isHoriz()){
				$intY+= 40;
			}
			else{
				$intX-= 40;
				$intY+= 8;	
			}
			
			$oText = $oG->addChild('text', $strNum); 
			$oText->addAttribute('class', 'txt_extra');
			$oText->addAttribute('x', $intX);
			$oText->addAttribute('y', $intY);
			
			return $this;
		}
		
		public function setCopyright($strTxt){
		
			// loading svg xml		
			$oXml   = $this->getXml();
			// getting child
			$oChild = $this->_getChild($oXml, 'svg', 'txtArea');
			
			if(!$oChild){
                $oChild = $this->_getChild($oXml, 'g', 'txtArea');
			}
			
			if(!$oChild){
					// required child is not found
					return $this;
			}
				
			$oG = $oChild->addChild('g');
			$oG->addAttribute('id', 'copyright');
			$oG->addAttribute('class', 'noteGroup');
			
			$oText = $oG->addChild('text', $strTxt);
			$oText->addAttribute('class', 'txt_copyrights');
			
			$intX = $this->getWidth() / 2;
			$intY = $this->getHeight() - 10;
			
			$oText->addAttribute('x', round($intX));
			$oText->addAttribute('y', round($intY));
									
			return $this;
		}
		
		public function setTitle($strTitle){
		
			// loading svg xml		
			$oXml   = $this->getXml();
			// getting child
			$oNameArea = $this->_getChild($oXml, 'svg', 'nameArea');
			
			// do we have the new svg version 
			if(!$oNameArea){
                // yes
                $oNameArea = $this->_getChild($oXml, 'g', 'nameArea');
			}
			
			$oText = $oNameArea->addChild('text', $strTitle);
			$oText->addAttribute('class', 'txt_title');
		
			$intX = $this->getWidth() / 2;
			$intY = 40;
			
			$oText->addAttribute('x', round($intX));
			$oText->addAttribute('y', round($intY));
		
			return $this;
		}
		
		// returns an array with all coords for a circle element
		protected function _getCircleCoords($intString, $intFret, $intHoriz = false){
				
			// string pos array
			$arrString = array(
				'horiz'  => array(75, 110, 145, 180, 215, 250),
				'vertic' => array(240, 205, 170, 135, 100, 65)
			); 
			
			// calculating pos
			$intX = ($intHoriz)? 80+(($intFret-1)*50) : $arrString['vertic'][$intString-1];
			$intY = ($intHoriz)? $arrString['horiz'][$intString-1] : 107.5+(($intFret-1)*45);
			
			return array(
				'x' => round($intX),
				'y' => round($intY),
				'textx' => round($intX),
				'texty' => round($intY+7.3333333333333),
			);				
		}
		
		// format id for objects
		protected function _formatId($intString, $intFret){
				
				// vertic : f = 7-string && s = fret
				// horiz  : f = fret   && s = string
				
				// in horiz mode, we have nothing to change
				if($this->isHoriz()){
					return 'Sn'.$intString.'Fn'.$intFret;	
				}
				
				// vertic mode
				return 'Sn'.$intFret.'Fn'.(7-$intString);
		}
		
		// add a circle on string $intString and fret $intFret
		public function addCircle($intString, $intFret, $strText = '', $strColor = 'black', $strTextColor = 'white'){
			
			$oXml       = $this->getXml();
			$oNoteArea  = $this->_getChild($oXml, 'svg', 'noteArea');
			
			if(!$oNoteArea){
                $oNoteArea  = $this->_getChild($oXml, 'g', 'noteArea');
			}
			
			// getting coords
			$arrCoords  = $this->_getCircleCoords($intString, $intFret, $this->isHoriz());
						
			// setting id
			$strId = $this->_formatId($intString, $intFret).'additional';
			// setting xml
			$strXml = '<g id="'.$strId.'" class="noteGroup">';
			$strXml.= '		<circle class="shape_circle" cx="'.$arrCoords['x'].'" cy="'.$arrCoords['y'].'" r="16" fill="'.$strColor.'"></circle>';
			$strXml.= '		<text class="txt_note" x="'.$arrCoords['textx'].'" y="'.$arrCoords['texty'].'" fill="'.$strTextColor.'" stroke="'.$strTextColor.'">'.$strText.'</text>';
			$strXml.= '</g>';
			
			// to xml object
			$oXmlCircle = simplexml_load_string($strXml);
			
			// inserting circle
			$oTargetDom = dom_import_simplexml($oNoteArea);
			$oInsertDom = $oTargetDom->ownerDocument->importNode(dom_import_simplexml($oXmlCircle), true);
			$oTargetDom->appendChild($oInsertDom);
			
			return $this;
		}
		
		public function setVisualMarksFromNeckPos($intNeckPos = 1, $strColor = 'white'){
						
			// defines the list of visuals marks
			$arrVisualMarksPos = array(3,5,7,9,12,15,17,19,21,24);
			$arrDoubleMarks    = array(12,24);
			$intFrets          = -1;
			
			// getting max pos from frets number
			$oXml       = $this->getXml();
			$oNeckArea  = $this->_getChild($oXml, 'svg', 'neckArea');
	
            if(!$oNeckArea){
                $oNeckArea  = $this->_getChild($oXml, 'g', 'neckArea');
            }
	
			// defining which type of line has to be count
			$strId = ($this->isHoriz())? 'vline':'hline';
			
			foreach($oNeckArea as $oLine){
					if(strpos($oLine->attributes()->id, $strId) === 0){
						$intFrets++;
					}
			}
			
			if($intFrets < 1){
					// not enought frets
					return $this;
			}
			
			foreach($arrVisualMarksPos as $intVisualPos){
				
				// is the pos usable
				if($intVisualPos < $intNeckPos){
						// no
						continue;
				}
			
				// do we have to stope the loop
				if($intVisualPos > $intFrets+$intNeckPos){
						// yes
						break;
				}
			
				// adding visual mark
				$this->addVisualMark((($intVisualPos - $intNeckPos) + 1), in_array($intVisualPos,$arrDoubleMarks), $strColor);				
			}
	
			// done
			return $this;
		}
		
		public function addVisualMark($mFret, $intDoubleMark = false, $strColor = 'white'){
			
			$oXml       = $this->getXml();
			$oNeckArea  = $this->_getChild($oXml, 'svg', 'neckArea');
			
			if(!$oNeckArea){
                $oNeckArea  = $this->_getChild($oXml, 'g', 'neckArea');
			}
			
			$oTargetDom = dom_import_simplexml($oNeckArea);
			
			// ensure $mFret to be an array
			if(!is_array($mFret)){
					$mFret = array($mFret);
			}
			
			foreach($mFret as $intFret){
					
				// setting double automaticaly for the 12th fret
				$intDouble = ($intFret == 12)? true : $intDoubleMark;
			
				// getting coords
				$arrCoords  = $this->_getCircleCoords(4, $intFret, $this->isHoriz());
				
				// setting vars 
				$intY1 = $arrCoords['y'];
				$intY2 = $arrCoords['y'];
				$intX1 = $arrCoords['x'];
				$intX2 = $arrCoords['x'];
				
				if($this->isHoriz()){
					$intY1 = ($intDouble)? $arrCoords['y']-9:$arrCoords['y']-18;
					$intY2 = $arrCoords['y']-27;
				}
				else{
					$intX1 = ($intDouble)? $arrCoords['x']+8 : $arrCoords['x']+17;
					$intX2 = $arrCoords['x']+26;
				}
				
				// setting id
				$strId = ($this->isHoriz())? 'Sn4Fn'.$intFret.'visual':'Sn'.$intFret.'Fn3visual';
				// setting xml
				
				$strXml = '<g id="'.$strId.'" class="visualGroup">';
				$strXml.= '		<circle class="shape_circle visual" cx="'.$intX1.'" cy="'.$intY1.'" r="5" fill="'.$strColor.'"></circle>';
				
				if($intDouble){
					$strXml.= '		<circle class="shape_circle visual" cx="'.$intX2.'" cy="'.$intY2.'" r="5" fill="'.$strColor.'"></circle>';
				}
				
				$strXml.= '</g>';
				
				// to xml object
				$oXmlCircle = simplexml_load_string($strXml);
				
				// inserting circle
				$oInsertDom = $oTargetDom->ownerDocument->importNode(dom_import_simplexml($oXmlCircle), true);
				$oTargetDom->appendChild($oInsertDom);
			}
			
			return $this;
		}
		
		// change the color of a string in order to hightlight it
		public function hightlightString($intString, $strColor = 'red'){
			
			// setting css
			$strCss = 'stroke:'.$strColor.';';
			
			if($this->isHoriz()){
				$strCss = '#hline'.$intString.'{'.$strCss.'}';
			}
			else{
				$strCss = '#vline'.(7-$intString).'{'.$strCss.'}';
			}
			
			$this->insertStyleString($strCss);
			
			return $this;
		}
		
		public function saveAs($strFileName = false){
			
			if(!is_string($strFileName) || empty($strFileName)){
				$strFileName = str_replace('.svg', '-new.svg', $this->_strFileName);
			}
			
			file_put_contents($strFileName, $this->getXml()->asXml());
			
			return $strFileName;
		}
		
		public function convert($strFileName = false, $intDensity = 100){
			
			// ensure to have the right file name
			if(!is_string($strFileName) || empty($strFileName)){
				$strFileName = $this->_strFileName;
			}
			
			// setting default value
			$strTmpFileName = $strFileName;
			
			if(is_string($strFileName)){
				// getting data
				$arrPath = pathinfo($strFileName);
			
				// getting working dir
				$strWorkingDir = $arrPath['dirname'];	
			
				if(!is_string($strWorkingDir) || empty($strWorkingDir) || $strWorkingDir == '/'){
					$strWorkingDir = '/tmp';
				}
				
				$strTmpFileName = $strWorkingDir.'/'.$arrPath['filename'].'.tmp';
			}

			// saving content
			$strTmpFileName = $this->saveAs($strTmpFileName);
			
			// ensure to have the right extension
			$strPngFile = str_replace('.svg', '.png', $strFileName);
			// generating file
			exec('/usr/bin/convert -density '.$intDensity.' '.$strTmpFileName.' '.$strPngFile);
			// removing temp file
			unlink($strTmpFileName);
			// done
			return $this;
		}
		
		public function toPng($strTitle = false, $strCssFileName = false, $intNeckPos = false, $strCopyright = false, $strImportCircles = false, $strFileName = false, $intDensity = 100){
			
			if(is_string($strTitle) && !empty($strTitle)){
				$this->setTitle($strTitle);
			}
			
			if(is_string($strCssFileName) && !empty($strCssFileName)){
				$this->insertStyle($strCssFileName, true);
			}
			
			if(is_numeric($intNeckPos)){
				$this->setNeckPosNum($intNeckPos);
			}
			
			if(is_string($strCopyright) && !empty($strCopyright)){
				$this->setCopyright($strCopyright);
			}
			
			if(is_string($strImportCircles) && !empty($strImportCircles)){
				$this->importCircles($strImportCircles);
			}
			
			$this->setBackground();
			$this->fixCirclesText();
			$this->fixCirclesText('neckInfoArea');
									
			$this->convert($strFileName, $intDensity);
			
			return $this;
		}
}
