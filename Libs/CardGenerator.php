<?php

require_once(__DIR__.'/SvgAdapt.php');
require_once(__DIR__.'/OutputAbstract.php');
require_once(__DIR__.'/MediaAbstract.php');
require_once(__DIR__.'/MusicTemplating.php');
require_once(__DIR__.'/CardText.php');
require_once(__DIR__.'/TemplateRenderingLogic.php');
require_once(__DIR__.'/Bootstrap.php');

class CardGenerator extends MusicTemplating{
		
		const CARDID       		  = 'CardId';
		const QUESTION     		  = 'Question';
		const QUESTIONINFO 		  = 'QuestionInfo';
		const ANSWER       		  = 'Answer';
		const ANSWERINFO   		  = 'AnswerInfo';
		const SCALETYPE   		  = 'ScaleType';
		const CALLBACKCONDITION   = 'CallBackCondition';
		const CARDNAME     		  = 'Name';
		const CARDTAGS     		  = 'Tags';
		const CARDCATEGORY        = 'Category';
		const CARDDISPLAYCATEGORY = 'CategoryDisplay';
		const CARDTITLE 		  = 'Title';
		const CARDONTHEFLY 		  = 'OnTheFly';
		const CARDTYPE     		  = 'CardType';
		const CARDONTHEFLYFUNC 	  = 'OnTheFlyFunc';
		
		// templates
		protected $_cardsTemplates = array(); 

		// current templates group
		protected $_strTemplateGroup = 'DefaultGroup';
		
		// default title
		protected $_strTitle = false;
		
		// outputdir
		protected $_strOutputDir = 'cards';

		// output mods
		protected $_arrOutputMods = array();

		// media mods
		protected $_arrMediaMods = array();
		
		// flag turned to true when cards template are parsed in order to generate cards
		protected $_intGeneratingCards                = false;
		// tone that for which cards are beeing generated
		protected $_strGeneratingTone                 = false;
		// tone number for which cards are beeing generated
		protected $_intGeneratingToneNumber           = false;
		// number of tones that for which cards are beeing generated
		protected $_intGeneratingTotalToneNumber      = false;
		// purcent of generating progress
		protected $_intGeneratingTonesPurcentProgress = false;
		// array of generated cards for the cards condition condition SingleCard
		protected $_cardsCondition_arrCardGeneratedList = array();
		// current generator id
		protected $_strGeneratorId = false;
		
		public function __construct($strTitle = false, $mOutputs = false, $mMedias = false){

			/// TITLE -------------

			// do we have to get the title from config
			if($strTitle === false){
				// yes
				$strTitle = Bootstrap::getInstance()->{'CardGenerator.title'};
			}

			if(!is_string($strTitle) || empty($strTitle)){
				throw new Exception('invalid title given. String expected');
			}

			/// OUTPUTDIR -------------

			// getting locale for outfilename
			$strLocale = Bootstrap::getInstance()->i18n()->getLocale();

			$this->_strOutputDir = Bootstrap::getInstance()->{'CardGenerator.outputfolder'}.'/'.str_replace(' ', '_', trim($strTitle)).'-'.$strLocale.'-'.date('Ymd-His');
			// setting generator id
			$this->_strGeneratorId = md5(microtime().mt_rand());

			// checking output dir
			if(!is_dir($this->_strOutputDir)){
				mkdir($this->_strOutputDir, 0755, true);
			}

			// do we have to retreive output from config file
			if($mOutputs === false){
				// yes
				$mOutputs = Bootstrap::getInstance()->{'CardGenerator.output'};
			}
	
			// do we have output mods
			if($mOutputs){
				// yes
				$this->addOutput($mOutputs);
			}

			// do we have to retreive medias from config file
			if($mMedias === false){
				// yes
				$mMedias = Bootstrap::getInstance()->{'^CardGenerator.medias'};
			}

			// do we have some medias modules
			if($mMedias){
				// yes
				$this->addMedia($mMedias);
			}
			// done
			return $this;
		}

		// returns generator main title
		public function getTitle(){
			return $this->_strTitle;
		}

		// call $strMethod on each media module
		protected function _triggerMedia($strMethod, $arrParams = array(), $intCliOut = false){
			return $this->_triggerObjectArray($this->_arrMediaMods, $strMethod, $arrParams, $intCliOut);
		}

