<?php 
	// setting css classes
	$strClass = 'image';
	// does the display param is equal to main type
	if($this->content['display'] != 'image'){
		// no
		$strClass.=' image-'.$this->content['display']; 
	}
?>
<div class="<?php echo $strClass; ?> <?php echo $this->content['css']; ?>" id="<?php echo $this->content['id']; ?>">
	<img src="<?php echo $this->content['media']; ?>" title="<?php echo $this->content['title']; ?>">
</div>
