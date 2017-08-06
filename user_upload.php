<?php 

$dbhost = "localhost";
$dbname = "Test";
$dbtable = "Users";
$dbuser = "root";
$dbpsswd = "root";
$fieldseparator = ","; 
$lineseparator = "\n";
$csvfile = "users.csv";


if (!file_exists($csvfile)) {

	die("CSV file not found.\n");

}

ini_set('auto_detect_line_endings', true);

$handle = fopen($csvfile, 'r');

if (!$handle) {

	die("Could not open the data file.\n");

}

// to remove the first line
fgetcsv($handle);

while (($data = fgetcsv($handle)) !== FALSE ) {
		

        $charlist = " \t\n\r\0..\x40\x5B..\x60\x7B..\x7F";


	$name = ucwords(strtolower(trim($data[0], $charlist)));

	$surname = ucwords(strtolower(trim($data[1], $charlist)));
        
        $surname = preg_replace_callback("/^O\'([a-z])/", function($match) { return strtoupper("$match[0]"); }, $surname);

	$email = strtolower(ltrim(rtrim($data[2], $charlist)));

        echo $name.", ".$surname.", ".$email,"\n";
	
	


}

fclose($handle);

// try {

// 	$con = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpsswd, array(PDO::MYSQL_ATTR_LOCAL_INFILE => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
     

// }
// catch (PDOException $e) {

//         die("DB connection failed: ".$e->getMessage());

// }

// echo "Server have been connected. \n";

// $loaded = $con -> exec("LOAD DATA LOCAL INFILE ".$con->quote($csvfile)." INTO TABLE `$dbtable` FIELDS TERMINATED BY ".$con->quote($fieldseparator)." LINES TERMINATED BY ".$con->quote($lineseparator)." IGNORE 1 LINES (name, surname, email)");

// echo "Loaded a total of $loaded records. \n";

?>