		// returns true if media $strName exists
		protected function _hasMedia($strName){

			if(!is_string($strName) || empty($strName)){
				throw new Exception('invalid media name given. String expected');
			}

			$strName = $this->_getSingular($strName);

			return array_key_exists($strName, $this->_arrMediaMods);
		}

		// return media for $strname
		protected function _getMedia($strName){

			if(!$this->_hasMedia($strName)){
				throw new Exception('unknown media : '.$strName);
			}

			// TODO : remove all call to singular or plural
			//$strName = $this->_getSingular($strName);

			return $this->_arrMediaMods[$strName];
		}

		// add a new media module
		public function addMedia($strName, $oMedia = null){
			
			// do we have an array of media mods
			if(is_array($strName)){
				// extracting media list
				$arrMedias = $strName;
				// yes
				foreach($arrMedias as $strName => $oMedia){
					$this->addMedia($strName, $oMedia);
				}

				return $this;
			}

			if(!is_string($strName) || empty($strName)){
				throw new Exception('invalid media name given. String expected');
			}

			// do we have a string
			if(is_string($oMedia)){
				// yes
				// checking if class name can be found in config
				if(!Bootstrap::getInstance()->getConfig()->hasVar($oMedia.'.classname')){
					throw new Exception('Missing classname in config file for media : '.$oMedia);
				}

				// getting class name
				$strClassName = Bootstrap::getInstance()->{$oMedia.'.classname'};

				// loading class
				require_once(__LIBS__.'/'.$strClassName.'.php');

				// setting output object
				$oMedia = new $strClassName(Bootstrap::getInstance()->{'^'.$oMedia});
			}


			if(!$oMedia instanceof MediaAbstract){
				throw new Exception('invalid media object given');
			}

			// configuring media
			$oMedia->setGenerator($this);
			$oMedia->setOutputDir($this->_strOutputDir.'/tmp');

			// adding module localy
			$this->_arrMediaMods[$strName] = $oMedia;

			// done
			return $this;
		}

		// call $strMethod on each output module
		protected function _triggerOutput($strMethod, $arrParams = array(),$intCliOut = false){
			return $this->_triggerObjectArray($this->_arrOutputMods, $strMethod, $arrParams, $intCliOut);
		}

		// add a new output module
		public function addOutput($oOutput){

			// do we have an array of output mods
			if(is_array($oOutput)){
				// yes
				foreach($oOutput as $oOutMod){
					$this->addOutput($oOutMod);
				}

				return $this;
			}

			// do we have a string
			if(is_string($oOutput)){
				// yes
				// checking if class name can be found in config
				if(!Bootstrap::getInstance()->getConfig()->hasVar($oOutput.'.classname')){
					throw new Exception('Missing classname in config file for output : '.$oOutput);
				}

				// getting class name
				$strClassName = Bootstrap::getInstance()->{$oOutput.'.classname'};

				// loading class
				require_once(__LIBS__.'/'.$strClassName.'.php');

				// setting output object
				$oOutput = new $strClassName(false, Bootstrap::getInstance()->{'^'.$oOutput});
			}

			if(!$oOutput instanceof OutputAbstract){
				throw new Exception('invalid output object given');
			}

			// configuring output module
			$oOutput->setRootDir($this->_strOutputDir);
			$oOutput->setGenerator($this);

			// adding module localy
			$this->_arrOutputMods[] = $oOutput;
			
			// done
			return $this;
		}

		// add a group
		public function addTemplateGroup($strGroup){

			if(!is_string($strGroup) || empty($strGroup)){
				throw new Exception('Invalid group name. String expected');
			}

			if(!isset($this->_arrTemplates[$strGroup])){
				$this->_arrTemplates[$strGroup] = array();
			}

			return $this;
		}

		public function setTemplateGroup($strGroup){

			if(!is_string($strGroup) || empty($strGroup)){
				throw new Exception('Invalid group name. String expected');
			}

			// setting cli output
			$this->_cliOutput('Group set to : '.$strGroup);

			// adding group
			$this->addTemplateGroup($strGroup);
			// setting current group name
			$this->_strTemplateGroup = $strGroup;
			// done
			return $this;
		}

		// returns current template group
		public function getTemplateGroup(){
			return $this->_strTemplateGroup;
		}

		// returns the id of the current generator
		public function getGeneratorId(){
			return $this->_strGeneratorId;
		}
		
