<?php
/**********************************************************************************************

    inc.stateobj.php
    StateObj class, rev 2
	A PHP class for making "state"-objects, which save their data in a database between sessions.
    
    WARNING: NOT for productional use. This code is NOT stable.
	
    Licensed under GPL v3 for now. Contact me if you have need for another license.
   
    Copyright (C) 2010, Nils Måsén

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.


***********************************************************************************************/

class SoDB{
	private $db;
	private $dbfile;
	
	// Assign variables, load DB file and call createStructure()
	function __construct($file){
		$this->dbfile = $file;
		$this->db = new SQLite3($file);
		$this->createStructure();
	}
	
    function __toString(){
        return "[SoDB-rev3@{$this->file}]";
    }
    
	// Create the table where SO data will reside
	function createStructure(){
		if(!@$this->db->exec('CREATE TABLE IF NOT EXISTS so_data (name STRING, key STRING, val STRING) '))
            die('Database locked! Try again later.');
	}
	
	// Put data in DB
	function setVar($name, $key, $val){
		if($this->db->querySingle("SELECT * FROM so_data WHERE key='$key' AND name='$name'")){
			$st = $this->db->prepare("UPDATE so_data SET val=:val WHERE key=:key AND name=:name ");
			$st->bindValue(':val', $val, SQLITE3_TEXT);
			$st->bindValue(':key', $key, SQLITE3_TEXT);
			$st->bindValue(':name', $name, SQLITE3_TEXT);
            //print "$name [$key] = $val";
		}
		else{
			$st = $this->db->prepare("INSERT INTO so_data (name, key, val) VALUES (:name, :key,:val)");
			$st->bindValue(':val', $val, SQLITE3_TEXT);
			$st->bindValue(':key', $key, SQLITE3_TEXT);
			$st->bindValue(':name', $name, SQLITE3_TEXT);
		}
        for($i=0;$i<10;$i++){
            $ret = @$st->execute();
            if($ret) return $ret;
            sleep(1);
        }
	}
	
    function getVar($name, $key){
		$st = $this->db->prepare('SELECT key, val FROM so_data WHERE name=:name AND key=:key');
		$st->bindValue(':name', $name, SQLITE3_TEXT);
        $st->bindValue(':key' , $key,  SQLITE3_TEXT);
    }
    
    function delVar($name, $key){
        $this->db->querySingle("DELETE FROM so_data WHERE key='$key' AND name='$name'");
    }
    
	// Simple wrapper of the latest database error
	function lastErrorMessage(){
		return $this->db->lastErrorMessage();	
	}
	
	// Used by getAllVars
	private function buildArray($array, $curr, $step, $val){
		if($step==0)
			return array((string)$curr => $val);
		else
			return array((string)$curr => $array);
	}
	
	// Custom array_merge_recursive, due to it handling numbers "wrong"
	private function customMerge() {
		$arrays = func_get_args();
		$base = array_shift($arrays);
		if(!is_array($base)) $base = empty($base) ? array() : array($base);
			foreach($arrays as $append) {
			if(!is_array($append)) $append = array($append);
			foreach($append as $key => $value) {
				if(!array_key_exists($key, $base) and !is_numeric($key)) {
					$base[$key] = $append[$key];
					continue;
				}
				if(is_array($value) or is_array($base[$key]))
					$base[$key] = @$this->customMerge($base[$key], $append[$key]);
				else if(is_numeric($key))
					if(!in_array($value, $base)) $base[] = $value;
		  		else 
					$base[$key] = $value;
			}
		}
		return $base;
	}
	
	// Get variables from the database and assign them to the object
	function getAllVars($name){
		$merarr = array();
		$st = $this->db->prepare('SELECT key, val FROM so_data WHERE name=:name');
		$st->bindValue(':name', $name, SQLITE3_TEXT);
		$result = $st->execute();
		// Iterate through the database rows
		while($row = $result->fetchArray(SQLITE3_ASSOC)){
			$path = explode('/', $row['key']);
			if(count($path)>0){
				$step = 0;
				$array = array();
				while(NULL != ($curr = array_pop($path))){
					$array = $this->buildArray($array, $curr, $step, $row['val']);	
					$step++;
				}
			}
			else {
				$array = array($row['key'] => $row['val']);	
			}
			$merarr = $this->customMerge($merarr,$array);
		}
        return $merarr;
	}
	
