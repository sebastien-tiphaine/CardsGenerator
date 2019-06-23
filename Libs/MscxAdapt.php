<?php

require_once(__DIR__.'/MsStyleHandlerAbstract.php');
require_once(__DIR__.'/IniHandler.php');
require_once(__DIR__.'/MsCssAdapt.php');

class MscxAdapt extends MsStyleHandlerAbstract{
	
	const TEXT_FOOTER = 'TEXT_FOOTER';
	const TEXT_HEADER = 'TEXT_HEADER';
	
	// tonal picth class
	protected $_arrTpc = array(
		-1 => 'fbb',  6 => 'fb', 13 => 'f', 20 => 'f#', 27 => 'f##',
		 0 => 'cbb',  7 => 'cb', 14 => 'c', 21 => 'c#', 28 => 'c##',
		 1 => 'gbb',  8 => 'gb', 15 => 'g', 22 => 'g#', 29 => 'g##',
		 2 => 'dbb',  9 => 'db', 16 => 'd', 23 => 'd#', 30 => 'd##',
		 3 => 'abb',  10 => 'ab', 17 => 'a', 24 => 'a#', 31 => 'a##',
		 4 => 'ebb',  11 => 'eb', 18 => 'e', 25 => 'e#', 32 => 'e##',
		 5 => 'hbb',  12 => 'hb', 19 => 'h', 26 => 'h#', 33 => 'h##'	
	);
	
	// list of loaded user styles and 
	// systext changes
	protected $_arrTextStyles = array();
	
	// Mapping indicating where to find items
	protected $_arrXmlMapping = array(
		// from root
		'Style'   => 'Score/Style',
		'Staff'   => 'Score/Staff',
		'Measure' => 'Score/Staff/Measure',
		'Harmony' => 'Score/Staff/Measure/voice/Harmony',
		'KeySig'  => 'Score/Staff/Measure/voice/KeySig',
		'Chord'   => 'Score/Staff/Measure/voice/Chord',
		'Note'    => 'Score/Staff/Measure/voice/Chord/Note',
	);
	
	// list of textes
	protected $_arrTexts = array();
	
	// musescore style sheet
	protected $_oMcss = null;
	
	// margin of boxes
	protected $_arrBoxesMargins = array();
	
	// constructor. 
	// string $strSrc xml file name
	public function __construct($strSrc){
			
		// setting current file name
		$this->_setFileName($strSrc);
		// done
		return $this;
	}
	
	// returns score object
	protected function _getScore(){
		// getting xml object
		$oXml = $this->getXml();
		// getting score xml object
		return $this->_getChild($oXml, 'Score');
	}
	
	// returns one or more staff
	protected function _getStaff($intId = false){
	
		// do we have to returns all staffs
		if(!$intId){
			// yes
			return $this->xpath('#Staff#');
		}
		
		// getting staffs
		$arrStaffs = $this->xpath('#Staff#');
		
		// we have to return a single staff
		foreach($arrStaffs as $oStaff){
			// do we have a valid element
			if(!$oStaff instanceof SimpleXMLElement){
				// no
				continue;
			}
			
			if(isset($oStaff->attributes()->id) && $oStaff->attributes()->id == $intId){
				return $oStaff;
			}
		}
		
		// nothing found
		return false;
	}
	
	// returns all mesures form all staffs
	public function _getAllMeasures(){
		return $this->xpath('#Measure#');
	}
	
	// returns all chords from all measures
	protected function _getAllChords(){
		return $this->xpath('#Chord#');
	}
	
	// returns all harmony items from all measures
	protected function _getAllHarmonies(){
		return $this->xpath('#Harmony#');
	}
	
	// returns all Notes items from all measures
	protected function _getAllNotes(){
		return $this->xpath('#Note#');
	}
	