		// add a special card template that have to be created on the fly while generating cards
		public function addCardTemplateOnTheFly($mCallBack, $strGroup = false){

			// do we need to add a new group
			if($strGroup){
				// yes
				$this->addTemplateGroup($strGroup);
			}
			else{
				$strGroup = $this->getTemplateGroup();
			}
			
			// do we have a string
			if(is_string($mCallBack)){
				// yes
				// defining function
				$strCallBackFunc = 'CardOnTheFly_'.$mCallBack;

				// checking if the function exists
				if(!function_exists($strCallBackFunc)){
					// no
					throw new Exception('no function named : '.$strCallBackFunc);
				}

				// yes

				// setting cli output
				$this->_cliOutput('# New onTheFlyTemplate function ['.$strGroup.']: '.$strCallBackFunc);
				// setting template
				$this->_cardsTemplates[$strGroup][] = array(
					self::CARDTYPE => self::CARDONTHEFLY,
					self::CARDONTHEFLYFUNC => 'CardOnTheFly_'.$mCallBack,
				);

				// done
				return $this;
			}

			// do we have a special array param
			if(is_array($mCallBack)){
				// yes
			
				// do we have a valid class name 
				if(!isset($mCallBack[0]) || !is_string($mCallBack[0])){
					// no
					throw new Exception('invalid parameter found in array.');
				}

				// extracting class name
				$strObjectClass = $mCallBack[0];

				// do we have a valid class name  
				if(!class_exists($strObjectClass, false)){
					// no
					throw new Exception('unknown class : '.$strObjectClass);
				}

				if(!method_exists($strObjectClass, 'getObject') || !method_exists($strObjectClass, 'hasObject')){
					throw new Exception('given class has no method  : getObject or hasObject');
				}

				// do we have a valid object id
				if(!isset($mCallBack[1]) || !is_string($mCallBack[1])){
					// no
					throw new Exception('invalid parameter found in array.');
				}

				// do we have a valid object
				if(!call_user_func(array($strObjectClass, 'hasObject'), $mCallBack[1])){
					// no
					throw new Exception('unknown object id in store : '.$mCallBack[1]);
				}

				// setting cli output
				$this->_cliOutput('# New onTheFlyTemplate '.$strObjectClass.' ['.$strGroup.'] : '.$mCallBack[1]);

				// setting object
				$this->_cardsTemplates[$strGroup][] = array(
					self::CARDTYPE => self::CARDONTHEFLY,
					self::CARDONTHEFLYFUNC => call_user_func(array($strObjectClass, 'getObject'), $mCallBack[1]),
				);	

				// done
				return $this;
			}

			if(!$mCallBack instanceof TemplateRenderingLogic){
				throw new Exception('invalid object given : instanceof TemplateRenderingLogic expected');
			}

			// setting cli output
			$this->_cliOutput('# New onTheFlyTemplate '.get_class($mCallBack).' ['.$strGroup.'] : object');

			// setting object
			$this->_cardsTemplates[$strGroup][] = array(
				self::CARDTYPE => self::CARDONTHEFLY,
				self::CARDONTHEFLYFUNC => $mCallBack,
			);	
			
			return $this;
		}
		
		// adds a template to the template list
		public function addCardTemplate($strID, $strName, $mCategory, $intScaleType, $strQuestion, $mQuestionInfo, $strAnswer, $mAnswerInfo, $mCallBackCondition = false, $strDisplayCaterory = false, $arrTags = array(), $strGroup = false){

			// do we need to add a new group
			if($strGroup){
				// yes
				$this->addTemplateGroup($strGroup);
			}
			else{
				$strGroup = $this->getTemplateGroup();
			}

			// setting cli output
			$this->_cliOutput('# New CardTemplate ['.$strGroup.']: '.$strID);

			// do we have to set a an id automatically
			if($strID === false || strtolower(trim($strID)) == 'auto'){
				$strID = $strGroup.'-'.count($this->_cardsTemplates[$strGroup])+1;
			}

			// adding new template
			$this->_cardsTemplates[$strGroup][] = $this->formatCardTemplate($strID, $strName, $mCategory, $intScaleType, $strQuestion, $mQuestionInfo, $strAnswer, $mAnswerInfo, $mCallBackCondition, $strDisplayCaterory, $arrTags);
			
			return $this;
		}
		
