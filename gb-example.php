<?php
 include_once('inc.stateobj.php');
 
 $db = new SoDB('so_gb.db');
 $gb = new StateObj($db, 'gb_data');
 
 if(!isset($gb->data)) $gb->data = array(); // Create structure if first time run
 
 if(@$_GET['name']){
	 $gb->data[] = array('name' => $_GET['name'], 'mess' => $_GET['mess']);
 }
?>
<h2>new row:</h2>
<form>
 Name: <input type="text" name="name" /><br/>
 Message: <textarea name="mess"></textarea>
 <input type="submit" value="add" />
</form>
<hr />
<h2>rows from database:</h2>
<?php
 foreach($gb->data as $row){
?>
<b><?=$row['name']?></b>: <?=$row['mess']?><br/>
<?php } ?>
<hr/>
<h2>gb-example.php source:</h2>
<?php highlight_file('gb-example.php'); ?>