	// add a style
	public function addTextStyle($strName, $arrStyle){
		
		// checking name
		if(!is_string($strName) || empty($strName)){
			throw new Exception('Style name is expected to be a string.');
		} 
		
		// checking datas
		if(!is_array($arrStyle) || empty($arrStyle)){
			throw new Exception('Missing style definition.');
		}
		
		if(!$this->_isSystemTextStyle($strName)){
			// inserting name
			$arrStyle['Name'] = $strName;
		}
		
		// adding style to the main array
		$this->_arrTextStyles[$strName] = $arrStyle;
	
		// done
		return $this;
	}
	
	// returns true if style $strName is set
	public function hasTextStyle($strName){ 
		
		// do we have a local style
		if(isset($this->_arrTextStyles[$strName])){
			return true;
		}
		
		// do we have the style set in current xml
		if(parent::hasTextStyle($strName)){
			return true;
		}
		
		// do we have a musescore css
		if(!$this->_oMcss instanceof MsCssAdapt){
			// no
			return false;
		}
		
		// trying to get the style from linked css
		return $this->_oMcss->hasTextStyle($strName);
	}
	
	// returns data of a given style
	public function getTextStyle($strName){
		
		// do we have a style
		if(!$this->hasTextStyle($strName)){
			// no
			throw new Exception('no style named '.$strName);
		}
		
		// list of style datas
		$arrStyleDatas = array();
		
		// do we have a style in the MsCss
		if($this->_oMcss instanceof MsCssAdapt){
			// getting data from the MsCss
			$arrStyleDatas = array_merge($arrStyleDatas, $this->_oMcss->getTextStyle($strName));	
		}
		
		// do we have some properties in the current xml
		$arrStyleDatas = array_merge($arrStyleDatas, parent::getTextStyle($strName));
		
		// do we have properties in the local style
		if(array_key_exists($strName, $this->_arrTextStyles)){
			$arrStyleDatas = array_merge($arrStyleDatas, $this->_arrTextStyles[$strName]);
		}
		
		return $arrStyleDatas;
	}
	
	// returns a property of a given style
	// returns $mDefault if style does not exists or $strProp is not set
	public function getTextStyleProp($strName, $strProp, $mDefault = false){
		
		if(!$this->hasTextStyle($strName)){
			return $mDefault;
		}
		
		// getting style
		$arrStyle = self::getTextStyle($strName);
		
		if(!is_array($arrStyle) || !array_key_exists($strProp, $arrStyle)){
			return $mDefault;
		}
		// done
		return $arrStyle[$strProp];
	}
	
	// insert all declared styles into current xml
	protected function _saveTextStyles(){
	
		// do we have styles
		if(!is_array($this->_arrTextStyles) || empty($this->_arrTextStyles)){
			// no
			return $this;
		}
			
		foreach($this->_arrTextStyles as $strName => $arrStyle){
			// inserting nodes
			$this->setTextStyle($strName, $arrStyle);	
		}
		
		// done
		return $this;
	}
	
	// import a style sheet in ini format
	// all contained datas will be imported into
	// the final xml
	public function importIniStyle($strFile){
		
		// reading file content
		$oIni = new IniHandler($strFile);
		// getting styles names
		$arrStylesNames = $oIni->getPrimaries();

		foreach($arrStylesNames as $strName){
			// extracting params
			$arrStyle = $oIni->{'^'.$strName};
			// adding style
			$this->addTextStyle($strName, $arrStyle);
		}
		
		// done
		return $this;
	}
	
	// import a style sheet in Mss (Musescore Xml StyleSheet) format
	public function importMsStyles($strFile){
		
		// loading file
		$this->_oMcss = new MsCssAdapt($strFile);
		// done
		return $this;
	}
	
	// return the value of the text box follwing the text size in spatium unit
	protected function _getTextBoxHeight($intFontSize, $intLines = 1, $strUnit = self::UNIT_SPATIUM){
		// updating textsize to avoid shorten calculation error
		$intFontSize+= $intFontSize*.15;
		// setting text height
		$intTextHeight = $intFontSize * $intLines;
		// adding a little margin
		$intTextHeight+= ($intTextHeight*.25)*$intLines;
		// returns value
		return $this->unitConvert($intTextHeight, self::UNIT_POINT, $strUnit);
	}
	
