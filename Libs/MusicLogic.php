<?php

require_once(__DIR__.'/BaseAbstract.php');

abstract class MusicLogic extends BaseAbstract{

		// consts
		const BECARRE = 1;
		const SHARP   = 2;
		const FLAT    = 3;
		const MAJOR   = 4;
		const MINOR   = 5;
		const HARMONICMINOR = 6;
		const MELODICMINOR  = 7;
		const NATURALMINOR  = 8;
		const DORIAN     = 9;
		const PHRYGIAN   = 10;
		const LYDIAN     = 11;
		const MIXOLYDIAN = 12;
		const AEOLIEN    = 8;
		const LOCRIAN    = 13;
		//const ALTERED    = 10;

		// intervals qualification
		const INT2m   = array(2,1);
		const INT2M   = array(2,2);
		const INT2Aug = array(2,3);
		const INT3m   = array(3,3);
		const INT3M   = array(3,4);
		const INT4Dim = array(4,4);
		const INT4j   = array(4,5);
		const INT4Aug = array(4,6);
		const INT5Dim = array(5,6);
		const INT5j   = array(5,7);
		const INT5Aug = array(5,8);
		const INT6m   = array(6,8);
		const INT6M   = array(6,9);
		const INT7Dim = array(7,9);
		const INT7m   = array(7,10);
		const INT7M   = array(7,11);
		const INT8    = array(8,12);
		const INT9m   = array(2,1);
		const INT9M   = array(2,2);
		const INT9Aug = array(2,3);
		const INT11Dim = array(4,4);
		const INT11j   = array(4,5);
		const INT11Aug = array(4,6);
		const INT13m   = array(6,8);
		const INT13M   = array(6,9);

		// intervals list
		protected $_arrIntervals = array(
			2 => array(self::INT2m,     self::INT2M,  self::INT2Aug),
			3 => array(self::INT3m,     self::INT3M),
			4 => array(self::INT4Dim,   self::INT4j,  self::INT4Aug),
			5 => array(self::INT5Dim,   self::INT5j,  self::INT5Aug),
			6 => array(self::INT6m,     self::INT6M),
			7 => array(self::INT7Dim,   self::INT7m,  self::INT7M),
			8 => array(self::INT8),
			9 => array(self::INT9m,     self::INT9M,  self::INT9Aug),
			10 => array(self::INT3m,    self::INT3M),
			11 => array(self::INT11Dim, self::INT11j, self::INT11Aug),
			12 => array(self::INT5Dim,  self::INT5j,  self::INT5Aug),
			13 => array(self::INT13m,   self::INT13M)
		);

		// setting note names 
		protected $_notesNames = array('c',  'd',  'e',  'f',  'g',  'a',  'h', 'c', 'd', 'e', 'f', 'g', 'a', 'h');
		
		// setting values of notes cycles.
		protected $_arrCycleOfFifth  = array('f', 'c', 'g', 'd', 'a', 'e', 'h', 'f#', 'c#', 'g#', 'd#', 'a#', 'e#', 'h#');
		protected $_arrCycleOfFourth = array('f', 'h', 'e', 'a', 'd', 'g', 'c');
		
		protected $_arrHalfTones = array(array('c',  'dbb', 'h#'), 
										 array('c#', 'db'), 
										 array('d',  'ebb', 'c##'),
										 array('d#', 'eb' , 'fbb'),
										 array('e',  'fb',  'd##'),
										 array('f',  'gbb', 'e#'),
										 array('f#', 'gb'),
										 array('g',  'abb', 'f##'),
										 array('g#', 'ab'),
										 array('a',  'hbb', 'g##'),
										 array('a#', 'hb',  'cbb'),
										 array('h',  'cb',  'a##'));
		
		// list common of scales
		protected $_arrCommonScales = array('g', 'd', 'a', 'e', 'h', 'f', 'c', 'ab', 'db', 'f#', 'eb', 'hb'); //, 'c#'
		
