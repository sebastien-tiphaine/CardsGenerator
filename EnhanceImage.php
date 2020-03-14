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
	)
);

if(!isset($arrOptions['src']) || empty($arrOptions['src'])){
    echo "Missing src image\n";
    exit;
}

// setting default values
$strSrcImage = $arrOptions['src'];
$strOutput = str_replace('.svg', '.png', $strSrcImage);
$intSize = 200;
$strCssFileName = 'source/styles/svg.css';
$strCopyright = 'Sébastien Tiphaine - 2020 - guitare-et-musique.com';
$strTitle = false;
$intNeckPos = false;
$intHstring = false;

if(isset($arrOptions['dest']) && !empty($arrOptions['dest'])){

    $strOutput = $arrOptions['dest'];

    // does the string ends with a /
    if(strrpos($arrOptions['dest'], "/") == strlen($arrOptions['dest']) - 1){
        // yes
        $strOutput = $arrOptions['dest'].str_replace('.svg', '.png', basename($strSrcImage));
    }
}

if(isset($arrOptions['size']) && !empty($arrOptions['size'])){
    $intSize = $arrOptions['size'];
}

if(isset($arrOptions['css']) && !empty($arrOptions['css'])){
    $strCssFileName = $arrOptions['css'];
}

if(isset($arrOptions['title']) && !empty($arrOptions['title'])){
    $strTitle = $arrOptions['title'];
}

if(isset($arrOptions['neckpos']) && !empty($arrOptions['neckpos'])){
   $intNeckPos = $arrOptions['neckpos'];
}

if(isset($arrOptions['hstring']) && !empty($arrOptions['hstring'])){
   $intHstring = $arrOptions['hstring'];
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
exit;
