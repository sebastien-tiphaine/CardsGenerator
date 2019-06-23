<?php 
	// setting css classes
	$strClass = 'text';
	// does the display param is equal to main type
	if($this->content['display'] != 'text'){
		// no
		$strClass.=' text-'.$this->content['display']; 
	}
?>
<div class="<?php echo $strClass; ?> <?php echo $this->content['css']; ?>" id="<?php echo $this->content['id']; ?>">
<?php echo $this->content['media']; ?>
</div>