		// list of allows note for card generation
		protected $_arrAllowsNotesGenCard = array('g', 'gb', 'g#', 'd', 'db', 'd#', 'a', 'ab', 'a#', 'e', 'eb', 'h', 'hb', 'f', 'f#', 'c', 'c#');
		
		// list of guitar string notes
		protected $_arrStringsNotes = array(
					1 => 'e', 
					2 => 'h',
					3 => 'g',
					4 => 'd',
					5 => 'a',
					6 => 'e',    
		);

		// cached datas of transposition
		protected static $_arrTransposeCache = array();

		// ----- Pure music logic

		// returns the number of alts for the tone $strNote
		// positive values represent the sharps and negatives value the flats
		public function getAltsByTone($strNote){
			
			// getting cycle array
			$arrCycle = $this->getCycleForTone($strNote);
			// getting cycle type
			$intCycleType = $this->getCycleTypeForTone($strNote);
			
			// do we use sharps
			if($intCycleType == 5){
				// yes
				$intIndex = array_search($strNote, $arrCycle);
				
				if($intIndex === false){
					throw new Exception('Note not found in cycle of fifth : '.$strNote);
				}
				
				return $intIndex - 1;
			}
			
			// no we are using flats (cycle of fourth)
			$intIndex = array_search($this->getNoteAbs($strNote), $arrCycle);
			
			if($intIndex === false){
				throw new Exception('Note not found in cycle of fourth : '.$strNote.' ('.$this->getNoteAbs($strNote).')');
			}
			
			return 0 - ($intIndex +1);
		}

		// returns the corresponding note cycle type (fifth or fourth) for $strNote
		// return 5 for the fifth or 4 for the Fourth
		public function getCycleTypeForTone($strNote){
			
			// should we use the CycleOfFourth
			if(strpos($strNote, 'b') > 0 || $strNote == 'f'){
				// yes
				return 4;
			}
			
			return 5;
		}
		
		// returns the note cycle array corresponding to tone $strNote
		public function getCycleForTone($strNote){
		
			// getting cycle type
			if($this->getCycleTypeForTone($strNote) == 4){
				return $this->_arrCycleOfFourth;
			}
			
			return $this->_arrCycleOfFifth;
		}
		
		// returns true if $strNote contains a flat sign
		public function hasFlat($strNote){
			
			if(strpos($strNote, 'b') > 0){
				// yes
				return true;
			}
			
			return false;
		}
		
		// return absolute value of a note, without alteration
		public function getNoteAbs($strNote){
				return substr($strNote, 0, 1);
		}
		
		// return a list a 7 notes starting with the absolute value of $strNote
		// turn the reverse flag to get the array in the reverse order
		public function getNotesArrayStartingWith($strNote, $intReverse = false){
				
				// getting notes list in the right order
				$arrNotes = ($intReverse)? array_reverse($this->_notesNames) : $this->_notesNames;
				
				// getting index of the current note
				$intNoteIndex = array_search($this->getNoteAbs($strNote), $arrNotes);
				// keeping only required note liste
				return array_slice($arrNotes, $intNoteIndex, 7);
		}
		
		// return the note placed at $intNum notes from $strNote in the list $arrNotes
		// for exemple $this->_getNoteNumberOf(5, 'c') will return g
		// if $strNote cannot be found in $arrNotes it will return the note placed $intNum from the first note
		// if $intReverse flag is set, the reverse note list will be used
		protected function _getNoteNumberOf($intNum, $strNote, $arrNotes = false, $intReverse = false){
			
			// getting note array if required
			if(!$arrNotes)  $arrNotes = $this->_notesNames;
			if($intReverse) $arrNotes = array_reverse($arrNotes);
			// ensure $arrNotes to be big enought 
			//$arrNotes = array_merge($arrNotes, $arrNotes);
			
			// getting index of the startNote note
			$intNoteIndex = array_search($strNote, $arrNotes);
			// updating note list	
			$arrNotes = array_slice($arrNotes, $intNoteIndex, 7);
			// ensure $arrNotes to be big enought 
			$arrNotes = array_merge($arrNotes, $arrNotes, $arrNotes);
			
			return $arrNotes[$intNum-1];
		}
		
