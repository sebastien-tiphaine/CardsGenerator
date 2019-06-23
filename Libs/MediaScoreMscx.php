<?php

require_once(__DIR__.'/MediaAbstract.php');
require_once(__DIR__.'/MscxAdapt.php');

class MediaScoreMscx extends MediaAbstract{

	// list of defaults params
	protected $_arrParams  = array(
		// mandatory params :
		'src'	       	 => false, 			// path to the mscx file
		'score.styles' 	 => false, 			//'source/styles/mscore-styles.ini',
		'score.mss'	   	 => false, 			//'source/styles/mscore-styles.mss',
		'size'		   	 => 130, 			// dpi size of the generated image
		// params that must have a default value
		'allowTranspose' => true, 			// flag that allow the score to be transposed using current note
		'refString'    	 => 6, 				// string on which the refNote is placed
		'minFret'      	 => 0, 				// lower fret on which the mscx can be transposed to
		'refToneType'  	 => self::MAJOR,  	// quality of the tone (major, minor ...)
	
		// params only listed for documentation
		//'refSvg'       	 => false, 			// svg reference file
		//'refTone'      	 => false, 			// tone in which the mscx is written in
		//'refNote'      	 => false, 			// reference note used to calculate halftone diff with current generator note
		//'refFret'	   	 => false, 			// fret on which the refNote is placed
		//'curString'    	 => false, 			// string on which the generator is actualy working
		//'copyright'	   	 => false, 			// copyright that have to be displayed
		//'title'		   	 => false,			// image title
		//'solfeggio'	   	 => false, 			// indicate that notes names in the score should follow the local
	);

	// filter for the refSvg media param
	protected function _filterSetRefSvg($strSvg){
		
		// checking param
		if(!is_string($strSvg) || empty($strSvg) || !is_file($strSvg)){
			throw new Exception(__CLASS__.':: File does not exists : '.$strSvg);
		}
		
		return $strSvg;
	}
	
	// filter for the src media param
	protected function _filterSrc($strSrc){
		
		// checking param
		if(!is_string($strSrc) || empty($strSrc) || !is_file($strSrc)){
			throw new Exception(__CLASS__.':: File does not exists : '.$strSrc);
		}
		
		return $strSrc;
	}
	
	// filter for the refNote media param
	protected function _filterSetRefNote($strRefNote){
		
		if(!is_string($strRefNote) || empty($strRefNote)){
			throw new Exception(__CLASS__.':: Invalid refNote given. String expected.');
		}
		
		return strtolower(trim($strRefNote));
	}

	// filter for the refTone media param
	protected function _filterSetRefTone($strRefTone){
		
		if(!is_string($strRefTone) || empty($strRefTone)){
			throw new Exception(__CLASS__.':: Invalid refTone given. String expected.');
		}
		
		return strtolower(trim($strRefTone));
	}
	
	// filter for the refToneType media param
	protected function _filterSetRefToneType($intRefToneType){
	
		if(!is_numeric($intRefToneType)){
			throw new Exception(__CLASS__.':: Invalid refToneType given. Integer expected.');
		}
		
		return intval($intRefToneType);	
	}
	
	// filter for the refString media param
	protected function _filterSetRefSting($intRefString){
	
		if(!is_numeric($intRefString)){
			throw new Exception(__CLASS__.':: Invalid refString given. Integer expected.');
		}
		
		return intval($intRefString);	
	}
	
	// filter for the curString media param
	protected function _filterSetCurString($intCurString){
	
		if(!is_numeric($intCurString)){
			throw new Exception(__CLASS__.':: Invalid curString given. Integer expected.');
		}
		
		return intval($intCurString);	
	}
	
	// filter for the minFret media param
	protected function _filterSetMinFret($intMinFret){
	
		if(!is_numeric($intMinFret)){
			throw new Exception(__CLASS__.':: Invalid minFret given. Integer expected.');
		}
		
		return intval($intMinFret);	
	}
	
	// filter for the refFret media param
	/*protected function _filterSetRefFret($intRefFret){
	
		if(!is_numeric($intRefFret)){
			throw new Exception(__CLASS__.':: Invalid refFret given. Integer expected.');
		}
		
		return intval($intRefFret);	
	}*/

