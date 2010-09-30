<?php
 // Include library
 include_once('inc.stateobj.php');
 
 // Create new SimpleStateObj using 'test' as unique ID
 $db = new soDB('testz.sobj');
 $so = new StateObj($db, 'testz');
 
 // Stress test StateObj
 $so->testArray = array(
    'kalle' => array('tolv', 'ficklampa'),
       '24' => True,
          7 => 8
  );
 
 // Print content
 print '<pre style="width:50%;float:left">Dump1:'.print_r($so,1).'</pre>'."\n";
 
 // Delete a key
 $so->delete('testArray/kalle');
 
 // Reload the database (due to a limitation with PHP)
 $so->reload();
 
 // Print content again
 print '<pre style="width:50%;float:left">Dump2:'.print_r($so,1).'</pre>';
 
 // Increase the value of numExec
 $so->numExec += 1;
  
 // Print a message that will prove that something is actually saved in the database
 print '<h2>This script has been executed '.$so->numExec.' times!</h2>';

 // Show source
 print '<hr/>'.highlight_file(__FILE__,1);
?>