		// return alterations for the Major scale $strNote
		// returns an array which contains notes as keys and alteration as value
		public function getAltForMajorScaleOf($strNote){
			
			// special case of fb scale which have all notes flatted and h double flatted
			if($strNote == 'fb'){
				return array(
					'f' => 'b',
					'h' => 'bb', 
					'e' => 'b',
					'a' => 'b',
					'd' => 'b',
					'g' => 'b',
					'c' => 'b'
				);
			}
			
			// getting default alts array and params
			$arrCycle  = $this->_arrCycleOfFifth;
			$intFifth  = true;
			$intIndex  = -2; 
			
			// TODO : use the getCycleTypeForTone method instead
			// should we use the CycleOfFourth instead
			if(strpos($strNote, 'b') > 0 || $strNote == 'f'){
				// yes
				$arrCycle  = $this->_arrCycleOfFourth;
				$intIndex = 0;
				$intFifth  = false;
			}
			// getting simple absolute value of the note
			$strNoteAbs = $this->getNoteAbs($strNote);
		
			// getting index
			$intNoteIndex = array_search((($intFifth)?$strNote : $strNoteAbs), $arrCycle);
			// defining start point
			$intStart  = $intNoteIndex - 6 + $intIndex;
			// settinh default length
			$intLength = 7;
			
			// checking if start point if less than 0
			if($intStart < 0) {
				$intStart = ($intFifth)? 0 : 1;
				$intLength = ($intNoteIndex+$intIndex)+1;
			}
			
			// setting default array
			$arrAlts = array();
			// extracting alts if required
			if($intLength > 0) $arrAlts = array_slice($arrCycle, $intStart, $intLength);
					
			// setting result array
			$arrResult = array();
			
			foreach($arrAlts as $strAltNote){
				
					// setting note alt
					$strAlt = ($intFifth)? '#':'b';
				
					if($intFifth && strpos($strAltNote, '#') == 1){
							$strAltNote = $this->getNoteAbs($strAltNote);
							$strAlt.='#';
					}
					
					$arrResult[$strAltNote] = $strAlt;
			}
				
			// return alts
			return $arrResult;
		}
		
		// return predefined halftone array.
		// if $strFromNote is given, then the array will start from this note
		protected function _getHalfToneArray($strFromNote = false){
			
			// getting default array
			$arrHalfTone = $this->_arrHalfTones;
			$intHasNote  = (is_string($strFromNote) && !empty($strFromNote));		
			
			if(!$intHasNote){
				return $arrHalfTone;
			}
			
			// setting array
			$arrHalfTone = array_merge($this->_arrHalfTones, $this->_arrHalfTones, $this->_arrHalfTones);
			$intRefKey   = 0;
			
			// searching fromNote
			foreach($arrHalfTone as $intKey => $arrNotes){
					if(in_array($strFromNote, $arrNotes)){
						$intRefKey = $intKey;
						break;
					}
			}
			
			// return array
			return array_slice($arrHalfTone, $intKey, 24);
		}
		
		// return an halftone array in a reverse order
		protected function _getHalfToneArrayReverse($strToNote){
			
			// getting default array
			$arrHalfTone = $this->_arrHalfTones;
			
			if(!is_string($strToNote) || empty($strToNote)){
				throw new Exception('Invalid param given. StringExpected');
			}		
						
			// setting array
			$arrHalfTone = array_reverse(array_merge($this->_arrHalfTones, $this->_arrHalfTones, $this->_arrHalfTones));
			$intRefKey   = 0;
			
			// searching fromNote
			foreach($arrHalfTone as $intKey => $arrNotes){
					if(in_array($strToNote, $arrNotes)){
						$intRefKey = $intKey;
						break;
					}
			}
			
			// return array
			return array_slice($arrHalfTone, $intKey, 24);
		}
		
