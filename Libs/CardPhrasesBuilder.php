<?php

require_once(__DIR__.'/CardGenerator.php');
require_once(__DIR__.'/CardText.php');
require_once(__DIR__.'/CardSkeleton.php');
require_once(__DIR__.'/MultiSkeleton.php');

class CardPhrasesBuilder extends TemplateRenderingLogic{

	// list of supported chord types
	protected $_arrChordsType = array(
		CardGenerator::MAJOR => array(1 => '7M', 2 => 'm7', 3 => 'm7', 4 => '7M', 5 => '7', 6 => 'm7', 7 => 'm7b5'), 
	);

	// mapping of all chord note to the scale degree
	/*protected $_arrChordScaleNote = array(
		1 => array(1 => 1, 3 => 3, 5 => 5, 7 => 7, 9 => 2, 11 => 4, 13 => 6),
		2 => array(1 => 2, 3 => 4, 5 => 6, 7 => 1, 9 => 3, 11 => 5, 13 => 7),
		3 => array(1 => 3, 3 => 5, 5 => 7, 7 => 2, 9 => 4, 11 => 6, 13 => 1),
		4 => array(1 => 4, 3 => 6, 5 => 1, 7 => 3, 9 => 5, 11 => 7, 13 => 2),
		5 => array(1 => 5, 3 => 7, 5 => 2, 7 => 4, 9 => 6, 11 => 1, 13 => 3),
		6 => array(1 => 6, 3 => 1, 5 => 3, 7 => 5, 9 => 7, 11 => 2, 13 => 4),
		7 => array(1 => 7, 3 => 2, 5 => 4, 7 => 6, 9 => 1, 11 => 3, 13 => 5),
	);*/

	// list of chords and notes that have to be used to
	// generate the phrases
	protected $_arrChordNote = array();

	// list of generated templates
	protected $_arrTemplates = array();

	// image that is used as scale ref
	protected $_mScaleBaseImage = false;

	// default scale type
	protected $_intScaleType = CardGenerator::MAJOR;

	// number of phrases to be generated
	protected $_intPhraseNum = 3;

	// skeletons vars
	protected $_arrVars = array();

	// skeletons values
	protected $_arrValues = array();

	// card skeleton
	protected $_oSkeleton = null;

	// default flag for getAllPhrases pointer
	protected $_intMoveStackPointer = true;

	// stores a builder object
	public static function storeBuilder($strId, $oBuilder){
		return parent::storeObject($strId, $oBuilder);
	}

	// returns true if CardPhrasesBuilder $strId exists
	public static function hasBuilder($strId){
		return parent::hasObject($strId);
	}

	//returns CardPhrasesBuilder with id $strId
	public static function getBuilder($strId){
		return parent::getObject($strId);
	}

	// constructor
	public function __construct($mSkeleton, $mScaleBaseImg, $intScaleType, $arrChordNotes = false, $intNum = 3, $arrVars = false, $arrValues = false, $intMoveStackPointer = true){
		
		// init storage
		$this->_oSkeleton = new MultiSkeleton();
		// adding skeletons to storage
		$this->_oSkeleton->addSkeleton($mSkeleton);
		// ensure skeletons to be locked so no change can be
		// applyied on originals values
		$this->_oSkeleton->setLocked();
		// preparing object for rendering
		$this->_oSkeleton->prepareRendering($mScaleBaseImg);

		// setting image
		$this->setScaleImage($mScaleBaseImg);

		// setting chord notes
		if($arrChordNotes) $this->setChordNote($arrChordNotes);
		// setting phrase num
		if($intNum) $this->setPhrasesNum($intNum);
		// setting skeleton vars
		if(is_array($arrVars)) $this->_arrVars = $arrVars;
		// setting skeleton vars
		if(is_array($arrValues)) $this->_arrValues = $arrValues;

		// setting flag
		$this->_intMoveStackPointer = $intMoveStackPointer;

		// done
		return $this;	
	}

	// sets the phrases number to be generated
	public function setPhrasesNum($intNum){

		if(!is_numeric($intNum)){
			throw new Exception('Invalid phrase num given.');
		}

		$this->_intPhraseNum = $intNum;

		return $this;
	}