	// increase the current page height with $intHeight and returns the new value.
	// $strUnit is the unit in which the $intHeight param is given
	protected function _increasePageHeight($intHeight, $strUnit = self::UNIT_INCH){
		
		// getting pageHeight unit
		$strPageUnit = $this->getPropertyUnit('pageHeight');
		// getting default page height in the right unit
		$intDefault = $this->unitConvert(2.20197, self::UNIT_INCH, $strPageUnit); 
		
		// getting current page height
		$intPageHeight = $this->getProperty('pageHeight', $intDefault, $strPageUnit);
				
		// updating page height
		$intPageHeight+= $this->unitConvert($intHeight, $strUnit, $strPageUnit);

		// setting value to xml
		$this->setProperty('pageHeight', $intPageHeight, $strPageUnit);
		// done
		return $intPageHeight;
	}
	
	// return margins total height for a Vbox
	protected function _getVBoxMarginsHeight($intMeasure = 0, $strUnit = self::UNIT_SPATIUM){
		
		// getting default unit
		$strFrameUnit = $this->getPropertyUnit('systemFrameDistance');
		
		// do we already have loaded margins
		if(!isset($this->_arrBoxesMargins['VBOX'])){
			
			// setting default value
			$intDefault = $this->unitConvert(7, self::UNIT_SPATIUM, $strFrameUnit); 
			
			// no
			// setting main cache
			$this->_arrBoxesMargins['VBOX'] = array();
			
			// setting margin Down
			$this->_arrBoxesMargins['VBOX']['Down'] = $this->getProperty('systemFrameDistance', $intDefault, $strFrameUnit);
			$this->_arrBoxesMargins['VBOX']['Down']+= $this->_arrBoxesMargins['VBOX']['Down']*.3;
			
			// setting margin Up
			$this->_arrBoxesMargins['VBOX']['Up'] = $this->getProperty('frameSystemDistance', $intDefault, $strFrameUnit);
			$this->_arrBoxesMargins['VBOX']['Up']+= $this->_arrBoxesMargins['VBOX']['Up']*.3;
		}
		
		// do we have to return only the margin down
		if($intMeasure == 0){
			// yes
			return $this->unitConvert($this->_arrBoxesMargins['VBOX']['Down'], $strFrameUnit, $strUnit);
		}
		
		// no. All boxes but the first have two margins
		return $this->unitConvert(($this->_arrBoxesMargins['VBOX']['Down'] + $this->_arrBoxesMargins['VBOX']['Up']), $strFrameUnit, $strUnit);
	}
	
	// add a copyright text
	public function setCopyright($strText){
		
		// adding text to pages footer
		$this->setProperty('evenFooterC', $strText, true);
		$this->setProperty('oddFooterC', $strText, true);
		
		// setting flags
		$this->setProperty('footerFirstPage', 1);
		$this->setProperty('footerOddEven', 1);
		$this->setProperty('showFooter', 1);
		
		// getting text size to define the minimum margin required
		$intSize   = $this->getTextStyleProp('footer', 'FontSize', 8);
		// getting unit in with margins are set
		$strMarginsUnit = $this->getPropertyUnit('pageEvenBottomMargin');
		
		// getting margin height in MarginUnit
		$intMargin = $this->_getTextBoxHeight($intSize, count(explode("\n", $strText)), $strMarginsUnit);
			
		// setting page height flag
		$intIncreasePageH = false;
		
		// getting even margin
		$intEvenMargin = $this->getProperty('pageEvenBottomMargin', 0);
		
		// is the margin lower than required
		if(floatval($intEvenMargin) < $intMargin){
			// yes
			$intIncreasePageH = true;
			// updating the margin
			$this->setProperty('pageEvenBottomMargin', $intMargin);
		}
		
		// getting even margin
		$intOddnMargin = $this->getProperty('pageOddBottomMargin', 0);
		
		// is the margin lower than required
		if(floatval($intOddnMargin) < $intMargin){
			// yes
			$intIncreasePageH = true;
			// updating the margin
			$this->setProperty('pageOddBottomMargin', $intMargin);
		}
		
		// do we have to update page height
		if($intIncreasePageH){
			// yes
			$this->_increasePageHeight($intMargin, $strMarginsUnit);
		}	
		
		// done
		return $this;
	}
	