		// return the seconde note name of the interval $arrInterval (see consts)
		public function getNoteForInterval($strNote, $arrInterval = array(0,0), $intReverse = false){
			
			// extracting interval properties
			$intNoteNum  = $arrInterval[0];
			$intHalfTone = $arrInterval[1];
			
			// getting interval ref note
			$strIntervalNote = $this->_getNoteNumberOf($intNoteNum, $this->getNoteAbs($strNote), false, $intReverse);
			// getting halfTone array
			$arrHalfTone = ($intReverse)? $this->_getHalfToneArrayReverse($strNote) : $this->_getHalfToneArray($strNote);
			// extracting list of notes
			$arrIntervalArray = $arrHalfTone[$intHalfTone];
			
			//print_r($arrIntervalArray);
			foreach($arrIntervalArray as $strRealIntervalNote){
				// checking if note is corresponding
				if($this->getNoteAbs($strRealIntervalNote) == $strIntervalNote){
					// yes
					return $strRealIntervalNote;
				}
			}
			
			// nothing found
			return false;
		}
		
		// returns the interval datas between $strNoteA and $strNoteB with $intHalfTone
		public function getIntervalByHalfTones($strNoteA, $strNoteB, $intHalfTones){
			
			// checking halftone value
			if(!$intHalfTones){
				throw new Exception('halftones value must not be equal to 0 !');
			}
			
			// getting notes
			$arrNotes = $this->getNotesArrayStartingWith($this->getNoteAbs($strNoteA), (($intHalfTones < 0)? true : false));
			
			// getting interval number
			$intInterval = array_search($this->getNoteAbs($strNoteB), $arrNotes) + 1;
			
			// checking if interval is usable
			if(!array_key_exists($intInterval, $this->_arrIntervals)){
				throw new Exception('Not a usable interval value : '.$intInterval);
			}
			
			// getting absolute value
			$intAbsHalfTones = abs($intHalfTones);
			
			foreach($this->_arrIntervals[$intInterval] as $arrIntervalData){
				if($arrIntervalData[1] == $intAbsHalfTones){
					return $arrIntervalData;
				}
			}
			
			throw new Exception('Interval of '.$intInterval.' does not allow '.$intAbsHalfTones.' halftones');
		}
		
		// return an array with notes as key and transposed notes as key
		// if $intReverse is turned on transposition will be applied in the reverser order
		public function getTransposedHalfTonesArray($arrInterval = array(0,0), $intReverse = false){
			
			// setting cache key
			$strCacheKey = md5('getTransposedHalfTonesArray'.serialize(func_get_args()));
			
			// do we have cached datas
			if(isset(self::$_arrTransposeCache[$strCacheKey])){
				// yes
				return self::$_arrTransposeCache[$strCacheKey];
			}

			// setting default result
			$arrResult = array();
			
			foreach($this->_arrHalfTones as $arrHalftones){
				foreach($arrHalftones as $strNote){
					$strTransNote = $this->getNoteForInterval($strNote, $arrInterval, $intReverse);
					if(!empty($strTransNote)) $arrResult[$strNote] = $strTransNote;
				}
			}
			
			// sorting array
			ksort($arrResult);
			// setting cache
			self::$_arrTransposeCache[$strCacheKey] = $arrResult;
			// done
			return $arrResult;
		}
		
