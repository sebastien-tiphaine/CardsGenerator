<?php

require_once(__DIR__.'/OutputAnkiAbstract.php');

class OutputAnkiPkg extends OutputAnkiAbstract{

	// list of medias
	protected $_arrMedias = array();

	// last id
	protected $_intLastGenId = false;

	// current deck id
	protected $_intPkgDeckId = false;
	
	// current model id
	protected $_intPkgModelId = false;
	
	// deck configuration id (DConf)
	protected $_intPkgDConfId = false;

	// name of the card set
	protected $_strCardSetName = false;

	// version of the package. Should be automaticaly defined
	// using config params
	protected $_strVersion = false;
	// integer representation of the version value
	protected $_intVersion = false;

	// database link
	protected $_oDb = null;
	
	// is database in error
	protected $_intDbError = false;

	// default params
	protected $_arrParams = array(
			'Media.image' => array(
				'size' => 130,
				'dir'  => 'apkg'
			),
			'Media.score' => array(
				'size' => 130,
				'dir'  => 'apkg'
			),
			'Media.sound' => array(
				'dir'      => 'apkg',
				'uniqname' => true,
				'format'   => array('mp3')
			),
			'layout.front' => false,//'source/layout/anki-front.html',
			'layout.back'  => false,//'source/layout/anki-back.html',
			'layout.css'   => false,//'source/styles/cards.css',
			'version'  	   => '1',
			'revision' 	   => '0',
			'sql.createscript' => false//'source/SQL/AnkiPkgDbSchema.sql'			
	);

	// returns subdirs
	protected function _getSubDirs(){
		return array('apkg');
	}

	// initialize object
	protected function _init(){
		
		// retreive the cardset name
		$this->_getCardSetName();
		// done	
		return $this;
	}

	// returns true if db is in error state
	protected function _hasDbErrorState(){
		return $this->_intDbError;
	}

	// return database object
	protected function _getDb(){
		
		// can we return the db object
		if($this->_hasDbErrorState()){
			// no
			return null;
		}
		
		// is database created
		if($this->_oDb instanceof SQLite3){
			// yes
			return $this->_oDb;
		}
		
		// initializing db -----
		
		// creating the db
		$this->_oDb = new SQLite3($this->getOutputDir().'/apkg/collection.anki2');
					
		// getting creation script
		$strCreateScript = $this->_renderTemplate($this->getParam('sql.createscript'));
		
		// importing script
		if(!$this->_oDb->exec($strCreateScript)){
			// output info
			$this->_cliOutput(__CLASS__.':: ERROR : Not able to import sql createscript. Datas won\'t be imported.');
			// removing data base link
			$this->_oDb = null;
			// setting database in error state
			$this->_intDbError = true;
		}
		
		// setting cols data
		$this->_pkgSetCol();
		
		// done
		return $this->_oDb;
	}

	// execute a query
	protected function _queryDb($strSQL){
	
		if(!is_string($strSQL) || empty($strSQL)){
			throw new Exception('String expected !');
		}
	
		// is db in error state
		if($this->_hasDbErrorState()){
			// yes, skipping query
			return false;
		}
		
		// getting database link
		$oDb = $this->_getDb();
		
		// is db in error state
		if($this->_hasDbErrorState()){
			// yes, skipping query
			return false;
		}
		
		// return result of query execution
		return $oDb->query($strSQL);
	}

	// adds a media file name to the media file list
	protected function _addMediaToList($mMediaFile){
		
		// do we have a full media array given as param
		if(is_array($mMediaFile) && isset($mMediaFile['media'])){
			// yes
			// extracting data
			$mMediaData = $mMediaFile['media'];
			
			if(is_string($mMediaData)){
				// adding media to the list
				$this->_addMediaToList($mMediaData);
				// done
				return $this;
			}
			
			if(is_array($mMediaData)){
				foreach($mMediaData as $mKey => $strMediaSrc){
					$this->_addMediaToList($strMediaSrc);
				}
			}
			
			// done
			return $this;
		}
		
		// do we have a string given
		if(!is_string($mMediaFile) || empty($mMediaFile)){
			$this->_cliOutput(__CLASS__.':: WARN : invalid media file found !');
			return $this;
		}
		
		// extracting media name
		$strName = basename($mMediaFile);
		
		// do we already have this file in the list
		if(in_array($strName, $this->_arrMedias)){
			// yes
			// nothing to do
			return $this;
		}
		
		// adding media to the list
		$this->_arrMedias[] = $strName;
		
		// done
		return $this;
	}

