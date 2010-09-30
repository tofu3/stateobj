<?php
 include_once('inc.stateobj.php');
 
 $db = new SoDB('gb-example.sobj');
 $gb = new StateObj($db, 'gb-example'); 

 //!isset($gb->data)?$gb->data = array(0 => array('name' => 'test', 'mess' => 'msg')):0;
 //$gb->dataz[0] = 'lala';
 
 
 if($_GET['name']){
	 $gb->data[] = array('name' => $_GET['name'], 'mess' => $_GET['mess']);
	 $gb->save('data');
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
 print '<br/>';
 $revdata = array_reverse($gb->data);
 foreach($revdata as $row){
?>
<b><?=$row['name']?></b>: <?=$row['mess']?><br/>
<?php } ?>
<hr/>
<h2>gb-example.php source:</h2>
<?php highlight_file('gb-example.php'); ?>
