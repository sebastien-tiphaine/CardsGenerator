<?php

require_once(__LIBS__.'/CardGenerator.php');
require_once(__LIBS__.'/CardsStore.php');

class CliExec{

	// CardGenerator engine
	protected $_oGenerator = null;
	
	// contruct
	protected function __construct($intDebug = false){

		// ensure translations to be up to date
		$this->_updateTranslations();

		// setting generator
		// no param set, so bootstrap config will be used
		$this->_oGenerator = new CardGenerator();
		
		// do we have to activate the debug function
		if($intDebug){
			// yes
			$this->_oGenerator->setDebug(true);
		}
	}

	// init and start cards generation from cli options
	public static function generate($arrOptions){

		// do we have to display an help message
		if(empty($arrOptions) || array_key_exists('help', $arrOptions) || array_key_exists('h', $arrOptions)){
			// yes
			return self::displayHelp();
		}

		// getting config file name
		$strConfigFile = self::_extractConfigFile($arrOptions);

		// do we have a config file
		if(!$strConfigFile){
			// no
			exit;
		}
		
		// inserting config to main config
		Bootstrap::getInstance()->getConfig()->loadConfigFile($strConfigFile);

		// extracting tone option
		$arrTone = self::_extractOption($arrOptions, 't', 'tones');

		// do we have a tone or a tone list
		if($arrTone && $arrTone!=='all'){
			// do we have a list
			if(strpos($arrTone, ',') !== false){
				// yes
				// getting all values
				$arrTone = explode(',',$arrTone);
			}

			// ensure value to be an array
			if(!is_array($arrTone)){
				$arrTone = array($arrTone);
			}

			// checking format
			foreach($arrTone as $intKey => $strTone){
				$arrTone[$intKey] = trim(strtolower($strTone));
			}
		}

		// do we have a single card
		$strCard = self::_extractOption($arrOptions, 'd', 'card');

		// extracting entry
		$strEntry = self::_extractEntry($arrOptions); 

		// default debug flag
		$intDebug = false;
		
		// do we have the debug option
		if(empty($arrOptions) || array_key_exists('debug', $arrOptions)){
			// yes
			$intDebug = true;
		}

		// do we have to generate all
		if($strEntry === 'TemplateGroups'){
			// do we have a single card
			if($strCard){
				// ok, this mean we have no group given. TemplateGroups is just
				// the default result from _extractEntry for no entry
				// so we have to generate a single card out of any group
				self::_generateSingleCard($strCard, $arrTone, $intDebug);
				exit;
			}
			// we have to generate all cards
			self::_generateAll($arrTone, $intDebug);
			exit;
		}

		// generating cards (all or single) from a single entry
		self::_generateEntry($strEntry, $strCard, $arrTone, $intDebug);
		exit;
	}

	// extract the value of an option
	protected static function _extractOption($arrOptions, $strShortName = false, $strLongName = false){

		if(is_string($strShortName) && !empty($strShortName)){
			$strShortName = isset($arrOptions[$strShortName])? $arrOptions[$strShortName]:false;
		}

		if(is_string($strLongName) && !empty($strLongName)){
			$strLongName = isset($arrOptions[$strLongName])? $arrOptions[$strLongName]:false;
		}

		if($strShortName && $strLongName){
			self::_cliError('-'.$strShortName.' and --'.$strLongName.' are the same option. Please use only one !', true);
		}

		if($strShortName) return $strShortName;
		if($strLongName)  return $strLongName;
 		return false;
	}

	// extracts a single card name from options
	protected static function _extractEntry($arrOptions){

		// getting option value
		$strEntry = self::_extractOption($arrOptions, 'e', 'entry');

		// do we have to return the default value
		if(!$strEntry){
			return 'TemplateGroups';
		}

		// extracting entry
		return $strEntry;
	}

	// extract config file name from options
	protected static function _extractConfigFile($arrOptions){

		// getting option value
		$strConfig = self::_extractOption($arrOptions, 'c', 'config');
		
		if(!is_file($strConfig)){
			self::_cliError('Missing config file, or given file does not exist !');
			return false;
		}
		
		return $strConfig;
	}

	// displays a message on the cli
	protected static function _cliOutput($strMessage){
		// display a message
		echo $strMessage."\n";
		// done
		return true;
	}

