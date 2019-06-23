<?php

require_once(__DIR__.'/OutputHtml.php');

class OutputPDF extends OutputHtml{

	// current note
	protected $_strCurrentNote = false;

	// card count by groups
	protected $_arrCardCount = array();

	// page count by group
	protected $_arrPageCount = array();

	// generated pages
	protected $_arrPages = array();

	// default params
	protected $_arrParams = array(
			'TonePage'      	  => 'source/layout/pdf-tone-page.html',
			'TonePage.Css'  	  => 'source/layout/pdf-tone-page.css',
			'Front.Layout' 	      => 'source/layout/pdf-front.html',
			'Front.Layout.Vars'   => array(),
			'Front.Layout.Css'    => 'source/layout/pdf.css',
			'Back.Layout' 	      => 'source/layout/pdf-back.html',
			'Back.Layout.Vars'    => array(),
			'Back.Layout.Css'     => 'source/layout/pdf.css',
			'Card.TitlePage'      => 'source/layout/pdf-title-page.html',
			'Card.TitlePage.Css'  => 'source/layout/pdf-title-page.css',
			CardGenerator::QUESTIONINFO.'.template' => 'source/templates/PdfInfoTpl.php',
			CardGenerator::ANSWERINFO.'.template'   => 'source/templates/PdfInfoTpl.php',
			'Media.image' => array(
				'size' => 300,
			),
			'Media.score' => array(
				'size' => 300,
			),
			'Media.sound' => array(
				'dir'    => 'audio',
				'single' => true,
				'format' => array('mp3', 'm4a')
			),
	);

	// generate a pdf from an html page
	protected function _outputPDF($strHtmlFile){
		// setting pdf file name
		$strPdfFile = $strHtmlFile.'.pdf';
		// generating pdf
		passthru('/usr/bin/weasyprint --base-url=None '.$strHtmlFile.' '.$strPdfFile);
		// returning pdf file
		return $strPdfFile;
	}

	// turn a $strFile into a pdf
	protected function _renderPDFPage($arrDatas, $strNote, $strCardRdrId, $arrVars, $strFile, $arrHtmlVars = array(), $arrStyles = array()){

		// generating html file
		$strHtmlFile = $this->_renderLayout($arrDatas, $strNote, $strCardRdrId, $arrVars, $strFile, $arrHtmlVars, $arrStyles);
		// generating pdf page
		$strPdfFile  = $this->_outputPDF($strHtmlFile);
		// removing html file
		unlink($strHtmlFile);
		// done
		return $strPdfFile;
	}

