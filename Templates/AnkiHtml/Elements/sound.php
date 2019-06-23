<?php

	// loading required functions
	require_once(__DIR__.'/sound/functions.php');

	// setting audio id
	$strAudioId = md5(uniqid('', true));
 
	// setting css classes
	$strClass = 'sound';
	// does the display param is equal to main type
	if($this->content['display'] != 'sound'){
		// no
		$strClass.=' sound-'.$this->content['display']; 
	}
?>

<div class="soundcontainer">
	<table align="center" class="<?php echo $strClass; ?> <?php echo $this->content['css']; ?>" id="<?php echo $this->content['id']; ?>">
	<tr>
		<td class="<?php echo $this->content['css']; ?> audioLabelContainer">
			<label for="<?php echo $strAudioId; ?>"><?php echo $this->content['label']; ?></label>
		</td>
		<td class="audioControlContainer"><?php echo AnkiRdrTplGetAudioControl($this->content['media'], $strAudioId); ?></td>
	</tr>
	</table>
</div>