	function __destruct(){
		$this->db->close();	
	}
	
}

class SimpleStateObj extends StateObj{
    function __construct($name) {
		$this->db = new SoDB($name.'.sobj');
		$this->name = $name;
		// Load data from the DB
		$this->data = $this->db->getAllVars($name);
    }
}

class StateObj {
    private $data = array();
	private $db;
	private $name;
	
    public function __construct($db, $name) {
		$this->db = $db;
		$this->name = $name;
		// Load data from the DB
		$this->data = $this->db->getAllVars($name);
    }

    public function __set($key, $val) {
        if(is_array($val)){
            $nest = $this->flatVars($val);
            foreach($nest as $k=>$v){
                $this->db->setVar($this->name, $key.'/'.$k, $v);
            }
        }
        else {
            $this->db->setVar($this->name, $key, $val);
        }
        $this->data[$key] = $val;
    }

    public function __get($key) {
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }
        return null;
    }

    public function __isset($key) {
        return isset($this->data[$key]);
    }

    public function delete($path){
        $pa = explode('/', $path);
        $ref = &$this->data[array_shift($pa)];
        foreach($pa as $k)
          $ref = &$ref[$k];
          
        $key = &$ref;
    
        if(is_array($key)){
            $nest = $this->flatVars($key);
            foreach($nest as $k=>$v){
                $this->db->delVar($this->name, $path.'/'.$k);
            }
        }
        else{
            $this->db->delVar($this->name, $path);
            unset($key);
        }
    }

    public function __unset($key) {
        if(is_array($this->data[$key])){
            $nest = $this->flatVars($this->data[$key]);
            foreach($nest as $k=>$v){
                $this->db->delVar($this->name, $k);
            }
        }
        else
            $this->db->delVar($this->name, $key);
        unset($this->data[$key]);
    }
    
	function listVars(){
		foreach($this->flatVars($this) as $key => $val)
			print "'$key' => '$val'\n";
	}

    public function reload(){
        $this->data = $this->db->getAllVars($this->name);
    }

	// Used by flatVars to iterate through arrays
	function iterArrayToPath($inkey, $inval){
		$a = array();
		foreach($inval as $key => $val) {
			if(is_array($val))
				$a = array_merge($a, $this->iterArrayToPath($inkey."/".$key,$val) );
			else
				$a[$inkey."/".$key] = $val;
		}
		return $a;
	}

	// Function for making a "array-nest" into a flat array with "paths"
	private function flatVars($array){
		$ret = array();
        foreach($array as $key => $val) {
            $key = (string)$key;
			if(is_object($val)) continue; // Skip objects for now, may add support for objects later
			if(($key[0]=='_') && ($key[1]=='_' )) continue; //Skip variables used by SO
		    if(is_array($val)){ // Iter thtough arrays
				$retarr = $this->iterArrayToPath($key, $val);
				foreach($retarr as $key => $val){
					$ret[$key]=$val;
				}
		    }
		    else{
				$ret[$key]=$val;
		    }
		}
		return $ret;
	}

    
	// Flatten data and save to db
	function saveData(){
        return 0; // Not needed anymore
        if(!$this->db) die();
		foreach($this->flatVars($this) as $key => $val)
			if(!$this->db->setVar($this->__name,$key,$val))
				print $this->db->lastErrorMsg();	
	}

	// Save the data on script end
    function __destruct() {
		$this->saveData();
    }
   
}

function rimplode($glue, $pieces){
  foreach($pieces as $piece)
     $r[]=is_array($piece)?rimplode($glue,$piece):$piece;
  return implode($glue, $r);
} 

?>