	// adds VBox with a text content after measure $intMeasure and styled with $strStyleName
	public function addVBox($strText, $strStyleName, $intMeasure = 0){
		
		if(!is_string($strText) || empty($strText)){
			throw new Exception('No text given. String Expected');
		}
		
		if(!is_string($strStyleName) || empty($strStyleName)){
			throw new Exception('No style given. String Expected');
		}
		
		$this->_arrTexts[] = array(
			'text'    => str_replace('<br>', "\n", $strText),
			'style'   => $strStyleName,
			'measure' => $intMeasure,
			'type'    => 'VBox'
		);
		
		// done
		return $this;
	}
	
	// add a title
	public function setTitle($strText, $strStyleName = 'title'){
		$this->addVBox($strText, $strStyleName, 0);
	}
	
	// add a text box after a measure
	public function addText($strText, $strStyleName, $intMeasure = 0){
		$this->addVBox($strText, $strStyleName, $intMeasure);
	}
	
	// inserts a VBox and returns its height in spatium
	// the $strUnit param is the unit in which box height is given
	protected function _setVBox($intHeight, $strContent = '', $intMeasure = 0, $strUnit = self::UNIT_SPATIUM){
		
		// ensure height to be in the right unit
		$intHeight = $this->unitConvert($intHeight, $strUnit, self::UNIT_SPATIUM);
		
		// setting xml
		$oXml = simplexml_load_string('<VBox><height>'.str_replace(',', '.', $intHeight.'').'</height>'.$strContent.'</VBox>');
			
		// is the box placed before the first measure
		if($intMeasure == 0){
			// yes
			// getting first measure of the staff
			// Vbox will have to be inserted just before
			$oMeasure = $this->xpath('#Staff#[@id=\'1\']/Measure[1]', true);
			
			// do we have something usable
			if(!$oMeasure instanceof SimpleXMLElement){
				// no
				throw new Exception('Score does not have any measure in the first staff !');
			}
			
			// do we already have boxes before
			$arrPrecBoxes = $this->xpath('#Staff#[@id=\'1\']/Measure[1]/preceding-sibling::VBox', false);
			// how many boxes do we already have before the first measure
			$intPrecBoxes = (is_array($arrPrecBoxes) && !empty($arrPrecBoxes))? count($arrPrecBoxes):0;
			
			if(!$intPrecBoxes){
				// no
				// updating box height with margin
				$intHeight+= $this->_getVBoxMarginsHeight(0, self::UNIT_SPATIUM);
			}else{
				// updating box with a collapse margin
				
				// setting default coef that ensure boxes height
				// to have the right margin size.
				$intCoef = .408;
				
				if($intPrecBoxes > 1){
					$intCoef+= .128*($intPrecBoxes -1); 
				}
				
				$intHeight+= $this->_getVBoxMarginsHeight(0, self::UNIT_SPATIUM)*$intCoef; 
			}
			
			// adding child
			$this->_insertBefore($oXml, $oMeasure);
			// done
			return $intHeight;
		}
		
		// is the box placed after a measure
		if($intMeasure > 0){
			//yes
			// getting the measure object
			$oMeasure = $this->xpath('#Staff#[@id=\'1\']/Measure[@number=\''.$intMeasure.'\']', true);
			
			if(!$oMeasure instanceof SimpleXMLElement){
				throw new Exception('Score does not have any measure number '.$intMeasure.' !');
			}
			
			// adding child
			$this->_insertAfter($oXml, $oMeasure);
			
			// updating height
			$intHeight+= $this->_getVBoxMarginsHeight($intMeasure);
			
			// done
			return $intHeight;
		}
		
		// the box have to be inserted at the end of the staff
		$oStaff = $this->xpath('#Staff#[@id=\'1\']', true);
		// do we have something usable
		if(!$oStaff instanceof SimpleXMLElement){
			// no
			throw new Exception('Score does have any staff with id 1 !');
		}
		
		// addin box
		$this->_addNode($oXml, $oStaff);
		// updating height
		$intHeight+= $this->_getVBoxMarginsHeight($intMeasure);
		// done
		return $intHeight;
	}
	