	// sets scale image for all skeletons
	public function setScaleImage($mImage){

		if(is_string($mImage) && !empty($mImage)){
			
			if(!file_exists($mImage)){
				// ensure path to be correct
				$mImage = Bootstrap::getPath($mImage); 
			}
		
			// setting image
			$mImage = new SvgAdapt($mImage);
		}

		if(!$mImage instanceof SvgAdapt){
			throw new Exception('Invalid image given. String or SvgAdapt expected');
		}

		// setting local copy of baseScale
		$this->_mScaleBaseImage = $mImage;

		// updating skeleton
		$this->_oSkeleton->setBaseScaleSvgForAll($mImage);

		// done
		return $this;
	}

	public function setScaleType($intType){

		if(!is_numeric($intType)){
			throw new Exception('Invalid ScaleType given. integer expected');
		}

		// setting local value
		$this->_intScaleType = $intType;

		// updating skeleton
		$this->_oSkeleton->setValueForAll(CardGenerator::SCALETYPE, $intType);

		// done
		return $this;
	}

	// sets chord not reference.
	public function setChordNote($intChord, $intNote = 3){

		if(is_array($intChord)){
			$arrChords = $intChord;
			foreach($arrChords as $intChord => $intNote){
				$this->setChordNote($intChord, $intNote);
			}
			return $this;
		}

		// extracting chord note
		$intChordNote = $intNote;

		// checking note
		if($intChordNote > 7){
			$intChordNote-=7;
		}

		// setting chord note
		$intScaleNote = $intChord-1+$intChordNote;

		if($intScaleNote > 7){
			$intScaleNote-=7;
		}

		$this->_arrChordNote[] = array($intScaleNote, $intChord, $intNote);

		// done
		return $this;
	}

	// return phrases
	protected function _getPhrases($oGenerator, $strNote = false){

		// validate generator object
		$this->_validateGenerator($oGenerator);

		// getting notes list
		$arrChordNote = $this->_arrChordNote;
		
		// extracting notes
		$arrStartDeg   = array_shift($arrChordNote);
		$arrEndDeg     = array_pop($arrChordNote);
		$arrPassingDeg = array();

		foreach($arrChordNote as $arrChord){
			$arrPassingDeg[] = $arrChord[0];
		}

		return $oGenerator->getAllPossiblePhrases(
											$this->_mScaleBaseImage,
											$arrStartDeg[0],
											$arrEndDeg[0],
											$arrPassingDeg,
											$this->_intPhraseNum,
											$strNote,
											$this->_intScaleType,
											$this->_intMoveStackPointer);

	}

	// returns params that have to be applied to skeketons templates
	protected function _getPhrasesSkeletonsParams($oGenerator, $strNote = false){

		// validate generator object
		$this->_validateGenerator($oGenerator);

		// getting phrases
		$arrPhrases = $this->_getPhrases($oGenerator, $strNote);

		// setting default result array
		$arrSkelsParams = array();

		// building phrase template
		foreach($arrPhrases as $intPhraseKey => $arrPhrase){

			// setting default single skel param array
			$arrSkelParams  = array(
					'tpl'   => array(),      
					'vars' 	=> array(
						'phrasenum'	=> $intPhraseKey,
					),
					CardGenerator::QUESTIONINFO => array(),
					CardGenerator::ANSWERINFO   => array(),
			);

			// init notenum
			$intNoteNum = 0;

			foreach($arrPhrase['phrase'] as $arrNote){
				// getting chord data
				$intChord     = $this->_arrChordNote[$intNoteNum][1];
				$intChordNote = $this->_arrChordNote[$intNoteNum][2];
				// setting chord name
				$strChord = '{chord:'.$intChord.'}'.$this->_arrChordsType[$this->_intScaleType][$intChord];

				// setting templates vars
				$arrTplVars = array();
	
				// inserting values
				$arrTplVars['tplChordnote']     = $intChordNote;//'{chordnote:'.$intChordNote.'}';
				$arrTplVars['tplChord'] 	    = $intChord;//$strChord;
				$arrTplVars['tplChordQuality'] 	= $this->_arrChordsType[$this->_intScaleType][$intChord];
				$arrTplVars['tplString']        = $arrNote['s'];//$oGenerator->getNumTh($arrNote['s']);

				// adding variables to skelparams
				$arrSkelParams['tpl'][] = $arrTplVars;
				
				// setting images entry key
				$strEntryKey = 'DiagramChord'.$intChord.'Note'.$intChordNote;
				
				$arrSkelParams[CardGenerator::QUESTIONINFO][$strEntryKey] = array(
					'type' => 'image',
					'params' => array(
						'src'                  => '{source}/svg/vertic5.svg',
						'setTitle'			   => $strChord,
						'setNeckPosNum' 	   => 'auto', //
						'importSubTramCircles' => array($this->_mScaleBaseImage->getFileName()),
						'hightlightString' 	   => $arrNote['s'],
						'zone' 				   => 'center',
						'group'				   => array(
													'name' => 'QPhraseNotesDiagrams',
													'type' => 'horiz',
												 )
					),
				);
				
				$arrSkelParams[CardGenerator::ANSWERINFO][$strEntryKey] = array(
					'type' => 'image',
					'params' => array(
						'src'                  => '{source}/svg/vertic5.svg',
						'setTitle'			   => $strChord,
						'setNeckPosNum' 	   => 'auto', //
						'importSubTramCircles' => array($this->_mScaleBaseImage->getFileName()),
						'insertStyleString'    => '#'.$arrNote['id'].' circle{fill:red}', //hightlight
						'hightlightString' 	   => $arrNote['s'],
						'zone' 				   => 'center',
						'group'				   => array(
													'name' => 'APhraseNotesDiagrams',
													'type' => 'horiz',
												 )
					),
				);

				$intNoteNum++;
			}

			// adding single params to result array
			$arrSkelsParams[] = $arrSkelParams;
		}

		// returns result
		return $arrSkelsParams;
	}

