<?php 
if(!empty($outline)){
	foreach((array)$outline as $key => $value){
		echo '<script src="' . $value . '"></script>';
	}
}

if(!empty($inline)){
	foreach((array)$inline as $key => $value){
		echo '<script type="text/javascript">' . $value . '</script>';    
	}
}