	// applying rendering
	protected function _render($strGroup, $arrCard, $strNote, $strCardRdrId, $arrVars){

		// checking vars
		if(!is_array($arrVars)){
			$arrVars = arrays();
		}

		// inserting vars into $arrVars
		$arrVars['MainTitle'] = $this->_getGenerator()->getTitle();

		// do we have pages for the current group
		if(!isset($this->_arrPages[$strGroup])){
			// no, init the array
			$this->_arrPages[$strGroup] = array();
		}

		// do we have a card count for the current group
		if(!isset($this->_arrCardCount[$strGroup])){
			// no, init the count
			$this->_arrCardCount[$strGroup] = 0;
		}

		// updating card count
		$this->_arrCardCount[$strGroup]++;

		// do we have a page count for the current group
		if(!isset($this->_arrPageCount[$strGroup])){
			// no, init the count
			$this->_arrPageCount[$strGroup] = 0;
		}

		// has note changed ?
		if(!$this->_strCurrentNote || $this->_strCurrentNote != $strNote){
			// yes
			// setting page for the tonality
			if($this->hasParam('TonePage')){

				// updating page count
				$this->_arrPageCount[$strGroup]++;
				
				// getting tone page
				$strTonePage = $this->getParam('TonePage');
				// getting css
				$arrStyles = $this->getParam('TonePage.Css');

				// setting variables
				$arrDatas = array(
					'Note'    => $this->_getGenerator()->translateNote($strNote),
					'Page'    => $this->_arrPageCount[$strGroup]
				);
				// adding page
				$this->_arrPages[$strGroup][] = $this->_renderPDFPage($arrDatas, $strNote, $strCardRdrId.'-tonePage', $arrVars, $strTonePage, array(), $arrStyles);
			}

			// updating current note
			$this->_strCurrentNote = $strNote;
		}

		// do we have a front page for the card
		if($this->hasParam('Card.TitlePage')){
			// getting title page
			$strTitlePage = $this->getParam('Card.TitlePage');
			// getting css
			$arrStyles = $this->getParam('Card.TitlePage.Css');
			// updating page count
			$this->_arrPageCount[$strGroup]++;
			
			// setting multilingual card num
			$strCardNum = str_replace('%num%', $this->_arrCardCount[$strGroup], Bootstrap::getInstance()->i18n()->_t('Carte %num%'));
					
			// setting variables
			$arrDatas = array(
				'Note'    => $strNote,
				'CardNum' => $strCardNum,
				'CardId'  => $strCardRdrId,
				'Page'    => $this->_arrPageCount[$strGroup],
			);
			// adding page
			$this->_arrPages[$strGroup][] = $this->_renderPDFPage($arrDatas, $strNote, $strCardRdrId.'-titlePage', $arrVars, $strTitlePage, array(), $arrStyles);
		}

		// rendering front ------------------------------------
		
		// setting layout
		$strLayout = $this->getParam('Front.Layout');
		// getting additionnals layout vars
		$arrLayoutVars = $this->getParam('Front.Layout.Vars', array());
		// getting layout css
		$arrStyles = $this->getParam('Front.Layout.Css');
		// updating page count
		$this->_arrPageCount[$strGroup]++;
		
		// setting page count
		$arrVars['Page'] = $this->_arrPageCount[$strGroup];

		// updating main title
		$arrVars['MainTitle'] = $this->_getGenerator()->getTitle().' - Card Front';

		// rendering card using the layout
		$this->_arrPages[$strGroup][] = $this->_renderPDFPage($arrCard, $strNote, $strCardRdrId.'-front', $arrVars, $strLayout, $arrLayoutVars, $arrStyles);

		// Rendering Back ------------------------------------

		// setting layout
		$strLayout = $this->getParam('Back.Layout');
		// getting additionnals layout vars
		$arrLayoutVars = $this->getParam('Back.Layout.Vars', array());
		// getting layout css
		$arrStyles = $this->getParam('Back.Layout.Css');
		// updating page count
		$this->_arrPageCount[$strGroup]++;

		// setting page count
		$arrVars['Page'] = $this->_arrPageCount[$strGroup];

		// updating main title
		$arrVars['MainTitle'] = $this->_getGenerator()->getTitle().' - Card Front & Back';

		// rendering card using the layout
		$this->_arrPages[$strGroup][] = $this->_renderPDFPage($arrCard, $strNote, $strCardRdrId.'-back', $arrVars, $strLayout, $arrLayoutVars, $arrStyles);

		// done
		return $this;
	}

	// join pdf pages
	protected function _pdfUnit($arrPages, $strOutPdfName){

		// getting list of pages as string
		$strPDFPages = implode(' ', $arrPages);
		// setting file name
		$strCardsPdf = $this->getOutputDir().'/'.$this->_getCleanFileName($strOutPdfName);

		// joining pages
		passthru('/usr/bin/pdfunite '.$strPDFPages.' '.$this->getOutputDir().'/'.$strOutPdfName);
		// removing sources
		foreach($arrPages as $strPage){
			if(file_exists($strPage)){
				unlink($strPage);
			}
		}

		// done
		return $strCardsPdf;
	}

	// finalize rendering
	public function finalize(){

		// building a pdf for each tone
		foreach($this->_arrPages as $strGroup => $arrPages){

			// do we have less than 50 pages
			if(count($arrPages) < 50){
				// yes, we can join pages simply
				$this->_pdfUnit($arrPages, 'Card-'.$strGroup.'.pdf');
				// done
				continue;
			}

			// no, pages have to be splitted

			// list of joined pages
			$arrUnitPage = array();

			while($arrPagesToJoin = array_splice($arrPages, 0, 50)){
				$arrUnitPage[] = $this->_pdfUnit($arrPagesToJoin, 'tmp-'.$strGroup.'-'.md5(uniqid('', true)).'.pdf');
			}

			// joining final pages
			$this->_pdfUnit($arrUnitPage, 'Card-'.$strGroup.'.pdf');
		}

		return $this;
	}
}