		// turn a list of transposed notes as generated by getTransposedHalfTonesArray
		// into an array containing only alts numbers
		public function getTransposedKeyAltsNumberArray($arrKeys){
			
			// setting cache key
			$strCacheKey = md5('getTransposedKeyAltsNumberArray'.serialize(func_get_args()));
			
			// do we have cached datas
			if(isset(self::$_arrTransposeCache[$strCacheKey])){
				// yes
				return self::$_arrTransposeCache[$strCacheKey];
			}
			
			$arrResult = array();
		
			foreach($arrKeys as $strKey => $strTrans){
				
				$intKeyHasFlat   = $this->hasFlat($strKey);
				$intTransHasFlat = $this->hasFlat($strTrans);
				$strKeyAbs       = $this->getNoteAbs($strKey);
				$strTransAbs     = $this->getNoteAbs($strTrans);
				
				if(($intKeyHasFlat   && !in_array($strKeyAbs, $this->_arrCycleOfFourth))   || 
				   ($intTransHasFlat && !in_array($strTransAbs, $this->_arrCycleOfFourth)) ||
				   (!$intKeyHasFlat  && !in_array($strKey, $this->_arrCycleOfFifth)) || 
				   (!$intTransHasFlat && !in_array($strTrans, $this->_arrCycleOfFifth))){
					// skipping
					continue;
				}
				
				$intKeyTone  = $this->getAltsByTone($strKey);
				$intKeyTrans = $this->getAltsByTone($strTrans);
				
				// adding data to result array
				$arrResult[$intKeyTone] = $intKeyTrans;
			}
			// sorting array
			ksort($arrResult);
			// setting cache
			self::$_arrTransposeCache[$strCacheKey] = $arrResult;
			// done	
			return $arrResult;
		}
		
		// apply alterations of the major scale of $strAltNote to a list of notes starting with $strStartNote
		// if $strStartNote is omitted, the list of notes will start with $strAltNote 
		public function applyAltsOf($strAltNote, $strStartNote = false){
			
			// setting startNote
			if(!$strStartNote) $strStartNote = $strAltNote; 
			// getting alts
			$arrAlts  = $this->getAltForMajorScaleOf($strAltNote);
			// getting note list
			$arrNotes = $this->getNotesArrayStartingWith($strStartNote);
			
			foreach($arrNotes as $intKey => $strNote){
				
				if(!isset($arrAlts[$strNote])){
					continue;
				}
				
				// setting alteration
				$arrNotes[$intKey] = $strNote.$arrAlts[$strNote]; 
			}

			return $arrNotes;
		}
		
		// extract a list of $intLength notes from $arrNotes, starting with $strStartNote
		protected function _extractListOfNotes($strStartNote, $arrNotes, $intLength = 7){
			// ensure array to have the right size
			$arrNotes = array_merge($arrNotes, $arrNotes, $arrNotes);
			// getting index of $strStartNote
			$intNoteIndex = array_search($strStartNote, $arrNotes);
			// keeping only required note liste
			return array_slice($arrNotes, $intNoteIndex, $intLength);
		}
		