		// returns a formated template array
		public function formatCardTemplate($strID, $strName, $mCategory, $intScaleType, $strQuestion, $mQuestionInfo, $strAnswer, $mAnswerInfo, $mCallBackCondition = false, $strDisplayCaterory = false, $arrTags = array()){
		
			// checking category format
			if(!is_array($mCategory)){
					$mCategory = array($mCategory);
			}
			
			// setting a formated template array
			$arrFormated =  array(
				self::CARDTYPE     			=> 'template',
				self::CARDID       			=> $strID,
				self::CARDNAME     			=> $strName,
				self::CARDCATEGORY 			=> $mCategory,
				self::CARDDISPLAYCATEGORY 	=> $strDisplayCaterory,
				self::CARDTAGS				=> $arrTags,	
				self::SCALETYPE	   			=> $this->_filterScaleType($intScaleType),
				self::QUESTION     			=> $strQuestion,
				self::QUESTIONINFO 			=> $mQuestionInfo,
				self::ANSWER       			=> $strAnswer,
				self::ANSWERINFO   			=> $mAnswerInfo,
				self::CALLBACKCONDITION		=> $mCallBackCondition	
			);
					
			return $arrFormated;
		}
				
		// ----- Extraction ---------------
				
		// execute the callback condition of a single template
		protected function _getTemplateConditionCallbackResult($arrCallBackCondition, $strNote, $arrTemplate){
			
			// ensure that we have an array
			if(is_string($arrCallBackCondition)){
				$arrCallBackCondition = array($arrCallBackCondition);
			}
								
			foreach($arrCallBackCondition as $mCallBackFunc){
				
				$arrCallBackParams = array($strNote, $arrTemplate);
				$strCallBackFunc   = $mCallBackFunc;
				
				// checking if given callback is an array
				if(is_array($mCallBackFunc)){
						$strCallBackFunc = $mCallBackFunc[0];
						unset($mCallBackFunc[0]);
						$arrCallBackParams = array_merge($arrCallBackParams, $mCallBackFunc);
				}
				
				// checking function
				if(!is_string($strCallBackFunc) || empty($strCallBackFunc)){
						// nothing usable
						// skipping
						echo "\ninvalid callback function :".print_r($strCallBackFunc, true);
						echo "\n";
						$this->_debugMessage('invalid callback function given : '.print_r($strCallBackFunc, true));
						continue;
				}
				
				// setting default callback function
				$mMethod = false;
									
				// do we have a local method
				if(method_exists($this, '_cardsCondition_'.$strCallBackFunc)){
					// yes. setting method
					$mMethod = array($this, '_cardsCondition_'.$strCallBackFunc);
				}
			
				// do we have a user function instead
				if(function_exists('cardsCondition_'.$strCallBackFunc)){
					// yes. Setting method to user function
					$mMethod = 'cardsCondition_'.$strCallBackFunc;
					// adding current objet to params 
					$arrCallBackParams = array_merge(array($this), $arrCallBackParams);
					$this->_debugMessage('Found call to user function : '.$mMethod);
				}
				
				// does the function exist ?
				if(!$mMethod){
					// no
					// skipping
					$this->_debugMessage('invalid callback function given : '.$strCallBackFunc);
					continue;
				}
				
				// checking condition
				if(!call_user_func_array($mMethod, $arrCallBackParams)){
					// skipping current note
					return false;
				}
			}
				
			// no condition has returned false
			return true;
		}
		
		// ---- cards conditions 
		
		// return false if $strNote is in $mSkipNote
		// else return true
		protected function _cardsCondition_SkipNote($strNote, $arrTemplate, $mSkipNote){
	
			if(!is_array($mSkipNote)) $mSkipNote = array($mSkipNote);
			
			foreach($mSkipNote as $strSkipNote){
				if($strNote == $strSkipNote){
					return false;
				}
			}

			return true;
		}
		
		// return false if the card has already been generated
		function _cardsCondition_SingleCard($strNote, $arrTemplate, $intAbsoluteSingle = false){

			// setting ident for template
			$strIdent = ($intAbsoluteSingle)? md5(serialize($arrTemplate)) : md5($strNote.serialize($arrTemplate));
			
			// if the card has already been generated we 
			// have to return false
			if(in_array($strIdent, $this->_cardsCondition_arrCardGeneratedList)){
				return false;
			}
			
			// adding ident to cards list
			$this->_cardsCondition_arrCardGeneratedList[] = $strIdent;
			
			return true;
		}
		
		
		// ---- /cards conditions 