	// sends an error message to the cli.
	// if $intExit is set to true, app will exit after displaying the message
	protected static function _cliError($strMessage, $intExit = false){

		// setting error prefix
		$strError = 'ERROR';

		// do we have a fatal error
		if($intExit){
			$strError = 'FATAL ERROR';
		}
				
		// displaying message
		self::_cliOutput($strError.': '.$strMessage);
		// do we have to exit ?
		if($intExit){
			self::_cliOutput('##### End of the process #####');
			exit;
		}

		// done
		return true;
	}

	// display usage 
	public static function displayHelp(){
		// displaying usage
		self::_cliOutput('usage : php Generate.php [--config|-c] path/to/file.ini [OPTIONS]');
		self::_cliOutput('   OPTIONS :');
		self::_cliOutput('       -e --entry (string) name : template group name use to generate cards configured for a single group.');
		self::_cliOutput('       -d --card (string) name : single card name to be generated.');
		self::_cliOutput('       -a --all : generates all cards of groups set in entry TemplateGroup. This is the default action');
		self::_cliOutput('       -t --tones : tone or list of tones (comma separated). This option will override what is set in the config file. Use [all] for generating cards in all tones.');
		self::_cliOutput('');
		// done
		return true;
	}

	// generate a single card only
	protected static function _generateSingleCard($strName, $arrTone = false, $intDebug = false){
		// Displayint init message
		self::_cliOutput('Starting card generation : '.$strName);

		// setting object handling card generation
		$oCliExec = new CliExec($intDebug);
		// setting card
		$oCliExec->configSingleCard($strName);
		// generating card
		$oCliExec->generateCards($arrTone);

		// done
		return true;
	}

	protected static function _generateAll($arrTone = false, $intDebug = false){
		// Displayint init message
		self::_cliOutput('Starting cards generation for all template groups');

		// extracting groups
		$arrGroups = Bootstrap::getInstance()->{'TemplateGroups'};

		// setting object handling cards generation
		$oCliExec = new CliExec($intDebug);

		foreach($arrGroups as $strName){
			// configuring card generator
			$oCliExec->configEntry($strName);
		}

		// generating cards
		$oCliExec->generateCards($arrTone);
		// done
		return true;
	}

	protected static function _generateEntry($strName, $strCard = false, $arrTone = false, $intDebug = false){
		// Displayint init message
		self::_cliOutput('Starting cards generation for entry : '.$strName);

		// setting object handling cards generation
		$oCliExec = new CliExec($intDebug);

		// configure card generator
		$oCliExec->configEntry($strName, $strCard);

		// generating cards
		$oCliExec->generateCards($arrTone);
		// done
		return true;
	}

	protected function _updateTranslations(){
		
		// init translation object
		Bootstrap::getInstance()->i18n();
		
		// setting default locale
		$strLocale = false;
		
		// trying to grab locale from config file
		if(Bootstrap::getInstance()->getConfig()->hasVar('CardGenerator.lang')){
			// extracting value
			$strLocale = Bootstrap::getInstance()->{'CardGenerator.lang'};
		}
		
		// do we have a locale
		if(!$strLocale){
			// no
			self::_cliOutput('WARNING : No locale found in config file. Missing CardGenerator.lang value.');
			// nothing more to do
			return $this;
		}
		
		// getting current locale
		$strCurrentLocale = getenv('LC_ALL');
		
		if($strCurrentLocale !== $strLocale){
			self::_cliOutput('WARNING : Locale is different from CardGenerator.lang. Translation may not work !');
		}
		
		// getting existing locales
		exec('locale -a', $arrResult);
		
		if(!in_array($strLocale, $arrResult)){
			self::_cliOutput('WARNING : Locale set in CardGenerator.lang is not supported by system. Please check locale -a !', true);
		}
		
		//removing extension from locale
		$intDot = strpos($strLocale, '.');
		if($intDot !== false){
			$strLocale = substr($strLocale, 0, $intDot);	
		}
		
		// setting path
		$strPath = __BASE__.'/i18n/'.$strLocale.'/LC_MESSAGES';
		
		// is dir existing
		if(!is_dir($strPath)){
			// no
			self::_cliOutput('WARNING : Path for locale does not exists : '.$strPath);
		}
		
		self::_cliOutput('## Updating translations :');
		
		foreach (glob($strPath.'/*.po') as $strPoFile) {
			
			// setting outfile
			$strOutFile = substr($strPoFile, 0, -3).'.mo';
			$strCmd = 'msgfmt -o '.$strOutFile.' '.$strPoFile;
			self::_cliOutput('     -> Updating : '.$strOutFile);
			exec($strCmd);
		}
		
		// done
		return $this;
	}

