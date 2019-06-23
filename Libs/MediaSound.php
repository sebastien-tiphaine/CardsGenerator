<?php

require_once(__DIR__.'/MediaAbstract.php');

class MediaSound extends MediaAbstract{

	// apply rendering
	protected function _render($arrMedia, $arrParams, $strNote, $intScaleType){		

		// checking sound source
		if(!isset($arrMedia['src']) || empty($arrMedia['src'])){
			throw new Exception(__CLASS__.':: given sound has no valid src param.');
		}

		// setting default uniq flag
		$intUniqName = false;

		// do we have to have a uniq name
		if(isset($arrParams['uniqname'])){
			// yes
			$intUniqName = $arrParams['uniqname'];
		}

		// do we have a format param
		if(isset($arrParams['format']) && !empty($arrParams['format'])){
			// yes
			if(is_string($arrParams['format'])){
				$arrParams['format'] = array($arrParams['format']);
			}

			// init filtered media
			$arrFilteredMedia = array();

			// checking source
			if(!is_array($arrMedia['src'])){
				$arrMedia['src'] = array($arrMedia['src']);
			}

			foreach($arrMedia['src'] as $strMediaFile){
				
				// checking path
				$strMediaFile = Bootstrap::getPath($strMediaFile);
				// extracting media info
				$arrMediaInfo = pathinfo($strMediaFile);
				
				// do we have datas about the file
				if(!is_array($arrMediaInfo)){
					// no. 
					throw new Exception(__CLASS__.':: not able to extract datas from file : '.$strMediaFile);
				}
				
				// checking file basename
				if(!isset($arrMediaInfo['basename']) || empty($arrMediaInfo['basename'])){
					// no. 
					throw new Exception(__CLASS__.':: not able to extract basename from file : '.$strMediaFile);
				}
				// checking file dirname
				if(!isset($arrMediaInfo['dirname']) || empty($arrMediaInfo['dirname'])){
					// no. 
					throw new Exception(__CLASS__.':: not able to extract dirname from file : '.$strMediaFile);
				}
				
				// cleaning file name
				$arrMediaInfo['basename'] = $this->_getCleanFileName($arrMediaInfo['basename']);
				
				// updating file path
				$strMediaFile = $arrMediaInfo['dirname'].'/'.$arrMediaInfo['basename'];
				
				// do we have an existing file
				if(!file_exists($strMediaFile)){
					// no
					throw new Exception(__CLASS__.':: media file does not exists : '.$strMediaFile);
				}
				
				// can we use the file
				if(in_array($arrMediaInfo['extension'], $arrParams['format'])){
					// yes
					$arrFilteredMedia[] = $strMediaFile;
				}
			}

			// updating $arrMedia['src']
			$arrMedia['src'] = $arrFilteredMedia;

			// checking sound source
			if(empty($arrMedia['src'])){
				throw new Exception(__CLASS__.':: no sound in a supported format found. Supported formats : '.implode(',', $arrParams['format']));
			}
		}

		// do we have to only keep a single media
		if(isset($arrParams['single']) && $arrParams['single']){
			// yes
			if(is_array($arrMedia['src'])){
				$arrMedia['src'] = $arrMedia['src'][0];
			}
		}

		// copying file
		return $this->_mediaCopy($this->_getOutputDir(), $arrMedia['src'], $intUniqName);
	}
}