	// sets version of musecore for xml interpretation
	protected function _setVersionRevision(){
		
		// getting xml object
		$oXml = $this->getXml();
		// setting values : TODO : update
		$oXml->children()->{'programVersion'} = '3.0.0';
		$oXml->children()->{'programRevision'} = '3543170';
		// done
		return $this;
	}
	
	// insert all text box if any
	protected function _setText(){
	
		// do we have texts
		if(!is_array($this->_arrTexts) || empty($this->_arrTexts)){
			// no
			return $this;
		}
		
		// total height of which page have to be augmented
		$intTotalHeight = 0;
		
		foreach($this->_arrTexts as $arrData){
			
			// getting style name
			$strStyle = $arrData['style'];
			
			// do we have a style
			if(!$this->hasTextStyle($strStyle)){
				// no
				throw new Exception('Style '.$strStyle.' is not set !');
			}
			
			// getting text size
			$intSize = $this->getTextStyleProp($strStyle, 'FontSize', 16, self::UNIT_POINT);
			// getting box height
			$intHeight = $this->_getTextBoxHeight($intSize, count(explode("\n", $arrData['text'])), self::UNIT_SPATIUM);
			// setting box content
			$strBoxContent = '<Text><style>'.$strStyle.'</style><text>'.$arrData['text'].'</text></Text>';
			
			if($arrData['type'] == 'VBox'){
				// updating Box height
				$intHeight = $this->_setVBox($intHeight, $strBoxContent, $arrData['measure'], self::UNIT_SPATIUM);
			}
			else{
				throw new Exception('Unsupported type : '.$arrData['type']);
			}
			
			// updating total height
			$intTotalHeight+=$intHeight;
		}
			
		// updating page height
		$this->_increasePageHeight($intTotalHeight, self::UNIT_SPATIUM);

		// done
		return $this;
	}
	
	// sets the keySig for all the measures or only the measure number $intMeasure
	public function setKeySig($intAccidentals, $intMeasure = false){
		
		// checking value
		if(!is_numeric($intAccidentals)){
			throw new Exception('Integer expected !');
		}
		
		// getting staffs
		$arrStaffs = $this->xpath('#Staff#');
		
		foreach($arrStaffs as $oStaff){
			
			// getting all staff measures
			$arrMeasures = $oStaff->xpath('Measure');
			
			foreach($arrMeasures as $intNum => $oMeasure){
			
				// do we have the right number
				if($intMeasure && $intNum+1 != $intMeasure){
					// no
					continue;
				}
					
				// getting keySig object
				$arrKeySig = $oMeasure->xpath('voice/KeySig');
				// setting default KeySig Object
				$oKeySig   = null;
				// can we extract the KeySig object
				if(is_array($arrKeySig) && !empty($arrKeySig)){
					$oKeySig = current($arrKeySig);
				}
						
				// checking object
				if(!$oKeySig instanceof SimpleXMLElement){
					// the measure has no KeySig
					// do we have to insert a keySig child
					if((!$intMeasure && $intNum === 0) || $intMeasure){
						// setting new node
						$oXmlKeySig = simplexml_load_string('<KeySig><accidental>'.$intAccidentals.'</accidental></KeySig>');
						// inserting node
						$this->_insertAsFirstChild($oXmlKeySig, $oMeasure);
						// done
						return $this;
					}
					// no
					continue;
				}
				
				// setting accidental
				$oKeySig->children()->{'accidental'} = $intAccidentals;
			}
			
		}
		
		// done
		return $this;
	}
	
