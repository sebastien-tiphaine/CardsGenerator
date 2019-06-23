<?php

class i18nTranslations{
	
	// list of loaded domains
	protected $_arrDomains = array();
	
	// default domain name
	protected $_strDefaultDomain = 'CardsGenerator';
		
	// default locale
	protected $_strLocale = false;	
	
	// constructor
	public function __construct($strLang = false){
		
		// adding default domain
		$this->addDomain($this->_strDefaultDomain, true);
		
		// do we have a local given
		if(!$strLang){
			// trying to grab locale from config file
			if(Bootstrap::getInstance()->getConfig()->hasVar('CardGenerator.lang')){
				// extracting value
				$strLang = Bootstrap::getInstance()->{'CardGenerator.lang'};
			}
		}
		
		// setting locale
		if($strLang){
			$this->_setLocal($strLang);
		}
		
		// do we have a locale set
		if(!$this->_strLocale){
			// no
			// getting current system locale
			$strSysLocale = getenv('LC_ALL');
			// is locale available
			if(!$strSysLocale){
				// no
				$this->_cliOutput('WARNING : no locale found. Using system default : '.$strSysLocale);
			}
			else{
				$this->_strLocale = $strSysLocale;
			}
		}
	}
	
	// returns current locale
	public function getLocale($intShort = false){
		
		// do we have a locale
		if(!is_string($this->_strLocale) || empty($this->_strLocale)){
			// no
			return '__NO_LOCALE_SET__';
		}
		
		// do we have to return a short local
		// or do we already have a short local
		if(!$intShort || strlen($this->_strLocale) == 2){
			// no
			return $this->_strLocale;
		}
		
		// spliting local to keep lang only
		// Note : the local should be COUNTRY_LANG.extension
		$mDatas = explode('_', $this->_strLocale);
		
		// do we have valid datas
		if(!is_array($mDatas) || empty($mDatas)){
			// no
			return $this->_strLocale;
		}
		
		// can we extract the lang only local
		if(isset($mDatas[1])){
			// yes
			$mDatas = $mDatas[1];
		}
		else{
			// no keeping country only
			$mDatas = current($mDatas);
		}
		
		// do we have an extension
		$intExt = strpos($mDatas, '.');
		if($intExt !== false){
			// yes. removing part
			$mDatas = substr($mDatas, 0, $intExt);
		}
		
		// returning short value
		return strtolower($mDatas);
	}
	
	// displays a message on the cli
	protected function _cliOutput($strMessage){
		$strMessage.="\n";
		echo $strMessage;
		return $this;	
	}
	
	// set locale params
	protected function _setLocal($strLang){
		
		// set lang flag
		$intSetLang = true;
		
		// setting local
		if(!putenv('LC_ALL='.$strLang)){
			$this->_cliOutput('WARNING : not able to put env variable : LC_ALL='.$strLang);
		}
		
		if(!setlocale(LC_ALL, $strLang)){
			$this->_cliOutput('WARNING : setlocale failed : LC_ALL='.$strLang);
			// turning flag to false
			$intSetLang = false;
		}
		
		if($intSetLang){
			$this->_strLocale = $strLang;
		}
		
		//$this->_cliOutput('INFO : locale set to '.$strLang);
		
		// done
		return $this;
	}
	
	// adds a domain for translations
	public function addDomain($strName, $intDefault = false){
		
		// checking name
		if(!is_string($strName) || empty($strName)){
			throw new Exception('invalid domain name given ! String expected');
		}
		
		// do we need to set the given domain
		if(in_array($strName, $this->_arrDomains)){
			// no.
			// nothing more to do
			return $this;
		}
		
		// adding new domain
		$this->_arrDomains[] = $strName;
		// setting domain to gettext
		$strBindTextDomain = bindtextdomain($strName, __BASE__.'/i18n');
		
		//$this->_cliOutput('BindTextDomain to : '.$strBindTextDomain);
		
		if($intDefault){
			$strCurDom = textdomain($strName);
			//$this->_cliOutput('Default domain set to : '.$strCurDom);
		}
		
		// done
		return $this;
	}
	
	// return text translated for a specified domain
	// for plural, send an array for $mText : array(strSingular, strPlural, intNumber)
	// $mText : msgid
	public function _t($mText, $strDomain = false, $strContext = false){
		
		// plural flag
		$intPlural = false;
		
		// do we have a plural
		if(is_array($mText)){
			// yes
			// do we have enought params
			if(count($mText) < 3){
				// no
				throw new Exception('Plural requires 3 parameters : singular msg, plural msg, number');
			}
			
			// do we have a text for the singular
			if(!is_string($mText[0]) || empty($mText[0])){
				// no
				throw new Exception('No text given for singular !');
			}
			
			// do we have a text for the plural
			if(!is_string($mText[1]) || empty($mText[1])){
				// no
				throw new Exception('No text given for plural !');
			}
			
			// do we have the plural value
			if(!is_numeric($mText[2])){
				// no
				throw new Exception('No number given for the plural !');
			}
			
			// updating false
			$intPlural = true;
		}
		// do we have a text
		else if((!is_string($mText) || empty($mText)) && !is_numeric($mText)){
			// no
			throw new Exception('No text given for translation');
		}
		
		// are we using the default domain
		if($strDomain === false){
			// yes
			// extracting defaut value
			$strDomain = $this->_strDefaultDomain;
		}
		
		// do we have a domain
		if(!is_string($strDomain) || empty($strDomain)){
			// no
			throw new Exception('Invalid domain given for translation. String expected');
		}
		
		// loading the domain, just in case
		$this->addDomain($strDomain);
		
		// do we have a plural
		if($intPlural){
			// yes
			// do we have to add the context to the each text
			if(is_string($strContext) && !empty($strContext)){
					// yes
					$arrTrsCtx = array();
					$arrTrsCtx[0] = $strContext."\004".$mText[0];
					$arrTrsCtx[1] = $strContext."\004".$mText[1];
					// translatting text
					$strTrs = dngettext($strDomain , $arrTrsCtx[0], $arrTrsCtx[1] , $mText[2]);
					
					// is text translated
					if($strTrs == $arrTrsCtx[0] || $strTrs == $arrTrsCtx[1]){
						// no
						return '__UT__'.$mText[0].'__UT__';
					}
					
					// return translated text
					return $strTrs;
			}
			
			// getting translated text
			return dngettext($strDomain , $mText[0], $mText[1] , $mText[2]);
		}
		
		// do we have to add the context to the text
		if(is_string($strContext) && !empty($strContext)){
			// yes
			// inserting context
			$strTrsCtx = $strContext."\004".$mText;
			// translating text
			$strTrs = dgettext($strDomain, $strTrsCtx);
			
			// is text translated
			if($strTrs == $strTrsCtx){
				// no
				// returning untranslated text
				return '__UT__'.$mText.'__UT__';
			}
			
			// return translated text
			return $strTrs;
		}
		
		// getting text
		$strTrs = dgettext($strDomain, $mText);
		// returns translated text
		return $strTrs;
	}
}