		// use the template $arrTemplate to generate a card array
		protected function _getGeneratedCardArray($arrTemplate, $strNote){
			
			// simple template check
			if(!is_array($arrTemplate) || empty($arrTemplate)){
				$this->_debugMessage('invalid or empty template found');
				return false;
			}
						
			// do we have one or more call back condition(s)
			if($arrTemplate[self::CALLBACKCONDITION] && !empty($arrTemplate[self::CALLBACKCONDITION])){
				// yes
				if(!$this->_getTemplateConditionCallbackResult($arrTemplate[self::CALLBACKCONDITION], $strNote, $arrTemplate)){
						// template should not be executed
						return false;
				}
			}
			
			// setting new card
			$arrCard = array();
			
			// do we have to use the question for the card name
			if((!is_string($arrTemplate[self::CARDNAME]) || empty($arrTemplate[self::CARDNAME])) && !$arrTemplate[self::CARDNAME] instanceof CardText){
				// yes
				$arrTemplate[self::CARDNAME] =  $arrTemplate[self::QUESTION];
			}
			
			// ensure scaletype to be filtered
			$arrTemplate[self::SCALETYPE] = $this->_filterScaleType($arrTemplate[self::SCALETYPE]);
			
			// card identification
			$arrCard[self::CARDNAME]     = $this->_replacePlaceholders($strNote, $arrTemplate[self::SCALETYPE], $arrTemplate[self::CARDNAME]);
			$arrCard[self::SCALETYPE]    = $arrTemplate[self::SCALETYPE];
			
			// do we have an array as category
			if(is_array($arrTemplate[self::CARDCATEGORY])){
				// yes
				$arrCard[self::CARDCATEGORY] = current($arrTemplate[self::CARDCATEGORY]); // main catergory only
			}
			else{
				$arrCard[self::CARDCATEGORY] = $arrTemplate[self::CARDCATEGORY];
			}
			
			// setting default tags
			$arrCard[self::CARDTAGS] = '';
			
			// do we have tags
			if(is_array($arrTemplate[self::CARDTAGS]) && !empty($arrTemplate[self::CARDTAGS])){
				// yes
				// applying replacements
				$arrCard[self::CARDTAGS] = $this->_replacePlaceholders($strNote, $arrTemplate[self::SCALETYPE], $arrTemplate[self::CARDTAGS]);
				// filtering datas
				foreach($arrCard[self::CARDTAGS] as $intTagKey => $strCardTag){
					$arrCard[self::CARDTAGS][$intTagKey] = str_replace(' ', '_', trim($strCardTag));
				}
				
				// setting tags as string
				$arrCard[self::CARDTAGS] = implode(' ', $arrCard[self::CARDTAGS]);
			}
					
			// Question relative fields // TODO : Add justify properties into config file
			$arrCard[self::QUESTION]     = $this->_replacePlaceholders($strNote, $arrTemplate[self::SCALETYPE], $arrTemplate[self::QUESTION]);
			// setting info
			$arrCard[self::QUESTIONINFO] = $this->_replacePlaceholders($strNote, $arrTemplate[self::SCALETYPE], $arrTemplate[self::QUESTIONINFO]);
			
			// Answer relative fields
			$arrCard[self::ANSWER]     = $this->_replacePlaceholders($strNote, $arrTemplate[self::SCALETYPE], $arrTemplate[self::ANSWER]);
			// setting info
			$arrCard[self::ANSWERINFO] = $this->_replacePlaceholders($strNote, $arrTemplate[self::SCALETYPE], $arrTemplate[self::ANSWERINFO]);
			
			// setting default title
			$arrCard[self::CARDTITLE] = '';
			
			// could we use the title (not used for pdf)
			if(isset($this->_strTitle) && is_string($this->_strTitle) && !empty($this->_strTitle)){
				$arrCard[self::CARDTITLE] = $this->_strTitle;
			}
			
			// using main category as display category by default
			$strDisplayCat = $arrTemplate[self::CARDCATEGORY][0];
			
			// do we have a special category to display
			if(!empty($arrTemplate[self::CARDDISPLAYCATEGORY])){
				// yes
				// parsing content
				$strDisplayCat = $this->_replacePlaceholders($strNote, $arrTemplate[self::SCALETYPE], $arrTemplate[self::CARDDISPLAYCATEGORY]);
			}
			
			// adding category to the title zone
			if(is_string($strDisplayCat) && !empty($strDisplayCat)){
				// do we already have a title
				if(!empty($arrCard[self::CARDTITLE])){
					// yes
					$arrCard[self::CARDTITLE].=' - ';
				}
				// adding category
				$arrCard[self::CARDTITLE].= $strDisplayCat;
			}
										
			// returns the cards
			return $arrCard;
		}
			
