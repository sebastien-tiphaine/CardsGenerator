<?php

require_once(__DIR__.'/OutputAbstract.php');

class OutputHtml extends OutputAbstract{

	// default params
	protected $_arrParams = array(
			'layout' 	  => false,
			'layout.vars' => array(),
			'layout.css'  => false,
			'Media.image' => array(
				'size' => 130,
				'dir'  => 'img'
			),
			'Media.score' => array(
				'size' => 130,
				'dir'  => 'img'
			),
			'Media.sound' => array(
				'dir'    => 'audio',
				'format' => array('mp3', 'ogg', 'm4a')
			),
	);

	// list of generated pages
	protected $_arrPages = array();

	// returns subdirs
	protected function _getSubDirs(){
		return array('img', 'audio');
	}
	
	// initialize object
	protected function _init(){
		return $this;
	}

	// write an html file and includes all given css file $mCssFiles
	protected function _writeHtmlFile($strFileName, $strHtml, $mCssFiles = false, $arrVars = false){
		
		// ensure $mCssFiles to be an array
		if($mCssFiles && !is_array($mCssFiles)){
			$mCssFiles = array($mCssFiles);
		}
		
		// default css content
		$strCss = '';
		
		// do we have css files
		if($mCssFiles){
			foreach($mCssFiles as $strCssFile){
				
				// getting real css file path
				$strCssFile = Bootstrap::getPath($strCssFile);
				
				// checking if file exists
				if(!file_exists($strCssFile)){
					$this->_cliOutput('');
					$this->_cliOutput(__CLASS__.':: WARN : CSS file does not exists : '.$strCssFile);
				}
				
				if(!empty($strCss)){
					$strCss.="\n";
				}
				
				// adding file content to main css content
				$strCss.= file_get_contents($strCssFile);
			}
		}
		
		if(!empty($strCss)){
			// adding required html tags
			$strCss = '<style type="text/css">'."\n".$strCss."\n".'</style>';
		}

		// do we have vars
		if(!is_array($arrVars)){
			// no
			$arrVars = array();
		}

		// inserting CSS
		$strHtml = $this->_replaceVar(array('CSS' => $strCss), $strHtml, $this->_getGenerator(), '{{', '}}');
		// replacing vars
		$strHtml = $this->_replaceVar($arrVars, $strHtml, $this->_getGenerator(), '{{', '}}');
		// writing file
		file_put_contents($strFileName, $strHtml);
		// done
		return $this;
	}

	// format media for a local usage
	protected function _formatMedia($arrMedia, $strDataType = false){
		// nothing to do
		return $arrMedia;
	}

	// rendering card using the layout
	protected function _renderLayout($arrCard, $strNote, $strCardRdrId, $arrVars, $strLayout, $arrLayoutVars = array(), $arrStyles = array()){
					
		// do we have a preview layout
		if(!is_string($strLayout) || !file_exists($strLayout)){
			// no
			$this->_cliOutput('');
			$this->_cliOutput(__CLASS__.':: WARN : invalid layout set : '.$strLayout);
			// done
			return $this;
		}

		// getting file content
		$strHtml = $this->_renderTemplate($strLayout);
		// setting outfile name
		$strOutFile = $this->getOutputDir().'/Card-'.$this->_getCleanFileName($strCardRdrId).'.html';
		// write file
		$this->_writeHtmlFile($strOutFile, $strHtml, $arrStyles, array_merge($arrCard, $arrLayoutVars, $arrVars));
		// done
		return $strOutFile;
	}

	// applying rendering
	protected function _render($strGroup, $arrCard, $strNote, $strCardRdrId, $arrVars){

		// setting layout
		$strLayout = Bootstrap::getPath($this->getParam('layout'));
		// getting additionnals layout vars
		$arrLayoutVars = $this->getParam('layout.vars', array());
		// getting layout css
		$arrStyles = $this->getParam('layout.css');
		// setting output dir

		// rendering card using the layout
		$this->_arrPages[] = $this->_renderLayout($arrCard, $strNote, $strCardRdrId, $arrVars, $strLayout, $arrLayoutVars, $arrStyles);
		// done
		return $this;
	}

	// finalize rendering
	public function finalize(){

		// getting the number of pages
		$intCount = count($this->_arrPages);
		// setting html index content
		$strHtmlIndex = '';

		// adding the prev-next buttons
		foreach($this->_arrPages as $intPage => $strFile){
			
			// setting default html
			$strHtml = '';
			$strHtmlIndex.= '<li><a href="'.basename($strFile).'">'.basename($strFile).'</a></li>';
			
			// can we add the previous button
			if($intPage > 0){
				// yes
				$strHtml.='<a href="'.basename($this->_arrPages[$intPage-1]).'"><< Previous</a>';
			}
			
			// can we add the next button
			if($intPage < $intCount -1){
				// yes
				// do we have a prev button
				if(!empty($strHtml)){
					$strHtml.='&nbsp;&nbsp;|&nbsp;&nbsp;';
				}
				
				$strHtml.='<a href="'.basename($this->_arrPages[$intPage+1]).'">Next >></a>';
			}
			
			// do we have buttons
			if(empty($strHtml)){
				// no
				continue;
			}
			
			// yes. adding wrapper
			$strHtml = '<div class="previewContainer"><div class="previewNavbuttons">'.$strHtml.'</div></div></body>';
			
			// applying replace 
			file_put_contents($strFile, str_replace('</body>', $strHtml, file_get_contents($strFile)));
		}

		// writing index file
		file_put_contents(dirname($strFile).'/0000-index.html',  '<html><head><title>Index Of Cards</title></head><body><h1>Index of cards</h1><ol>'.$strHtmlIndex.'</ol></body></html>');

		// done
		return $this;
	}
}