	// encode data to json format
	protected function _pkgJsonEncode($mData){
		
		return json_encode($mData);
	}

	// format media for a local usage
	protected function _formatMedia($mMediaFile, $strDataType = false){
		
		// could we add the media to the file list
		if($strDataType && $this->hasParam('Media.'.$strDataType)){
			// yes
			$this->_addMediaToList($mMediaFile);
		}
				
		// calling parent method
		return parent::_formatMedia($mMediaFile, $strDataType);
	}

	// generated an id of 13 chars
	protected function _generateId(){
		
		// do we already have an id generated
		if(!is_numeric($this->_intLastGenId)){
			// no
			// initializing it
			$this->_intLastGenId = intval(random_int(1000000000, (pow(2,32)-1)));
		}
		
		// updating id
		$this->_intLastGenId+= random_int(100, 999) ;
		
		// done
		return $this->_intLastGenId;
	}

	// returns current package version
	protected function _getPkgVersion($intIntVer = true){
	
		if(!$this->_strVersion){
			// setting revision date
			$strRevDate = date('Ymd');
			// setting version in string format
			$this->_strVersion = $this->getParam('version').'.'.$this->getParam('revision').'-'.date('Ymd');
			// setting version in integer format
			$this->_intVersion = intval(date('Ymd').$this->getParam('version').$this->getParam('revision'));
		}
		
		// done
		return ($intIntVer) ? $this->_intVersion:$this->_strVersion;
	}

	// return current cardSet name
	protected function _getCardSetName($intWithVersion = false){
		
		// do we have to get card set name from config
		if(!is_string($this->_strCardSetName) || empty($this->_strCardSetName)){
			// yes
			// getting cardSetName from config
			$this->_strCardSetName = Bootstrap::getInstance()->{'CardGenerator.title'};
			
			// do we have a valid title
			if(!is_string($this->_strCardSetName) || empty($this->_strCardSetName)){
				// no
				$this->_cliOutput(__CLASS__.':: WARN : Not able to retreive CardGenerator.title to set it as cardSetName. Using "__Card Set Default__" as title');
				// using default title
				$this->_strCardSetName = "__Card Set Default__";
			}
		}
		
		// extracting cardSetName
		$strCardSetName = $this->_strCardSetName;
		
		// do we have to return the version
		if($intWithVersion){
			// yes
			$strCardSetName.= '-'.$this->_getPkgVersion(false);
		}	
		
		return $strCardSetName;
	}

	// return deck id
	protected function _pkgGetDeckId(){
		
		// do we have an id generated
		if(!$this->_intPkgDeckId){
			// no
			$this->_intPkgDeckId = $this->_generateId();
		}
		
		return $this->_intPkgDeckId;
	}
	
	// return deck conf id
	protected function _pkgGetDConfId(){
		
		// do we have an id generated
		if(!$this->_intPkgDConfId){
			// no
			$this->_intPkgDConfId = $this->_generateId();
		}
		
		return $this->_intPkgDConfId;
	}
	
	// return model id
	protected function _pkgGetModelId(){
		
		// do we have an id generated
		if(!$this->_intPkgModelId){
			// no
			$this->_intPkgModelId = $this->_generateId();
		}
		
		return $this->_intPkgModelId;
	}
	
	// returns collection settings json string
	protected function _pkgGetCollectionSettings(){
		
			$oColSet = new stdClass();
			$oColSet->nextPos 		= 1;
			$oColSet->estTimes 		= true;
			$oColSet->activeDecks 	= array(1);
			$oColSet->sortType 		= "noteFld";
			$oColSet->timeLim 		= 0;
			$oColSet->sortBackwards = false;
			$oColSet->addToCur 		= true;
			$oColSet->curDeck 		= 1;
			$oColSet->newBury 		= true;
			$oColSet->newSpread 	= 0;
			$oColSet->dueCounts 	= true;
			$oColSet->curModel 		= time();
			$oColSet->collapseTime 	= 1200; 
		
			return $this->_pkgJsonEncode($oColSet);
	}