		// parse card template and return a csv string
		protected function _parseCardTemplates($strGroup, $arrTemplates, $strNote, $mCardId = false, $intNoteCount = 0, $intMainTplKey = false){

			// setting cli output
			$this->_cliOutput('');

			if(!count($this->_arrOutputMods)){
				$this->_cliOutput('# No output module set. Nothing will be generated !');
				return '';
			}
			
			$this->_cliOutput('# Parsing cards templates : '.count($arrTemplates));
			
			// do we have a tempate list
			if(!is_array($arrTemplates) || empty($arrTemplates)){
				// no
				$this->_debugMessage('## Empty template list found');
				return '';
			}
			
			// number total of card to generate
			$intTotal  = (is_array($mCardId) && !empty($mCardId)) ? count($mCardId) : count($arrTemplates);	
			$intPassed = 0;
			
			// setting default csv content
			$strCSV = '';

			// rollling over _cardsTemplates				
			foreach($arrTemplates as $intTplKey => $arrTemplate){

				// do we have an on the fly card generation		
				if(isset($arrTemplate[self::CARDTYPE]) && $arrTemplate[self::CARDTYPE] == self::CARDONTHEFLY){
					// yes
					// do we have a phrasesBuilder
					if(is_object($arrTemplate[self::CARDONTHEFLYFUNC])){
						// do we have a usable object
						if(!$arrTemplate[self::CARDONTHEFLYFUNC] instanceof TemplateRenderingLogic){
							// no
							throw new Exception('Given object for onTheFlyTemplate is not an instance of TemplateRenderingLogic : '.get_class($arrTemplate[self::CARDONTHEFLYFUNC]));
						}

						// extracting object
						$oRenderer = $arrTemplate[self::CARDONTHEFLYFUNC];

						// getting template
						$arrTemplate = $oRenderer->getTemplate($this, $strNote);

						// do we have an array
						if(!is_array($arrTemplate)){
							// no
							$this->_cliOutput('# WARNING : The Rendered :'.get_class($oRenderer).' has not returned an array.');
							// skipping
							continue;
						}
						
					} 	// do we have a valid callback function
					else if(is_string($arrTemplate[self::CARDONTHEFLYFUNC]) && function_exists($arrTemplate[self::CARDONTHEFLYFUNC])){
							// yes
							// getting template
							$arrTemplate = call_user_func_array($arrTemplate[self::CARDONTHEFLYFUNC], array($this, $strNote));
					}
					else{
						$this->_debugMessage('invalid callback found.');
						continue;
					}
					
					if(!is_array($arrTemplate) || empty($arrTemplate)){
						$this->_debugMessage('empty template found.');
						continue;
					}
					
					// getting keys
					$arrTplKeys = array_keys($arrTemplate);
					
					// do we have an array of cards
					if(is_numeric($arrTplKeys[0])){
						// yes
						// self recall
						$this->_parseCardTemplates($strGroup, $arrTemplate, $strNote, $mCardId, $intNoteCount, $intTplKey);
						// done
						continue;
					}

					// no, going forward as for a standard template
				}
			
				// do we have a filtered id
				if(is_array($mCardId) && !in_array($arrTemplate[self::CARDID], $mCardId)){
					// yes

					// setting skip flag
					$intSkipCardId = true;

					// checking if any id can be found
					foreach($mCardId as $strSkipCardId){
						if(strpos($arrTemplate[self::CARDID], $strSkipCardId) !== false){
							// changing flag
							$intSkipCardId = false;
							break;
						} 
					}

					// do we have to skip the card
					if($intSkipCardId){
						// yes
						$this->_cliOutput('## Skipping card : '.$arrTemplate[self::CARDID]);
						continue;
					}
				}
	
				// setting indication
				$intPassed++;
				$intPurcent = round($intPassed*100/$intTotal);
				$intDispTplKey = (is_numeric($intMainTplKey)) ? $intMainTplKey : $intTplKey;

				// output
				$this->_cliOutput('[Tones '.str_pad($this->_intGeneratingTonesPurcentProgress, 3, '0', STR_PAD_LEFT).'%] [Group '.str_pad($intPurcent, 3, '0', STR_PAD_LEFT).'%] - Tone: '.ucfirst($strNote).' - Generating card ('.($intDispTplKey+1).'): '.$arrTemplate[self::CARDID], true);
				$this->_cliOutput(' Card ', true);
	
				// getting generated card
				$arrCard = $this->_getGeneratedCardArray($arrTemplate, $strNote);
			
				// checking card result
				if(!is_array($arrCard)){
						// a callback condition may have return false
						$this->_cliOutput('[Skipped]');
						continue;
				}

				// generating items inside the card (images, info etc);
				foreach($this->_arrOutputMods as $oOutputMod){

					$this->_cliOutput(' '.get_class($oOutputMod), true);

					// getting a copy of the card
					$arrCardOutPut = $arrCard;

					foreach($arrCardOutPut as $strCardKey => $mContent){
						// do we have a complex entry
						
						if(!is_array($mContent)){
							// no
							// do we have something usable
							if(empty($mContent) || (!is_string($mContent) && !is_string($mContent)) ||
							   !in_array($strCardKey, array(self::QUESTION, self::ANSWER))){
								// no
								continue;
							}
							
							// yes. A complex entry should be build
							$mContent = array(
								$strCardKey => array(
									'type'   => strtolower($strCardKey).'text',
									'params' => array(
										'text' 	  => $mContent,
										'display' => $strCardKey,
										'name'	  => $strCardKey
									)
								)
							);
							
							// updating output
							$arrCardOutPut[$strCardKey] = $mContent;
						}

						foreach($mContent as $strMediaKey => $arrMedia){
							
							// do we have a usable media
							if(!is_array($arrMedia) || empty($arrMedia) ||
							   !isset($arrMedia['type']) || !isset($arrMedia['params'])){
								   // no
								   $this->_cliOutput("WARN : Content found is not a array [$strMediaKey] or type and params key are missing : skipping");
								   continue;
							}
							
							// adding root param
							// adding root data to the media
							$arrMedia['params']['root'] = $strCardKey;
							
							// extracting media type
							$strMedia = $arrMedia['type'];						
							
							// do we have a media object for strMedia
							if(!$this->_hasMedia($strMedia)){
								$this->_cliOutput("WARN :  Media of type $strMedia cannot be handled");
								// no
								continue;
							}

							// yes we have a media

							$arrOutParams = array();
							// do we have specials params for the output Module
							if($oOutputMod->hasParam('Media.'.$strMedia)){
								$arrOutParams = $oOutputMod->getParam('Media.'.$strMedia);
							}

							// getting rendered media
							$arrCardOutPut[$strCardKey][$strMediaKey] = $this->_getMedia($strMedia)->render($arrMedia['params'], $arrOutParams, $strNote, $arrCardOutPut[self::SCALETYPE]);	
							// inserting media type
							if(!is_array($arrCardOutPut[$strCardKey][$strMediaKey])){
								throw new Exception('The media '.$strMedia.' handle by '.get_class($this->_getMedia($strMedia)).' is no formated properly. The render method should return an array !');
							}
							
							// inserting object type
							$arrCardOutPut[$strCardKey][$strMediaKey]['type'] = $strMedia;						
						}
					}

					// setting card rendering id
					$strCardRdrId = str_pad(($this->_intGeneratingToneNumber+1), 3, '0', STR_PAD_LEFT);
					$strCardRdrId.= '-'.ucfirst($strNote);

					if(is_numeric($intMainTplKey)){
						$strCardRdrId.= '-'.str_pad(($intMainTplKey+1), 3, '0', STR_PAD_LEFT);
					}else{
						$strCardRdrId.= '-001';
					}

					$strCardRdrId.= '-'.str_pad(($intTplKey+1), 3, '0', STR_PAD_LEFT);
					$strCardRdrId.= '-'.str_replace(' ', '', $arrTemplate[self::CARDID]);
		
					// rendering card : TODO : AddVars to last param : array()
					$oOutputMod->render($strGroup, $arrCardOutPut, $strNote, $strCardRdrId, array());
				}

				$this->_cliOutput(' [Ok]');
			}

			$this->_cliOutput('');
			
			return $this;
		}