	// adds a formatted template
	protected function _addTemplate($oGenerator, $arrCard, $intPhraseKey, $strRenderKey, $strNote = false){

		// validate generator object
		$this->_validateGenerator($oGenerator);

		// setting unique card identity
		$strCardIdent = $strRenderKey.'-'.($intPhraseKey+1);

		// setting default card id and name
		$strCardId   = 'PhraseBuilder';
		$strCardName = 'PhraseBuilder';

		// could we extract the card id
		if(isset($arrCard[CardGenerator::CARDID]) && !empty($arrCard[CardGenerator::CARDID])){
			// yes
			$strCardId = $arrCard[CardGenerator::CARDID];
			// do we have to extract the text
			if($strCardId instanceof CardText) {
				// yes
				$strCardId = $strCardId->getText();
			}
		}

		// could we extract the card name
		if(isset($arrCard[CardGenerator::CARDNAME]) && !empty($arrCard[CardGenerator::CARDNAME])){
			// yes
			$strCardName = $arrCard[CardGenerator::CARDNAME];
			// do we have to extract the text
			if($strCardName instanceof CardText) {
				// yes
				$strCardName = $strCardName->getText();
			}
		}

		// updating card id
		$arrCard[CardGenerator::CARDID]   = $strCardId.' - '.$strCardIdent;
		$arrCard[CardGenerator::CARDNAME] = $strCardName.' ('.$strCardIdent.')';
		
		// getting generator id
		$strGenId = $oGenerator->getGeneratorId();

		// do we have a note
		if(is_string($strNote)){
			$strGenId.=$strNote;
		}
		
		if(!isset($this->_arrTemplates[$strGenId])){
			//removing templates
			$this->_arrTemplates[$strGenId] = array();
		}

		// setting template
		$this->_arrTemplates[$strGenId][] = $arrCard;
		// done
		return $this;
	}

	// removes all generated templates
	public function clearTemplates($oGenerator, $strNote = false){

		// validate generator object
		$this->_validateGenerator($oGenerator);

		// getting generator id
		$strGenId = $oGenerator->getGeneratorId();

		if(is_string($strNote)){
			$strGenId.=$strNote;
		}

		if(isset($this->_arrTemplates[$strGenId])){
			//removing templates
			$this->_arrTemplates[$strGenId] = array();
		}
		
		// done
		return $this;
	}

