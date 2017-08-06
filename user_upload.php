<?php 

$dbhost = "localhost";
$dbname = "Test";
$dbtable = "Users";
$dbuser = "root";
$dbpsswd = "root";
$fieldseparator = ","; 
$lineseparator = "\n";
$csvfile = "users.csv";

// echo "start";

if (!file_exists($csvfile)) {

die("File not found.\n");

}

try {
// ini_set('auto_detect_line_endings',TRUE);

	$con = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpsswd, array(PDO::MYSQL_ATTR_LOCAL_INFILE => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));

        echo "Connected on. \n";

}
catch (PDOException $ex) {

        die("DB connection failed: ".$e->getMessage());

}

$loaded = $con -> exec("LOAD DATA LOCAL INFILE ".$con->quote($csvfile)." INTO TABLE `$dbtable` FIELDS TERMINATED BY ".$con->quote($fieldseparator)." LINES TERMINATED BY ".$con->quote($lineseparator)." IGNORE 1 LINES (name, surname, email)");

echo "Loaded a total of $loaded records. \n";

?>