		// returns list of note corresponding to the scale of $strNote.
		public function getScaleOf($strNote, $intType = self::MAJOR){
			
			// getting real type value
			$intType = $this->_filterScaleType($intType);
			
			$this->_debugMessage('Getting scale of : '.$strNote.' type '.$intType);
			
			if($intType == self::MAJOR){
					// getting alterations
					
					// setting double flat falg
					$intDoubleFlat = false;
					// do we have a double flat note
					if(strpos($strNote, 'bb') == 1){
						// yes. Asking for single flat scale
						$strNote = $this->getNoteAbs($strNote).'b';
						$intDoubleFlat = true;
					}				
					
					// getting scale array
					$arrScale = $this->applyAltsOf($strNote, $strNote);
					
					if(!$intDoubleFlat){
						// no double flat, scale can be returned.
						return $arrScale;
					}
					
					// updating scale with double flat
					foreach($arrScale as $intKey => $strScaleNote){
						$arrScale[$intKey] = $strScaleNote.'b';
					}
					// done
					return $arrScale;
			}
			
			// getting relative major scale
			$strRelMaj = $this->getRelativeMajorScale($strNote, $intType);
			
			if(!$strRelMaj){
				// no scale found
				return false;
			}
			
			// getting scale notes
			$arrRefScale = $this->getScaleOf($strRelMaj, self::MAJOR);
			// returning notes in the right order
			return $this->_extractListOfNotes($strNote, $arrRefScale, 7);
			
			/*if($intType == self::NATURALMINOR){
					// getting relative major scale
					$arrRefScale = $this->getScaleOf($this->getNoteForInterval($strNote, self::INT3m), self::MAJOR);
					return $this->_extractListOfNotes($strNote, $arrRefScale, 7);
			}
			
			if($intType == self::DORIAN){
					// getting relative major scale
					$arrRefScale = $this->getScaleOf($this->getNoteForInterval($strNote, self::INT7m), self::MAJOR);
					return $this->_extractListOfNotes($strNote, $arrRefScale, 7);
			}
			
			if($intType == self::PHRYGIAN){
					// getting relative major scale
					$arrRefScale = $this->getScaleOf($this->getNoteForInterval($strNote, self::INT6m), self::MAJOR);
					return $this->_extractListOfNotes($strNote, $arrRefScale, 7);
			}
			
			if($intType == self::LYDIAN){
					// getting relative major scale
					$arrRefScale = $this->getScaleOf($this->getNoteForInterval($strNote, self::INT5j), self::MAJOR);
					return $this->_extractListOfNotes($strNote, $arrRefScale, 7);
			}
			
			if($intType == self::MIXOLYDIAN){
					// getting relative major scale
					$arrRefScale = $this->getScaleOf($this->getNoteForInterval($strNote, self::INT4j), self::MAJOR);
					return $this->_extractListOfNotes($strNote, $arrRefScale, 7);
			}
			
			if($intType == self::LOCRIAN){
					// getting relative major scale
					$arrRefScale = $this->getScaleOf($this->getNoteForInterval($strNote, self::INT2m), self::MAJOR);
					return $this->_extractListOfNotes($strNote, $arrRefScale, 7);
			}
			
			return false;*/
		}
		
		// returns fret number on which $strNote is placed for string $intString
		// is $intAll is true, all the positions will be return, else, only the first
		public function getFretNumberForNote($strNote, $intString = 6, $intAll = true, $intMinFret = 0){
			
			// getting string first note
			$strStringFirstNote = $this->_arrStringsNotes[$intString];
			// checking for empty string
			if($strNote == $strStringFirstNote && !$intAll && !$intMinFret) return 0;
			// ensure minFret to be a number
			if(!is_numeric($intMinFret)) $intMinFret = 0;
			
			// getting halftone array
			$arrStringNotes = $this->_getHalfToneArray($strStringFirstNote);
			
			// setting result array
			$arrResult = array();
			
			foreach($arrStringNotes as $intFret => $arrNotesList){
				// do we have $strNote in the note list
				if(!in_array($strNote, $arrNotesList)){
					// no
					continue;
				}	
				
				// is the found fret number highter than
				// the minimum fret param
				if($intFret <= $intMinFret){
					// no
					continue;
				}
				
				if(!$intAll) return $intFret;
				$arrResult[] = $intFret;
			}
			
			return $arrResult;	
		}
		
		// translate $strNote
		public function translateNote($strNote){
				// getting note Absolute value
				$strAbsNote = $this->getNoteAbs($strNote);
				// getting translation
				$strTransNote = Bootstrap::getInstance()->i18n()->_t($strAbsNote, false, 'translateNote');			
				// replacing note name 
				$strNote = $strTransNote.substr($strNote, 1);
				// return note string
				return $strNote;
		}
		
		// translate $strNote
		public function translateChord($strChord){
				// getting note Absolute value
				$strAbsNote = $this->getNoteAbs($strChord);
				// getting translation				
				$strTransNote = Bootstrap::getInstance()->i18n()->_t($strAbsNote, false, 'translateChord');
				// replacing note name 
				$strChord = $strTransNote.substr($strChord, 1);
				// return note string
				return $strChord;
		}
		
