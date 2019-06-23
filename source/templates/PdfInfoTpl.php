<?php
	// do we have to set the wrapper
	if($this->hasvar()){ ?>
		<div class="contentcontainer">
	<?php }

	// do we have info to display ?
	if(isset($this->info)){ ?>
		<div class="info"><?php echo $this->info; ?></div>
	<?php }
	
	// do we have a single image (placed before the image group)
	if(isset($this->image) && is_array($this->image) && $this->isVarBefore('image', 'images')){ ?>
		<img src="<?php echo $this->image['media']; ?>" title="<?php echo $this->image['title']; ?>" class="<?php echo $this->image['css']; ?>">
	<?php }
	
	// do we have an image group
	if(isset($this->images)){
			foreach($this->images as $mImgKey => $arrImage){
				if(!is_array($arrImage) || !is_numeric($mImgKey)){
					continue;
				} ?>
				<img src="<?php echo $arrImage['media']; ?>" title="<?php echo $arrImage['title']; ?>" class="<?php echo $arrImage['css']; ?>">
	 <?php }
	 }
	 
	 // do we have a single image (placed after the image group)
	if(isset($this->image) && is_array($this->image) && !$this->isVarBefore('image', 'images')){ ?>
		<img src="<?php echo $this->image['media']; ?>" title="<?php echo $this->image['title']; ?>" class="<?php echo $this->image['css']; ?>">
	<?php }

	// do we have a remark to display ?
	if(isset($this->rem)){ ?>
		<div class="rem"><?php echo $this->rem; ?></div>
	<?php }
 
	 // do we have to close the wrapper
	 if($this->hasvar()){ ?>
		</div>
	<?php }

	if((isset($this->sound) && is_array($this->sound))){?>
		<div class="soundcontainer <?php echo $this->sound['css']; ?>">
			<?php if(isset($this->sound['title'])){?>
				<div class="soundtitle"><?php echo $this->sound['title']; ?></div>
			<?php } ?>
			<div class="audio">
				<span class="audiolabel">
					<?php if(isset($this->sound['label'])){
						echo $this->sound['label'];
					 } ?>
				</span>
				[ <?php echo ((is_array($this->sound['media'])) ? $this->sound['media'][0]:$this->sound['media']); ?> ]
				</div>
		</div>
	<?php }

	if(isset($this->sounds) && is_array($this->sounds)){ ?>
		<div class="soundcontainer">
			<?php if(isset($this->sounds['title'])){?>
				<div class="soundtitle"><?php echo $this->sounds['title']; ?></div>
			<?php } ?>
			
				<?php foreach($this->sounds as $mSoundKey => $arrSound){
						if(!is_numeric($mSoundKey)) continue;
					?>
					<div class="audio <?php echo $arrSound['css']; ?>">
						<span class="audiolabel">
							<?php if(isset($arrSound['label'])){
								 echo $arrSound['label'];
							} ?>
						</span>
						[ <?php echo ((is_array($arrSound['media'])) ? $arrSound['media'][0]:$arrSound['media']); ?> ]
					</div>
				<?php } ?>
		</div>
	<?php }