	// transpose all KeySig of the score using given data array
	// the array must be like : [intCurrentAccidentals] => intTransposedAccidentals 
	public function transposeKey($arrTransposeData, $intDefaultKey = false){
		
		// getting measures
		//$arrMeasures = $this->_getAllMeasures();
		//$int //TODO: verifier qu'il y ai une keysign sur toutes les 1eres mesures.
		
		// getting staffs
		$arrStaffs = $this->xpath('#Staff#');
		
		foreach($arrStaffs as $oStaff){
			
			// getting all staff measures
			$arrMeasures = $oStaff->xpath('Measure');
			
			foreach($arrMeasures as $intNum => $oMeasure){
				
				// getting keySig object
				$arrKeySig = $oMeasure->xpath('voice/KeySig');
				// setting default KeySig Object
				$oKeySig   = null;
				// can we extract the KeySig object
				if(is_array($arrKeySig) && !empty($arrKeySig)){
					$oKeySig = current($arrKeySig);
				}
				
				// do we have a measure with a keySig
				if(!$oKeySig instanceof SimpleXMLElement){
					// no
					// do we have a default key
					if(!is_numeric($intDefaultKey)){
						//no
						continue;
					}
					
					// are we on the first measure
					if($intNum !== 0){
						// no
						continue;
					}
					
					// yes
					// KeySign must be added to the measure
					$this->setKeySig($intDefaultKey, 1);
					// getting created object to transpose it
					// getting keySig object
					$arrKeySig = $oMeasure->xpath('voice/KeySig');
					$oKeySig   = null;
					// can we extract the KeySig object
					if(is_array($arrKeySig) && !empty($arrKeySig)){
						$oKeySig = current($arrKeySig);
					}
					
					// do we have a KeySig
					if(!$oKeySig instanceof SimpleXMLElement){
						// no
						throw new Exception('Not able to found KeySig Object after making new node');
					}
					
				}
				
				// getting value
				$intAccidentals = intval($oKeySig->children()->{'accidental'});
				
				// do we have the trasnspose datas
				if(!isset($arrTransposeData[$intAccidentals])){
					// no
					throw new Exception('No transposition data for value : '.$intAccidentals);
				}
				
				// yes
				// setting the new value
				$oKeySig->children()->{'accidental'} = $arrTransposeData[$intAccidentals];
			}// end of foreach measure
		}// end of foreach staff
		
		// done
		return $this;
	}
	
	// transpose a tpc value according to transposeData
	protected function _tpcTranspose($intTpc, $arrTransposeData){
		
		// do we have a valid tpc
		if(!is_numeric($intTpc)){
			// no
			throw new Exception('Numerical value expected for TPC');
		}
		
		// do we have a valid data array
		if(!is_array($arrTransposeData) || empty($arrTransposeData)){
			// no
			throw new Exception('Array expected for arrTransposeData');
		}
		
		// getting note name
		$strNoteName = $this->_arrTpc[$intTpc];
				
		// do we have the note name in transpose data array
		if(!isset($arrTransposeData[$strNoteName])){
			// no
			throw new Exception($strNoteName.' cannot be found as a key of transpose data array');
		}
		
		// yes
		// getting transposed name
		$strTransNote = $arrTransposeData[$strNoteName];
		
		// is the transposed note in the tpc array
		if(!in_array($strTransNote, $this->_arrTpc)){
			// no
			throw new Exception($strTransNote.' cannot be found in tpc array');
		}
		
		// getting new tpc
		return array_search($strTransNote, $this->_arrTpc);
	}
	
