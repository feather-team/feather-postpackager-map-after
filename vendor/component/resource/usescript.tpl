<?php foreach((array)$outline as $key => $value):?>
<script src="<?=$value;?>"></script>
<?php endforeach;?>

<?php foreach((array)$inline as $key => $value):?>
<script type="text/javascript"><?=$value;?></script>    
<?php endforeach;?>