	// launch cards generation
	public function generateCards($arrTone = false){

		// do we have to generates cards in all tones
		if(is_string($arrTone) && strtolower(trim($arrTone)) == 'all'){
			// yes
			$this->getGenerator()->generateAllTones();
			// done
			return $this;
		}
		
		// generating cards
		$this->getGenerator()->generateFor($arrTone);
		// done
		return $this;
	}

	// returns current generator
	public function getGenerator(){
		return $this->_oGenerator;
	}

	// set the generator for card $strName
	public function configSingleCard($strName){

		if(!is_string($strName) || empty($strName)){
			self::_cliOutput('Card name should be a string !', true);
		}

		// extracting card name
		$strCardName = $strName;

		// do we have other card name
		if(Bootstrap::getInstance()->getConfig()->hasVar($strName.'.cardname')){
			// yes
			$strCardName = Bootstrap::getInstance()->{$strName.'.cardname'};
			self::_cliOutput('Card real name : '.$strCardName);
		}

		// setting default args
		$arrArgs = array();

		// do we have args
		$arrCardVars = Bootstrap::getInstance()->{'^'.$strName.'.vars.'};

		if(is_array($arrCardVars)){
			$arrArgs = array_merge($arrArgs, $arrCardVars);
		}

		// inserting card to generator
		CardsStore::invoke($strCardName, $this->getGenerator(), $arrArgs);

		// done
		return $this;
	}

	// sets all entry $strName params to cardsGenerator
	public function configEntry($strName, $mCards = false){

		// checking entry name
		if(!is_string($strName) || empty($strName)){
			throw new Exception('Invalid entry given ! String Expected');
		}

		// checking if entry can be retreived from config file
		$arrEntryParams = Bootstrap::getInstance()->{'^'.$strName};

		// checking datas
		if(!is_array($arrEntryParams) || empty($arrEntryParams)){
			throw new Exception('no params found in config file for entry : '.$strName.' !');
		}

		// setting template group name
		$this->getGenerator()->setTemplateGroup($strName);

		// do we have to override cards list
		if(is_string($mCards) || is_array($mCards)){
			// yes
			$arrEntryParams['cards'] = $mCards;
		}

		// extracting cards list from config
		if(!isset($arrEntryParams['cards']) || empty($arrEntryParams['cards'])){
			self::_cliError('Cards param not found or empty in config file for entry : '.$strName, true);
		}

		// ensure cards list to be an array
		if(!is_array($arrEntryParams['cards'])){
			$arrEntryParams['cards'] = array($arrEntryParams['cards']);
		}

		// setting default args
		$arrArgs = array();

		// do we have an array of args
		if(isset($arrEntryParams['vars'])){
			// yes
			if(!is_array($arrEntryParams['vars']) || empty($arrEntryParams['vars'])){
				self::_cliError('Invalid Vars param for entry : '.$strName, true);
			}

			$arrArgs = $arrEntryParams['vars'];
		}
		
		// extracting secondary vars from entry name	
		$arrVars = Bootstrap::getInstance()->{'^'.$strName.'.vars.'};

		// do we have vars specified in the entry
		if(is_array($arrVars) && !empty($arrVars)){
			// yes;
			// updating args
			$arrArgs = array_merge($arrArgs, $arrVars);
		}

		foreach($arrEntryParams['cards'] as $strCard){

			if(!is_string($strCard) || empty($strCard)){
				self::_cliError('Invalid card name found. String expected !', true);
			}

			// extracting cardname
			$strCardName = $strCard;

			// do we have other card name
			if(Bootstrap::getInstance()->getConfig()->hasVar($strCard.'.cardname')){
				// yes
				$strCardName = Bootstrap::getInstance()->{$strCard.'.cardname'};
			}

			// setting default args
			$arrCardArgs = $arrArgs;

			// do we have some specific params
			$arrCardVars = Bootstrap::getInstance()->{'^'.$strCard.'.vars.'};

			if(is_array($arrCardVars) && !empty($arrCardVars)){
				// yes. Merging vars
				$arrCardArgs = array_merge($arrCardArgs, $arrCardVars);
			}

			// inserting card to generator
			CardsStore::invoke($strCardName, $this->getGenerator(), $arrCardArgs);
		}

		// done
		return $this;
	}
	
}
