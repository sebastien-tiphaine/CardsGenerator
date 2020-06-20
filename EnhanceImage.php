<?php

// php ./EnhanceImage.php --src source/svg/GammeMajPos1-I7M.svg --dest test.png --size 130 --neckpos 2
// php ./EnhanceImage.php --src source/svg/FretboardNotes/Tetras26eCorde.svg --dest test.png --size 200 --hstring 6


require_once(__DIR__.'/Libs/SvgAdapt.php');

$arrOptions = getopt(
	// short opts
	'',
	// long opts
	array(
		'src:',
		'dest:',
		'size:',
		'css:',
		'title:',
		'neckpos:',
		'hstring:',
		'srcpath:',
	)
);

// init images list
$arrDatas = array();

if(isset($arrOptions['srcpath']) && !empty($arrOptions['srcpath'])){
    
    // do we have a directory
    if(!is_dir($arrOptions['srcpath']) || !file_exists($arrOptions['srcpath'])){
         echo "SrcPath is not a valid folder\n";
         exit;
    }

    // checking destination
    if(isset($arrOptions['dest']) && !empty($arrOptions['dest'])){
        if(is_file($arrOptions['dest']) && !is_dir($arrOptions['dest'])){
            echo "dest option must be a folder if srcpath is set";
            exit;
        }
    }
    
    // getting file list
    foreach (glob($arrOptions['srcpath'].'*.svg') as $strFilename) {
        // setting file datas
        $arrFileDatas = array_merge($arrOptions, array('src' => $strFilename));
        unset($arrFileDatas['srcpath']);
        // inserting file data to main array
        $arrDatas[]  = $arrFileDatas;
    }
}
else{
    // setting options to first datas Entry
    $arrDatas[] = $arrOptions; 
}

// rolling over file list
foreach($arrDatas as $arrFileDatas){
    // checking src
    if(!isset($arrFileDatas['src']) || empty($arrFileDatas['src'])){
        echo "Missing src image\n";
        exit;
    }

    // setting default values
    $strSrcImage    = $arrFileDatas['src'];
    $strOutput      = str_replace('.svg', '.png', $strSrcImage);
    $intSize        = 200;
    $strCssFileName = 'source/styles/svg.css';
    $strCopyright   = 'Sébastien Tiphaine - 2020 - guitare-et-musique.com';
    $strTitle       = false;
    $intNeckPos     = false;
    $intHstring     = false;

    if(isset($arrFileDatas['dest']) && !empty($arrFileDatas['dest'])){

        $strOutput = $arrFileDatas['dest'];

        // does the string ends with a /
        if(strrpos($arrFileDatas['dest'], "/") == strlen($arrFileDatas['dest']) - 1){
            // yes
            $strOutput = $arrFileDatas['dest'].str_replace('.svg', '.png', basename($strSrcImage));
        }
    }

    if(isset($arrFileDatas['size']) && !empty($arrFileDatas['size'])){
        $intSize = $arrFileDatas['size'];
        // updating strOutput
        $strOutput = str_replace('.png', '_size'.$arrFileDatas['size'].'.png', $strOutput);
    }

    if(isset($arrFileDatas['css']) && !empty($arrFileDatas['css'])){
        $strCssFileName = $arrFileDatas['css'];
    }

    if(isset($arrFileDatas['title']) && !empty($arrFileDatas['title'])){
        $strTitle = $arrFileDatas['title'];
    }

    if(isset($arrFileDatas['neckpos']) && !empty($arrFileDatas['neckpos'])){
    $intNeckPos = $arrFileDatas['neckpos'];
    }

    if(isset($arrFileDatas['hstring']) && !empty($arrFileDatas['hstring'])){
    $intHstring = $arrFileDatas['hstring'];
    }

    $oSvg = new SvgAdapt($strSrcImage, $strCssFileName, $strCopyright);

    // add default visual marks on the neck if neckpos is not ste
    if($intNeckPos === false){
        $oSvg->setVisualMarksFromNeckPos();
    }

    if($intHstring){
    $oSvg->hightlightString($intHstring);
    }

    $oSvg->toPng($strTitle, false, $intNeckPos, false, false, $strOutput, $intSize);

    echo "image generated : $strOutput\n";
}

exit;
