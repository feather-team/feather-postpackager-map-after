<?php 
if(!empty($outline)){
	foreach((array)$outline as $key => $value){
		echo "<link rel='stylesheet' href='{$value}' type='text/css' />";
	}
}