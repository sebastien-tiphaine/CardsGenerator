<?php

require_once(__LIBS__.'/CardPhrasesBuilder.php');

// setting builder name
$strBuilderName = $oCardEnv->CardId.'-'.$oCardEnv->ScalePos.'-'.$oCardEnv->GroupId;

CardPhrasesBuilder::storeBuilder(
	$strBuilderName,
	new CardPhrasesBuilder(
		/* Skeletons */ 	array(
								'MajorScale/PhrasesBuilder-SimpleFollow-II-V-I',
								'MajorScale/PhrasesBuilder-II-V-I',
							),
		/* BaseScale */ 	'{source}/svg/GammeMajPos'.$oCardEnv->ScalePos.'.svg',
		/* Scale type */ 	CardGenerator::MAJOR,
		/* Chords Notes */  array(
								2 => 3,
								5 => 3,
								1 => 3
							),
		/* Phrases num */ 	1,
		/* Skels vars */ 	array(
								'GroupId' => $oCardEnv->GroupId,
								'ScalePos' => $oCardEnv->ScalePos
							),
		/* Skels values */  array(
								CardGenerator::CARDID       	   => $oCardEnv->CardId.'-'.$oCardEnv->ScalePos,
								CardGenerator::CARDNAME			   => $oCardEnv->GroupId.' | '.$oCardEnv->CardId.' ('.$oCardEnv->ScalePos.') | {note}',
								CardGenerator::CARDCATEGORY 	   => array($oCardEnv->Category),
								CardGenerator::CALLBACKCONDITION   => $oCardEnv->CallBackCondition,
								CardGenerator::CARDDISPLAYCATEGORY => false,
								CardGenerator::CARDTAGS			   => array('PhraseBuilder-II-V-I')
							)
	)
);

// adding special card template
$oCardEnv->Generator->addCardTemplateOnTheFly(array('CardPhrasesBuilder', $strBuilderName));
