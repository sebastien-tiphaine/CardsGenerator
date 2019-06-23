<?php

require_once(__DIR__.'/OutputAnkiAbstract.php');

class OutputAnkiCsv extends OutputAnkiAbstract{

	// array of generated cards
	protected $_strCSV = '';

	// default params
	protected $_arrParams = array(
			'Media.image' => array(
				'size' => 130,
				'dir'  => 'medias'
			),
			'Media.score' => array(
				'size' => 130,
				'dir'  => 'medias'
			),
			'Media.sound' => array(
				'dir'      => 'medias',
				'uniqname' => true,
				'format'   => array('mp3', 'ogg')
			),
	);

	// returns subdirs
	protected function _getSubDirs(){
		return array('medias');
	}

	// initialize object
	protected function _init(){
		// nothing to do
		return $this;
	}

	// apply rendering
	protected function _render($strGroup, $arrCard, $strNote, $strCardRdrId, $arrVars){

		// updating card count
		$this->_intCardCount++;

		// setting default filtered card
		$arrFilteredCard = array(
			'id' => $this->_intCardCount,
		);

		// validating card datass
		$arrCard = $this->_validateCard($arrCard);

		// checking card content to csv compatible
		foreach($this->_arrRequiredEntries as $strKey){

			if(strpos($arrCard[$strKey], ';') !== false){
				if($intIsFirst){
					$this->_cliOutput('');
					$intIsFirst = false;
				}
				$this->_cliOutput(__CLASS__.':: WARN : the card field : '.$strKey.' is containing a semi-colon (;) ! It will be removed ! Please avoid html special chars.');
				$arrCard[$strKey] = str_replace(';','', $arrCard[$strKey]);
			}

			$arrFilteredCard[$strKey] = $arrCard[$strKey];
		}
	
		// implode card array.
		$this->_strCSV.= implode(';', $arrFilteredCard)."\r\n";
		// done
		return $this;
	}

	// finalize rendering
	public function finalize(){

		// writing file
		file_put_contents($this->getOutputDir().'/cards.csv', $this->_strCSV);
		// done
		return $this;
	}
}