		// generating cards for each note in array $arrNotes
		// cards are ScaleType dependent, so this is the card which defines for which kind of scale it have to be generated
		protected function _generateCards($arrNotes, $mCardId = false){
			
			// setting flag
			$this->_intGeneratingCards = true;	
			
			// setting default csv content
			$strCSV = '';
		
			// checking if we have a cardid filter
			if($mCardId && !empty($mCardId) && is_string($mCardId)){
				// setting filter
				$mCardId = array($mCardId);
			}
			
			// updating datas
			$this->_intGeneratingTotalToneNumber = count($arrNotes);
			$this->_intGeneratingTonesPurcentProgress = 0;
					
			$this->_debugMessage('# Number of tones to generates : '.$this->_intGeneratingTotalToneNumber);

			foreach($this->_cardsTemplates as $strGroup => $arrTemplates){

				$this->_cliOutput('# Extracting templates of Group : '.$strGroup);
						
				// rollling over $arrNotes
				foreach($arrNotes as $intKey => $strNote){	
						
					$this->_debugMessage('# Generating cards for tone : '.ucfirst($strNote));
				
					// updating datas
					$this->_strGeneratingTone       = $strNote;
					$this->_intGeneratingToneNumber = $intKey;
					$this->_intGeneratingTonesPurcentProgress = round(($intKey+1)*100/$this->_intGeneratingTotalToneNumber);

					// parsing cards
					$this->_parseCardTemplates($strGroup, $arrTemplates, $strNote, $mCardId, count($arrNotes));	
				}											
			}
			
			// setting flag
			$this->_intGeneratingCards = false;
			// updating datas
			$this->_strGeneratingTone                 = false;
			$this->_intGeneratingTotalToneNumber      = false;
			$this->_intGeneratingTonesPurcentProgress = false;

			// finalizing all output
			$this->_triggerOutput('finalize', array(), true);

			// done
			return $this;
		}
		
