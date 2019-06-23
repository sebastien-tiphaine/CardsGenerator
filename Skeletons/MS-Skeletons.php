<?php

require_once(__DIR__.'/../Libs/CardSkeleton.php');
require_once(__DIR__.'/../Libs/CardText.php');

//-X-------------------------------------------- Card -------------------------------------------------

//-X-------------------------------------------- Card -------------------------------------------------

//-X-------------------------------------------- Card -------------------------------------------------

//-X-------------------------------------------- Card -------------------------------------------------

//-X-------------------------------------------- Card -------------------------------------------------

//-X-------------------------------------------- Card -------------------------------------------------

//-X-------------------------------------------- Card -------------------------------------------------


//-X-------------------------------------------- Card -------------------------------------------------



//-X-------------------------------------------- Card -------------------------------------------------


//-X-------------------------------------------- Card -------------------------------------------------


//-X-------------------------------------------- Card -------------------------------------------------

//-X-------------------------------------------- Card -------------------------------------------------

//-X-------------------------------------------- Card -------------------------------------------------


//-X-------------------------------------------- Card -------------------------------------------------

//-X-------------------------------------------- Card -------------------------------------------------

//-X-------------------------------------------- Card -------------------------------------------------

//-X-------------------------------------------- Card -------------------------------------------------


//-X-------------------------------------------- Card -------------------------------------------------

//-X-------------------------------------------- Card -------------------------------------------------

CardSkeleton::storeSkeleton(
	'MS-ChordArpeggio3to9',
	new CardSkeleton(array(
			CardGenerator::CARDID       	=> '%groupId%-ChordArpeggio3to9-%scalePos%-Chord%chord%',
			CardGenerator::CARDNAME     	=> new CardText(array(
													'fr' => '[%groupId%-{note}] Arpège 3-9 de l\'accord de {chord:%chord%}%chordQuality% dans la gamme de {note} majeur.',
													'en' => '[%groupId%-{note}] 3 to 9 arpeggio of the chord of {chord:%chord%}%chordQuality% in the scale of {note} major.',
											   )),
			//CardGenerator::CARDCATEGORY => array(),
			CardGenerator::SCALETYPE    	=> CardGenerator::MAJOR,
			CardGenerator::QUESTION     	=> new CardText(array(
													'fr' => 'Identifier l\'arpège 3-9 de {chord:%chord%}%chordQuality% dans la {numth:%scalePos%} position de la gamme de {note} majeur.',
													'en' => 'Identify the 3 to 9 arpeggio of {chord:%chord%}%chordQuality% in the {numth:%scalePos%} position of the {note} major scale.',
												)),
			CardGenerator::QUESTIONINFO 	=> array(
													'images' => array(
																array( // Chord
																		'src'                  => 'source/GammeMajPos%scalePos%-%imageExt%.svg',
																		'setTitle'		       => '{chord:%chord%}%chordQuality%',
																		'setNeckPosNum'        => 'auto:%chord%', //
																),
																array( // Scale
																	'src'              => 'source/GammeMajPos%scalePos%.svg',
																	'setTitle'		   => new CardText(array(
																							'fr' => '{note} majeur - {numth:%scalePos%} position',
																							'en' => '{note} major - {numth:%scalePos%} position'
																						  )),
																	'setNeckPosNum'    => 'auto', //
																),
															),
											),
			CardGenerator::ANSWER	   		=> new CardText(array(
													'fr' => 'L\'arpège de substitution (3-9) de {chord:%chord%}%chordQuality% est l\'arpège de {chord:%chordSub%}%chordSubQuality%:',
													'en' => 'The substitution arpeggio (3 to 9) of {chord:%chord%}%chordQuality% is the arpeggio of {chord:%chordSub%}%chordSubQuality%:',
												)),
			CardGenerator::ANSWERINFO		=> array(
												'images' => array(
																array( // chord with scale
																		'src'                  => 'source/GammeMajPos%scalePos%-%imageExt%.svg',
																		'setTitle'		       => '{chord:%chord%}%chordQuality%',
																		'setNeckPosNum'        => 'auto', //
																		'importSubTramCircles' => array('source/GammeMajPos%scalePos%.svg'), // gray circle import
																),
																array( // Arpeggio
																	'src'               => 'source/vertic5.svg',
																	'setTitle'		    => new CardText(array(
																							'fr' => 'Arpège 3-9 de {chord:%chord%}%chordQuality%',
																							'en' => '{chord:%chord%}%chordQuality% 3 to 9 arpeggio',
																						  )),
																	'importCircles'     => array('source/GammeMajPos%scalePos%.svg'), // black circle import
																	'setNeckPosNum'     => 'auto:GammeMajPos%scalePos%', //
																	'insertStyleString' => array(array( //hightlight
																								'circle.d%arpNote1%-GammeMajPos%scalePos%{fill:red}',
																								'circle.d%arpNote2%-GammeMajPos%scalePos%{fill:black}',
																								'circle.d%arpNote3%-GammeMajPos%scalePos%{fill:black}',
																								'circle.d%arpNote4%-GammeMajPos%scalePos%{fill:black}',
																						   )), 
																	'setCircleTextByCssClass1' => array('d%arpNote1%-GammeMajPos%scalePos%', '3'),
																	'setCircleTextByCssClass2' => array('d%arpNote2%-GammeMajPos%scalePos%', '5'),
																	'setCircleTextByCssClass3' => array('d%arpNote3%-GammeMajPos%scalePos%', '7'),
																	'setCircleTextByCssClass4' => array('d%arpNote4%-GammeMajPos%scalePos%', '9'),	
																),
															),
											),

	))
);

//-X-------------------------------------------- Card -------------------------------------------------
// ajouter une rem sur le BuildPhrases : utilisez alternativement la gamme maj et la penta


//-X-------------------------------------------- Card -------------------------------------------------



//-X-------------------------------------------- Card -------------------------------------------------








