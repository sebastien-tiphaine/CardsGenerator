<?php

// format sound datas
function AnkiRdrTplPrepareSoundData($arrSound){

	// do we have a string instead of an array
	if(is_string($arrSound)){
		// yes
		$arrSound = array($arrSound);
	}

	// setting default result
	$arrCanvas = array(
			'file.src'   => 'invalidSoundData',
			'audio.type' => 'invalidSoundData',
			'file.ext'   => 'invalidSoundData'
	);

	if(!is_array($arrSound)){
		// invalid sound
		return array($arrCanvas);
	}

	// setting default result
	$arrResult   = array();
	$arrFirtItem = array();

	foreach($arrSound as $strData){
		
		// do we have a valid sound
		if(empty($strData)){
			throw new Exception('Empty sound file name found !');
		}
		
		// setting default sound data
		$arrSoundData = $arrCanvas;
		// setting src
		$arrSoundData['file.src'] = $strData;
		// getting file info
		$arrPathInfo  = pathinfo($strData);

		if(!is_array($arrPathInfo) || empty($arrPathInfo)){
			$arrResult[] = $arrSoundData;
			continue;
		}

		// extracting ext
		$strExt = strtolower($arrPathInfo['extension']);
	
		// setting audio type
		switch($strExt){
			case 'mp3':
				$arrSoundData['audio.type'] = 'audio/mpeg';
				break;
			case 'm4a':
				$arrSoundData['audio.type'] = 'audio/mp4';
				break;
			default:
				$arrSoundData['audio.type'] = 'audio/'.$strExt;
		}

		// setting extension
		$arrSoundData['file.ext'] = $strExt;

		// mp3 should be the first entry
		if($strExt == 'mp3'){
			$arrFirtItem[] = $arrSoundData;
			continue;
		}

		// setting sound data
		$arrResult[] = $arrSoundData;
	}

	return array_merge($arrFirtItem, $arrResult);
}

// return audio control html
function AnkiRdrTplGetAudioControl($arrAudio, $strId){

	// getting formated sound params
	$arrSound = AnkiRdrTplPrepareSoundData($arrAudio);

	$strLoad    = '<div class="audioLoading" style="display:none">';
	$strNoAudio = '';
	$strCtrl    = '<audio controls="controls" id="'.$strId.'">';

	foreach($arrSound as $arrData){
		// setting crtl
		$strCtrl.= '<source src="'.$arrData['file.src'].'" type="'.$arrData['audio.type'].'">';
		// setting noaudio
		$strNoAudio.= '[ <a href="'.$arrData['file.src'].'">'.$arrData['file.ext'].'</a> ]';
		// setting fake img for the sound to be packaged by anki
		$strLoad.= '<img src="'.$arrData['file.src'].'">';
	}

	// closing loadTag
	$strLoad.= '</div>';
	// finalize Ctrl
	$strCtrl.= $strNoAudio.'</audio>';
	
	// setting result
	return $strCtrl.$strLoad;
}

	