		// return mode const value from degre pos
		public function getScaleModeForDegre($intDegre = false){
		
				if($intDegre == 1 || !$intDegre){
					return self::MAJOR;
				}
		
				// setting mode list
				$arrList = array(
					2 => self::DORIAN,
					3 => self::PHRYGIAN,
					4 => self::LYDIAN,
					5 => self::MIXOLYDIAN,
					6 => self::AEOLIEN,
					7 => self::LOCRIAN
				);
		
				return $arrList[$intDegre];
		}

		// filters scale type to ensure it to be an int
		protected function _filterScaleType($mType){
			
			if(is_numeric($mType)){
				return $mType;
			}
			
			if(!is_string($mType)){
				throw new Exception('Invalid scale type given. Numerical or string expected');
			}
			
			if(empty($mType)){
				throw new Exception('Empty ScaleType found');
			}
			
			// getting realtype
			try{
				eval('$intType = self::'.trim(strtoupper($mType)).';');
			}catch(Throwable $oException){
				throw new Exception("Error while evaluating scale type : [$mType]");
				exit;
			}
						
			if(!is_numeric($intType)){
				throw new Exception('Unknown scale type : '.$mType);
			}
			
			return $intType;
		}

		// return the relative major scale of $strNote from $intType
		public function getRelativeMajorScale($strNote, $intType){
			
			// getting real type value
			$intType = $this->_filterScaleType($intType);
			
			// do we already have a major scale
			if($intType == self::MAJOR){
				// yes
				return $strNote;
			}
			
			if($intType == self::NATURALMINOR){
				return $this->getNoteForInterval($strNote, self::INT3m);
			}
			
			if($intType == self::DORIAN){
				return $this->getNoteForInterval($strNote, self::INT7m);
			}
			
			if($intType == self::PHRYGIAN){
				return $this->getNoteForInterval($strNote, self::INT6m);
			}
			
			if($intType == self::LYDIAN){
				return $this->getNoteForInterval($strNote, self::INT5j);
			}
			
			if($intType == self::MIXOLYDIAN){
				return $this->getNoteForInterval($strNote, self::INT4j);
			}
			
			if($intType == self::LOCRIAN){
				return $this->getNoteForInterval($strNote, self::INT2m);
			}	
			
			// nothing found
			throw new Exception('Unknown scale type : '.$intType);
		}

		// returns the halftone between to scales of any types
		public function getScalesHalfTonesDiff($strScaleA, $intScaleAType, $strScaleB, $intScaleBType){
		
				// checking scale A
				if(!is_string($strScaleA) || empty($strScaleA)){
					throw new Exception('Invalid ScaleA : String expected');
				}
				
				// getting real type value
				$intScaleAType = $this->_filterScaleType($intScaleAType);
				
				// checking ScaleType A
				if(!is_numeric($intScaleAType) || $intScaleAType < 1){
					throw new Exception('Invalid ScaleAType : Integer expected');
				}
				
				// checking scale B
				if(!is_string($strScaleB) || empty($strScaleB)){
					throw new Exception('Invalid ScaleB : String expected');
				}
				
				// getting real type value
				$intScaleBType = $this->_filterScaleType($intScaleBType);
				
				// checking ScaleType B
				if(!is_numeric($intScaleBType) || $intScaleBType < 1){
					throw new Exception('Invalid ScaleBType : Integer expected');
				}
		
				// getting relatives majors scales for both notes
				$strMajorA = $this->getRelativeMajorScale($strScaleA, $intScaleAType);
				$strMajorB = $this->getRelativeMajorScale($strScaleB, $intScaleBType);
				// getting halftone array starting from the first note
				$arrHalfTone = $this->_getHalfToneArray($strMajorA);
				
				
				foreach($arrHalfTone as $intHalfTones => $arrNotes){
					// checking if $strMajorB Note is current halftone array
					if(in_array($strMajorB, $arrNotes)){
						// yes
						// we can return the value
						return $intHalfTones;
					}
				}
				
				// not able to find the diff
				return false;
		}
} 