	// clear template list and built new ones
	public function buildTemplates($oGenerator, $strNote = false){

		// validate generator object
		$this->_validateGenerator($oGenerator);
		// cleaning templates
		$this->clearTemplates($oGenerator, $strNote);

		// getting params from generated phrases in order to generate cards template from skeletons
		$arrSkelsParms = $this->_getPhrasesSkeletonsParams($oGenerator, $strNote);

		// for each phrase, generates as many templates as skeletons
		foreach($arrSkelsParms as $arrSkelParams){

			// rolling over all skels
			foreach($this->_oSkeleton as $oMultiSkel){

				// getting a copy of the rendering skeleton
				$oSkeleton    = clone $oMultiSkel->getSkeleton();
				$strRenderKey = $oMultiSkel->getRenderingKey();

				// init default QUESTIONINFO -----------------------------------------------------------
				$arrQInfo = array();
				
				// do we already have datas in QUESTIONINFO
				if($oMultiSkel->hasValue(CardGenerator::QUESTIONINFO)){
					// yes using value
					$arrQInfo = $oMultiSkel->getValue(CardGenerator::QUESTIONINFO);
					
					// do we have an empty value
					if(!is_array($arrQInfo)){
						// yes
						// using an empty array instead
						$arrQInfo = array();
					}
				}

				// do we have a template
				if(!isset($arrQInfo['TemplateText'])){
					// no
					throw new Exception('The QUESTIONINFO entry does not have any [TemplateText] data. To be usable the skeleton must have a valid Card.QuestionInfo.TemplateText.');
				}

				// do we have a template with valid type
				if(!is_array($arrQInfo['TemplateText']) || $arrQInfo['TemplateText']['type'] != 'text' ){
					// no
					throw new Exception('Card.QuestionInfo.TemplateText is not in a expected format. An array with type="text" is expected !');
				}
				
				// do we have a template with data
				if(!isset($arrQInfo['TemplateText']['params']['text'])){
					// no
					throw new Exception('Card.QuestionInfo.TemplateText.params.text is not set !');
				}
				
				// yes
				// init info list
				$strInfo = '<div class="infolist"><ol>';
					
				foreach($arrSkelParams['tpl'] as $arrTplVars){
					$strInfo.= '<li>'.$this->_replaceVar($arrTplVars, $arrQInfo['TemplateText']['params']['text'], $oGenerator).'</li>';
				}

				// endof list
				$strInfo.= '</ol></div>';

				// setting new list into info
				$arrQInfo['TemplateText']['params']['text'] = $strInfo;
	
				// inserting common values
				if(is_array($this->_arrValues) && !empty($this->_arrValues)){
					foreach($this->_arrValues as $strValName => $mValValue){
						$oSkeleton->setValue($strValName, $mValValue, $strRenderKey);
					}
				}

				// setting images
				$arrQInfo = array_merge($arrQInfo, $arrSkelParams[CardGenerator::QUESTIONINFO]);

				// updating QUESTIONINFO
				$oSkeleton->setValue(CardGenerator::QUESTIONINFO, $arrQInfo, $strRenderKey);

				// /QUESTIONINFO -----------------------------------------------------------

				// init default ANSWERINFO -----------------------------------------------------------
				$arrAInfo = array();
				
				// do we already have datas in ANSWERINFO
				if($oMultiSkel->hasValue(CardGenerator::ANSWERINFO)){
					// yes using value
					$arrAInfo = $oMultiSkel->getValue(CardGenerator::ANSWERINFO);
					
					// do we have an empty value
					if(!is_array($arrAInfo)){
						// yes
						// using an empty array instead
						$arrAInfo = array();
					}
				}

				// setting images
				$arrAInfo = array_merge($arrAInfo, $arrSkelParams[CardGenerator::ANSWERINFO]);

				// updating ANSWERINFO
				$oSkeleton->setValue(CardGenerator::ANSWERINFO, $arrAInfo, $strRenderKey);

				// getting card array
				$arrCard = $oSkeleton->toArray($strRenderKey, array_merge($this->_arrVars, $arrSkelParams['vars']));

				// adding template
				$this->_addTemplate($oGenerator, $arrCard, $arrSkelParams['vars']['phrasenum'], $strRenderKey, $strNote);

			} // endof foreach $this->_oSkeleton
		}// endof foreach $arrSkelsParms
		
		// done
		return $this;
	}

	// return last builts templates for the given generator
	public function getPhrasesTemplates($oGenerator, $strNote = false){

		// validate generator object
		$this->_validateGenerator($oGenerator);
		
		// getting generator id
		$strGenId = $oGenerator->getGeneratorId();

		if(is_string($strNote)){
			$strGenId.=$strNote;
		}

		if(isset($this->_arrTemplates[$strGenId])){
			//removing templates
			return $this->_arrTemplates[$strGenId];
		}
		
		return array();
	}

	// returns an array
	public function getTemplate($oGenerator, $strNote){

		// validate generator object
		$this->_validateGenerator($oGenerator);
		// clear templates
		$this->clearTemplates($oGenerator, $strNote);
		// building phrases
		$this->buildTemplates($oGenerator, $strNote);
		// getting templates
		return $this->getPhrasesTemplates($oGenerator, $strNote);
	}
}