	// apply rendering
	protected function _render($arrMedia, $arrParams, $strNote, $intScaleType){

		echo "\n\n  ## MSCX ## Starting Score generation";

		// getting full params array (media and params)
		$arrParams = array_merge($arrParams, $arrMedia);

		// setting image indentKey
		$strImgIdent = md5(serialize(func_get_args()).serialize(array_merge($this->_arrParams, $arrParams, $arrMedia)));
		// setting outputfile name
		$strPngFile = Bootstrap::getPath($this->_getOutputDir().'/'.$strImgIdent.'.png');

		// checking if file has already been generated
		if(file_exists($strPngFile)){
			// yes
			return $strPngFile;
		}
		
		// getting file src
		$strSrc	= $this->_getParam('src', false, $arrParams); // mscx file path
		// do we have a valid source
		if(!is_string($strSrc) || empty($strSrc)){
			// no
			throw new Exception(__CLASS__.':: Missing or invalid src param');
		}
		
		// checking path
		$strSrc = Bootstrap::getPath($strSrc);

		// getting adapter
		$oAdapt = new MscxAdapt($strSrc);
				
		// getting transposition flag. Should be true by default
		$intAllowTranspose = $this->_getParam('allowTranspose', true, $arrParams);
		
		// is transposition allowed
		if($intAllowTranspose){
			echo "\n  ## MSCX ## Transposition is Turned [ON]";
			// yes
			// getting ref note
			$strRefNote = $this->_getParam('refNote', $this->_getParam('refTone', $arrParams), $arrParams);
			// checking ref note
			if(!is_string($strRefNote) || empty($strRefNote)){
				throw new Exception('Missing one of the following param : refNote or refTone');
			}
			// getting other transposition params
			$intRefString   = $this->_getParam('refString', 6, $arrParams); // reference string for the score	
			$intCurString   = $this->_getParam('curString', $intRefString, $arrParams); // string of the current note
			$intMinFret     = $this->_getParam('minFret', 0, $arrParams); // lower fret on which the score can be transposed
			
			echo "\n  ## MSCX ## refNote   : $strRefNote";
			echo "\n  ## MSCX ## current note : $strNote";
			echo "\n  ## MSCX ## refString : $intRefString";
			echo "\n  ## MSCX ## curString : $intCurString";
			echo "\n  ## MSCX ## minFret   : $intMinFret";
						
			// getting fret number for given note
			$intFret    = $this->getFretNumberForNote($strNote, $intCurString, false, $intMinFret);
			
			echo "\n  ## MSCX ## Fret found for current note [$strNote] on string [$intCurString]: $intFret";
			
			// getting fret number for the reference note
			$intRefFret = $this->getFretNumberForNote($strRefNote, $intRefString, false, 0);
			
			echo "\n  ## MSCX ## Fret found for ref note [$strRefNote] on string [$intRefString]: $intRefFret";
			
			// getting the number of frets (halftones)
			// the score has to be transposed to
			$intDiff = $intFret - $intRefFret;
				
			echo "\n  ## MSCX ## diff found : $intDiff";
				
			// do we have to transpose 
			if($intDiff != 0){
				// yes
				// getting interval
				$arrInterval = $this->getIntervalByHalfTones($strRefNote, $strNote, $intDiff);

				// getting notes transposition array
				$arrTransposedNotes = $this->getTransposedHalfTonesArray($arrInterval, (($intDiff < 0)? true:false));
				// getting keys transposition array 
				$arrTransposedKey   = $this->getTransposedKeyAltsNumberArray($arrTransposedNotes);

				// applying transposition
				$oAdapt->transposeKey($arrTransposedKey);
				$oAdapt->transposeNotes($arrTransposedNotes, $intDiff);
				$oAdapt->transposeHarmonies($arrTransposedNotes);
			}
		}else{
			echo "\n  ## MSCX ## Transposition is Turned [OFF]";
		}// end of transposition

		// setting size
		$intSize = $this->_getParam('size', 130, $arrParams);
		
		// do we have styles that have to be inserted into the final xml
		$strStylesheet = $this->_getParam('score.styles', false, $arrParams);
		
		if(is_string($strStylesheet) && !empty($strStylesheet)){
			// yes importing data
			$oAdapt->importIniStyle(Bootstrap::getPath($strStylesheet));
		}
		
		// do we have a global stylesheet
		$strStylesMss = $this->_getParam('score.mss', false, $arrParams);
		
		if(is_string($strStylesMss) && !empty($strStylesMss)){
			// yes importing data
			$oAdapt->importMsStyles(Bootstrap::getPath($strStylesMss));
		}
		
		// do we have a copyright text
		$strCopyright = $this->_getParam('copyright', false, $arrParams);
		
		if(is_string($strCopyright) && !empty($strCopyright)){
			// yes
			$oAdapt->setCopyright($strCopyright);
		}
		
		// do we have a title
		$strTitle = $this->_getParam('title', false, $arrParams);
		// checking value
		if(is_string($strTitle) && !empty($strTitle)){
			// yes
			$oAdapt->setTitle($strTitle);
		}
		
		// do we have the solfeggio flag set
		$intSolfeggio = $this->_getParam('solfeggio', false, $arrParams);

		// updating property if required
		if($intSolfeggio){
			echo "\n  ## MSCX ## Solfeggio is turned [ON]";
			// getting local
			$strLocal = Bootstrap::getInstance()->i18n()->getLocale(true);
			
			echo "\n  ## MSCX ## found local : ".$strLocal;
			
			if(in_array(strtolower($strLocal), array('fr', 'es', 'it'))){
				// turning off standard notes
				$oAdapt->setProperty('useStandardNoteNames', 0);
				// turning on Solfeggio notes
				$oAdapt->setProperty('useSolfeggioNoteNames', 1);
			}
		}
				
		// converting score
		$arrFiles = $oAdapt->convert($strPngFile, $intSize);
		
		echo "\n\n  ## MSCX ## End of Score generation\n\n";
		
		// returing file name
		return $arrFiles;
	}
}