	// transpose all Harmony items
	public function transposeHarmonies($arrTransposeData){
			
		// getting all Harmonies
		$arrHarmonies = $this->_getAllHarmonies();
		
		// do we have harmonies	
		if(!is_array($arrHarmonies) || empty($arrHarmonies)){
			// no
			return $this;
		}
			
		foreach($arrHarmonies as $oHarmony){
			
			// do we have an object
			if(!$oHarmony instanceof SimpleXMLElement){
				// no
				continue;
			}
			
			// do we have a root item
			if(!isset($oHarmony->children()->root)){
				// no
				continue;
			}
			
			// yes			
			// extracting tpc value
			$intTpc = intval($oHarmony->children()->root);
			// transposing value
			$intTransTpc = $this->_tpcTranspose($intTpc, $arrTransposeData);
			// updating tpc
			$oHarmony->children()->root = $intTransTpc;	
		}
			
		// done
		return $this;	
	}
	
	
	// transpose all notes of the score up or down $intHalfTones halftone
	// and using  $arrTransposeData for enharmony
	public function transposeNotes($arrTransposeData, $intHalfTones){
		
		// getting all notes
		$arrNotes = $this->_getAllNotes();

		// do we have notes
		if(!is_array($arrNotes) || empty($arrNotes)){
			// no
			return $this;
		}

		// transposing all notes
		foreach($arrNotes as $oNote){

			// do we have an object
			if(!$oNote instanceof SimpleXMLElement){
				// no
				continue;
			}
			
			// updating fret
			$oNote->children()->fret = intval($oNote->children()->fret) + $intHalfTones;
			// updating pitch
			$oNote->children()->pitch = intval($oNote->children()->pitch) + $intHalfTones;
				
			// extracting tpc value
			$intTpc = intval($oNote->children()->tpc);
			// transposing value
			$intTransTpc = $this->_tpcTranspose($intTpc, $arrTransposeData);
			// updating tpc
			$oNote->children()->tpc = $intTransTpc;	
		}		
	
		// done
		return $this;		
	}
	
	// save current xml content as $strFileName
	// and returns filename
	public function saveAs($strFileName = false){
			
		if(!is_string($strFileName) || empty($strFileName)){
			$strFileName = str_replace('.mscx', '-new.mscx', $this->_strFileName);
		}
		// setting version
		$this->_setVersionRevision();
		// inserting styles
		$this->_saveTextStyles();
		// inserting texts
		$this->_setText();
		// writing file
		file_put_contents($strFileName, $this->getXml()->asXml());
		// returns current file name
		return $strFileName;
	}
	
	// convert current xml score to a png and result a list of generated files
	public function convert($strFileName = false, $intDensity = 100){
		
		// do we have a filename given
		if(!is_string($strFileName) || empty($strFileName)){
			// no
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
			
			$strTmpFileName = $strWorkingDir.'/'.$arrPath['filename'].'-tmp.mscx';
		}

		// saving content
		$strTmpFileName = $this->saveAs($strTmpFileName);
		
		// default style value
		$strStyle = '';
		
		// do we have a style mss
		if($this->_oMcss instanceof MsCssAdapt){
			// yes
			$strStyle = ' --style '.$this->_oMcss->getFileName();
		}
		
		// setting temp file flag for removing
		$intRmTemp = true;
		
		// ensure to have the right extension
		$strPngFile = str_replace('.mscx', '.png', $strFileName);
		// generating file
		echo "\n#### Musescore output :\n";
		exec('/usr/bin/mscore --export-to '.$strPngFile.' --image-resolution '.$intDensity.$strStyle.' '.$strTmpFileName);
	
		// setting default result array
		$arrFiles = array($strPngFile);
	
		// do we have multiple file dues to pagination error
		if(!file_exists($strPngFile)){
			// may be
			// testing if page one can be found
			$strPagePrefix = str_replace('.png', '-', $strPngFile);
			// getting files list
			$arrFiles = glob($strPagePrefix.'[0-9]\.png');
			
			if(!is_array($arrFiles) || empty($arrFiles)){
				echo "\n       -> WARN : no file has been generated !";
			}
			else{
				echo "\n       -> files generated : ".count($arrFiles);
			}
		}
		
		// removing temp file
		/*if($intRmTemp){
			unlink($strTmpFileName);
		}*/
		// done
		return $arrFiles;
	}
}
	
