<?php
	// getting type
	$strType = (isset($this->content['properties']['type']) && $this->content['properties']['type'] == 'horiz')? 'horiz':'vertic';
	
?>
<div class="group group-<?php echo $strType; ?> group-<?php echo $this->content['group']; ?> group-<?php echo $this->content['group']; ?>-<?php echo $strType; ?>">
<?php
	
	if(isset($this->content['data'])){
		
		// do we have a array of items
		if(is_array($this->content['data'])){
			// do we have a single item
			if(count($this->content['data']) < 2){
				// yes
				echo current($this->content['data']);
			}
			else{
				// no we have more than one item
				
				// do we have an horiz type
				if($strType == 'horiz'){
					// yes
					?><table align="center"><tr><?php

						foreach($this->content['data'] as $strData){ 
							?><td class="groupitem"><?php echo  $strData; ?></td><?php
						}
						
					?></tr></table><?php
				}
				else{
					// type must be vertic
					foreach($this->content['data'] as $strData){ 
						?><div class="groupitem"><?php echo $strData; ?></div><?php
					}
				}
			}// end of many items
		}// end of is_array
		else{
			// content is not an array
			// just outputing content
			echo $this->content['data'];
		}
	}
?></div>