		// generates cards for one or more note
		public function generateFor($mNote =  false, $mCardId = false){

			// do we have to extract note from config file
			if($mNote === false && Bootstrap::getInstance()->getConfig()->hasVar('CardGenerator.tones')){
				// yes
				// extracting notes
				$mNote = Bootstrap::getInstance()->{'CardGenerator.tones'};
			}

			// ensure given param to be an array
			if(is_string($mNote)){
				// do we have the all shortcut
				if(strtolower(trim($mNote)) === 'all' ){
					// yes. Using common scale list
					$mNote = $this->_arrCommonScales;
				}
				else{ 
					$mNote = array(trim($mNote));
				}
			}

			// do we have a valid array
			if(!is_array($mNote) || empty($mNote)){
				// no
				$this->_cliOutput('Error : Empty note (tone) list given');
				// nothing can be done
				return $this;
			}
			
			// setting error flag
			$intHasError = false;
			// setting default note array
			$arrNotes = array();
			
			// checking notes
			foreach($mNote as $strNote){
				
					if(!is_string($strNote) || empty($strNote)){
						$this->_cliOutput('Error : Invalid note name given ! String expected');
						$intHasError = true;
						continue;
					}
				
					if(strpos($strNote, 'b') === 0){
						$this->_cliOutput('Error : Please use h letter for note b');
						$intHasError = true;
						continue;
					}
				
					if(!in_array($strNote, $this->_arrAllowsNotesGenCard)){
							$this->_cliOutput('Error : '.$strNote.' is not allowed for card generation');
							$intHasError = true;
					}
					
					// filtering note name
					$arrNotes[] = strtolower(trim($strNote));
			}
			
			if(empty($arrNotes)){
				$this->_cliOutput('Error : empty note list given');
				$intHasError = true;
			}
			
			// do we have an error
			if($intHasError){
					// yes
					return $this;
			}

			$this->_cliOutput('');
			$this->_cliOutput('Starting cards generation !');
			
			$this->_generateCards($arrNotes, $mCardId);

			$this->_cliOutput('');
			$this->_cliOutput('Operation finished !');
			
			// done
			return $this;	
		}
		
		// generates cards in all common used tones
		public function generateAllTones($mCardId = false){
			// generating cards for common scales
			$this->_generateCards($this->_arrCommonScales, $mCardId);
			// done
			return $this;	
		}
}