	protected function _pkgGetDeckSettings(){
		
		// getting current deck id
		$intDeckId = $this->_pkgGetDeckId();	
		
		// setting global decks lis
		$oDecks = new stdClass();
		
		// setting default deck object
		$oDeckSet = new stdClass();
		$oDeckSet->desc = "";
		$oDeckSet->name = "Default";
		$oDeckSet->extendRev = 50;
		$oDeckSet->usn = 0;
		$oDeckSet->collapsed = false;
		$oDeckSet->newToday  = array(0,0);
		$oDeckSet->timeToday = array(0,0);
		$oDeckSet->dyn		 = 0;
		$oDeckSet->extendNew = 10;
		$oDeckSet->conf 	 = 1;
		$oDeckSet->revToday  = array(0,0);
		$oDeckSet->lrnToday  = array(0,0);
		$oDeckSet->id 		 = 1;
		$oDeckSet->mod 		 = time();
		
		// setting card set deck, using values of the default deck
		$oCardSetDeck 		= clone $oDeckSet;
		$oCardSetDeck->name = $this->_getCardSetName(true);
		$oCardSetDeck->id 	= $intDeckId;
		$oCardSetDeck->conf = $this->_pkgGetDConfId();
		
		// getting description
		$strDeckDesc = Bootstrap::getInstance()->{'CardGenerator.description'};
		
		// replacing new line and carriage return chars
		$strDeckDesc = str_replace('\r', chr(015), $strDeckDesc);
		$strDeckDesc = str_replace('\n', chr(012), $strDeckDesc);
		
        // do we have a valid description
        if(is_string($strDeckDesc) || empty($strDeckDesc)){
            $oCardSetDeck->desc = $strDeckDesc;
        }
		
		//$oCardSetDeck->desc = Bootstrap::getInstance()->i18n()->_t('Description', 'OutputAnkiPkg', $this->_getCardSetName(false));
		
		// adding decks to the list
		$oDecks->{"1"} = $oDeckSet;
		$oDecks->{$intDeckId.""} = $oCardSetDeck;
				
		return $this->_pkgJsonEncode($oDecks);
	}

	// returns formated list of fields for the model json object
	protected function _pkgGetModelFields(){
		
		// setting ord default key
		$intKey = 0;
		
		// list of fields
		$arrFields = array();
		
		// setting default field object
		$oField = new stdClass();
		$oField->name = ""; 
		$oField->media = array(); 
		$oField->sticky = false;
		$oField->rtl    = false; 
		$oField->ord    = 0;
		$oField->font   = "Arial"; 
		$oField->size   = 20;
		
		// setting id field
		$oId = clone $oField;
		$oId->name = "Id";
		$oId->ord  = $intKey;
		
		// adding id field to the list
		$arrFields[] = $oId;
		
		foreach($this->_arrRequiredEntries as $strFieldName){
			
			// updating key
			$intKey++;
			
			// setting field
			$oNewFld = clone $oField;
			$oNewFld->name = $strFieldName;
			$oNewFld->ord  = $intKey;
			
			// adding id field to the list
			$arrFields[] = clone $oNewFld;
			
		}

		// done
		return $arrFields;
	}

