<?php
	
	// setting default zones datas
	$arrContentZones = array();
	$arrFooterZones  = array();

	// do we have medias rendered
	if(isset($this->content['medias']) && is_array($this->content['medias']) && !empty($this->content['medias'])){
		// yes
		// extracting zone content (top, center, 1, bottom)
		$arrContentZones = array_intersect_key ($this->content['medias'], array_flip(array('1', 'center', 'top', 'bottom')));
		
		// do we have item in the default zone
		if(isset($arrContentZones[1])){
			// yes
			// do we have to merge them with the center zone
			if(isset($arrContentZones['center'])){
				// yes
				$arrContentZones['center'] = array_merge($arrContentZones[1], $arrContentZones['center']);
			}
			else{
				// no center zone defined. Using default zone as center zone
				$arrContentZones['center'] = $arrContentZones[1];
			}
			
			// unset zone 1
			unset($arrContentZones[1]);
		}
		
		// extracting zone content (footer)
		$arrFooterZones = array_intersect_key ($this->content['medias'], array_flip(array('footer')));
	}
	
	// do we have to set the wrapper
	if(!empty($arrContentZones)){ ?>
		<div class="contentcontainer">
			
			<?php if(isset($arrContentZones['top']['data'])){ ?>
				<div class="zonetop">
					<?php echo implode('', $arrContentZones['top']['data']); ?>
				</div>
			<?php } ?> 
			
			<?php if(isset($arrContentZones['center']['data'])){ ?>
				<div class="zonecenter">
					<?php echo implode('', $arrContentZones['center']['data']); ?>
				</div>
			<?php } ?>
			
			<?php if(isset($arrContentZones['bottom']['data'])){ ?>
				<div class="zonebottom">
					<?php echo implode('', $arrContentZones['bottom']['data']); ?>
				</div>
			<?php } ?>
				
		</div><?php
	}

	// do we have to set the wrapper
	if(!empty($arrFooterZones)){ ?>
		<div class="footercontentcontainer">
			
			<?php if(isset($arrFooterZones['footer']['data'])){ ?>
				<div class="zonefooter">
					<?php echo implode('', $arrFooterZones['footer']['data']); ?>
				</div>
			<?php } ?> 
				
		</div><?php
	}