	// returns a json representation of the pkg model
	protected function _pkgGetModelSettings(){
		
		// getting current deck id
		$intDeckId  = $this->_pkgGetDeckId();
		// getting model id
		$intModelId = $this->_pkgGetModelId();
		
		// setting template
		$oTmpl = new stdClass();
		$oTmpl->name = $this->_getCardSetName(true).'-Template'; 
		$oTmpl->qfmt = $this->_renderTemplate($this->getParam('layout.front')); 
		$oTmpl->did  = null; 
		$oTmpl->bafmt = ""; 
		$oTmpl->afmt  = $this->_renderTemplate($this->getParam('layout.back'));
		$oTmpl->ord   = 0; 
		$oTmpl->bqfmt = "";
		
		// setting models list
		$oModels = new stdClass();
		// setting model object
		$oMod 			 = new stdClass();
		$oMod->vers 	 = array($this->_getPkgVersion(false)); 
		$oMod->name 	 = $this->_getCardSetName(true).'-Model';
		$oMod->tags 	 = array(); 
		$oMod->did  	 = $intDeckId; 
		$oMod->usn  	 = -1; 
		$oMod->req  	 = array(array(0, "any", array(0, 3, 4, 7)));
		$oMod->flds 	 = $this->_pkgGetModelFields();
		$oMod->sortf 	 = 0; // id of field used to sort cards
		$oMod->tmpls	 = array($oTmpl);
		$oMod->mod  	 = time(); 
		$oMod->latexPost = "\\end{document}"; 
		$oMod->type      = 0; 
		$oMod->id        = $intModelId.""; 
		$oMod->css       = $this->_renderTemplate($this->getParam('layout.css'));
		$oMod->latexPre  = "\\documentclass[12pt]{article}\n\\special{papersize=3in,5in}\n\\usepackage[utf8]{inputenc}\n\\usepackage{amssymb,amsmath}\n\\pagestyle{empty}\n\\setlength{\\parindent}{0in}\n\\begin{document}\n";
		
		// adding model to the list
		$oModels->{$intModelId.""} = $oMod;
		
		// done
		return $this->_pkgJsonEncode($oModels);
	}

	// returns dconf settings
	protected function _pkgGetDConfSettings(){
	
			// setting DConfsList
			$oDconfs = new stdClass();
	
			// setting default lapse
			$oLapse 			 = new stdClass();
			$oLapse->leechFails  = 8;
			$oLapse->minInt		 = 1;
			$oLapse->delays 	 = array(10);
			$oLapse->leechAction = 0;
			$oLapse->mult 		 = 0;
	
			// setting default rev
			$oRev 			= new stdClass();
			$oRev->perDay 	= 200;
			$oRev->fuzz  	= 0.05;
			$oRev->ivlFct 	= 1;
			$oRev->maxIvl 	= 36500;
			$oRev->ease4  	= 1.3;
			$oRev->bury 	= true;
			$oRev->minSpace = 1;
			//$oRev->hardFactor = 1.2;
			
			// setting default new
			$oNew 				 = new stdClass();
			$oNew->perDay 		 = 20;
			$oNew->delays 		 = array(1,10);
			$oNew->separate 	 = true;
			$oNew->ints 		 = array(1,4,7);
			$oNew->initialFactor = 2500;
			$oNew->bury 		 = true;
			$oNew->order 		 = 1;
			
			// setting default dconf
			$oDCnfDefault 		   	= new stdClass();
			$oDCnfDefault->name    	= "Default";
			$oDCnfDefault->replayq 	= true;
			$oDCnfDefault->lapse   	= clone $oLapse;
			$oDCnfDefault->rev 		= clone $oRev;
			$oDCnfDefault->timer 	= 0;
			$oDCnfDefault->maxTaken = 60;
			$oDCnfDefault->usn 		= 0;
			$oDCnfDefault->{'new'}	= clone $oNew;
			$oDCnfDefault->mod 		= 0;
			$oDCnfDefault->id 		= 1;
			$oDCnfDefault->autoplay = true;
	
			// setting local conf
			$oCardSetDconf 			= clone $oDCnfDefault;
			$oCardSetDconf->name 	= $this->_getCardSetName(true);
			$oCardSetDconf->id 		= $this->_pkgGetDConfId();
			$oCardSetDconf->lapse  	= clone $oLapse;
			$oCardSetDconf->autoplay = false;
			$oCardSetDconf->replayq  = false;
			$oCardSetDconf->dyn      = false;
			$oCardSetDconf->usn 	 = -1;
			
			$oCardSetDconf->rev 		= clone $oRev;
			$oCardSetDconf->rev->perDay = 200;
			
			$oCardSetDconf->{'new'}			= clone $oNew;
			$oCardSetDconf->{'new'}->perDay = 30;
			
			// adding DConf to the list
			$oDconfs->{"1"} = $oDCnfDefault;
			$oDconfs->{$this->_pkgGetDConfId().""} = $oCardSetDconf;
		
			// done
			return $this->_pkgJsonEncode($oDconfs);
	}

	// sets sql script for default col 
	protected function _pkgSetCol(){
	
		// INSERT INTO col VALUES( id, crt, mod, scm, ver, dty, usn, ls, conf, models, decks, dconf, tags );
		// id: integer (13) : arbitrary number since there is only one row
		// crt: integer : created timestamp
		// mod: integer : last modified in milliseconds
		// scm: integer : schema mod time: time when "schema" was modified : timestamp
		// ver: integer : version
		// dty: 0 : - We can leave it untouched.
		// usn: 0 : - We can leave it untouched.
		// ls: 0  : - We can leave it untouched.
		// conf: json string : configuration of the collection.
		// models: json string : models available in the collection 
		// decks: json string:  Decks of the collection. Leave the first (default deck) untouched, 
		//                      but for the second deck, modify the deck name and generate the deck id as a random integer. 
		// dconf: json string : Configuration of each deck. We can leave it untouched. 
		// tags : json string : {}. 
	
		// setting fields
		$intId = $this->_generateId(); 
		$intCrt = time();
		$intMod = time();
		$intScm = time();
		$intVer = 11;
		$intDty = 0;
		$intUsn = 0;
		$intLs  = 0;
		$strConf   = SQLite3::escapeString($this->_pkgGetCollectionSettings());
		$strModels = SQLite3::escapeString($this->_pkgGetModelSettings());
		$strDecks  = SQLite3::escapeString($this->_pkgGetDeckSettings());
		$strDConf  = SQLite3::escapeString($this->_pkgGetDConfSettings());
		
		// sql script											
		$this->_queryDb('INSERT INTO col VALUES( '.$intId.', '.$intCrt.', '.$intMod.', '.$intScm.', '.$intVer.', '.$intDty.', '.$intUsn.', '.$intLs.', \''.$strConf.'\', \''.$strModels.'\', \''.$strDecks.'\', \''.$strDConf.'\', \'{}\' );');
	
		// done
		return $this;
	}

	// apply rendering
	protected function _render($strGroup, $arrCard, $strNote, $strCardRdrId, $arrVars){
		
		// updating card count
		$this->_intCardCount++;
		
		// validating card datas
		$arrCard = $this->_validateCard($arrCard);
		// extracting values without keys
		//$arrCard = array_values($arrCard);
		
		// inserting card id
		array_unshift($arrCard, $this->_intCardCount);
		
		//INSERT INTO notes VALUES( id, guid, mid, mod, usn, tags, flds, sfld, csum, flags, data);
		// id: integer (13) : The note id, generate it randomly (timestamp+micro).
		// guid: string (10+): a GUID identifier, generate it randomly. 
		// mid: integer - Identifier of the model, use the one found in the models
		// mod: integer - timestamp
		// usn: -1 : We can leave it untouched
		// tags: string : Tags, visible to the user, which can be used to filter cards (e.g. "verb"). May be the card categories.
		// flds: string : Card content, front and back, separated by \x1f char. 
		// sfld: string : CardName
		// csum: integer : A string SHA1 checksum of sfld, limited to 8 digits. PHP: (int)(hexdec(getFirstNchars(sha1($sfld), 8)));
		// flags: 0 - We can leave it untouched.
		// data: - We can leave it untouched.
		
		// getting note id
		$intNoteId  = $this->_generateId(); 
		$strGuid    = SQLite3::escapeString(md5(serialize($arrCard).time().$this->_generateId()));
		$intModelId = $this->_pkgGetModelId();
		$intMod     = time();
		$intUsn     = -1;		
		$strTags    = SQLite3::escapeString($arrCard[CardGenerator::CARDTAGS]);
		$strFlds    = SQLite3::escapeString(implode("\x1f", $arrCard));
		$strSfld    = SQLite3::escapeString($arrCard[CardGenerator::CARDNAME]);
		$strCsum    = SQLite3::escapeString(hexdec(substr(sha1($strSfld), 0, 8)));
		
		// sql script
		$this->_queryDb('INSERT INTO notes VALUES( '.$intNoteId.', \''.$strGuid .'\', '.$intModelId.', '.$intMod.', '.$intUsn.', \''.$strTags.'\', \''.$strFlds.'\', \''.$strSfld.'\', '.$strCsum.', 0, \'\');');
		
		// INSERT INTO cards VALUES( id, nid, did, ord, mod, usn, type, queue, due, ivl, factor, reps, lapses, left, odue, odid, flags, data);
		// id: The card id, generate it randomly.
		// nid: The note id this card is associated with.
		// did: The deck id this card is associated with.
		// ord: 0 - template number - We can leave it untouched.
		// mod: 1398130110 - Same as note's mod field
		// usn: -1 - We can leave it untouched.
		// type: 0 - (0=new, 1=learning, 2=due, 3=filtered) We can leave it untouched.
		// queue: 0 - We can leave it untouched.
		// due: 484332854 - card id or random int. We can leave it untouched.
		// ivl: 0 - We can leave it untouched.
		// factor: 0 - We can leave it untouched.
		// reps: 0 - We can leave it untouched.
		// lapses: 0 - We can leave it untouched.
		// left: 0 - We can leave it untouched.
		// odue: 0 - We can leave it untouched.
		// odid: 0 - We can leave it untouched.
		// flags: 0 - We can leave it untouched.
		// data: - We can leave it untouched.
		
		$intCardId  = $this->_generateId(); 
		$intDeckId  = $this->_pkgGetDeckId();
		$intOrd     = 0;
		$intDue     = $intCardId;
		
		// sql script
		$this->_queryDb('INSERT INTO cards VALUES( '.$intCardId.', '.$intNoteId.', '.$intDeckId.', '.$intOrd.', '.$intMod.', '.$intUsn.', 0, 0, '.$intDue.', 0, 0, 0, 0, 0, 0, 0, 0, \'\');');
	}

	// creates an apkg archive of all apkg folder
	protected function _pkgMakeAPkg(){
		
		// setting zip file name
		$strZipFile = $this->getOutputDir().'/'.$this->_getCardSetName().'-'.$this->_getPkgVersion(false).'.apkg';
		
		// getting zip object
		$oZip = new ZipArchive();
		
		// creating archive
		if(!$oZip->open($strZipFile, ZipArchive::CREATE)){
			// not able to create file
			$this->_cliOutput(__CLASS__.':: WARN : not able to create zip file : '.$strZipFile);
			// nothing more to do
			return $this;
		}
		
		// adding all file to the root of the archive
		$oZip->addGlob($this->getOutputDir().'/apkg/*', GLOB_BRACE, array('remove_all_path' => true));
		// closing archive
		$oZip->close();
		// done
		return $this;
	}

	// finalize rendering
	public function finalize(){
	
		// setting media list to object
		$oMedias = new stdClass();
	
		// rename all the media
		foreach($this->_arrMedias as $intKey => $strFilename){
			
			// getting real file path
			$strRealPath = $this->getOutputDir().'/apkg/'.$strFilename;
			// is the file in the right path
			if(!is_file($strRealPath)){
				// no
				$this->_cliOutput(__CLASS__.':: WARN : media file : '.$strFilename.' is not in the apkg folder : '.$this->getOutputDir().'/apkg/.');
				continue;
			}
			
			// setting new name
			$strNewName = $this->getOutputDir().'/apkg/'.$intKey;
			
			// renaming file
			if(!rename($strRealPath,$strNewName)){
				$this->_cliOutput(__CLASS__.':: WARN : not able to rename media file '.$strFilename.' to '.$intKey);
			}
			
			// adding media file to the list
			$oMedias->{$intKey.""} = $strFilename;
		}
				
		// writing file list to media file
		file_put_contents($this->getOutputDir().'/apkg/media', $this->_pkgJsonEncode($oMedias));
		 
		// creating the archive
		$this->_pkgMakeAPkg();
		
		// done
		return $this;
	}
}
