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

// try to connect to DB

try {

	$con = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpsswd, array(PDO::MYSQL_ATTR_LOCAL_INFILE => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
     

 }
 catch (PDOException $e) {

         die("DB connection failed: ".$e->getMessage());

 }

 echo "\nServer has been connected. \n\n";

// to remove the first line
fgetcsv($handle);

while (($data = fgetcsv($handle)) !== FALSE ) {
		

        $charlist = " \t\n\r\0..\x40\x5B..\x60\x7B..\x7F";


	$name = ucwords(strtolower(trim($data[0], $charlist)));

	$surname = ucwords(strtolower(trim($data[1], $charlist)));
        
        $surname = preg_replace_callback("/^O\'([a-z])/", function($match) { return strtoupper("$match[0]"); }, $surname);

        $surname = str_replace("'", "\'", $surname);

	$email = strtolower($data[2]);

	$email = filter_var($email, FILTER_SANITIZE_EMAIL);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

	 	echo "Email $email is not valid. Row will not be added to DB.\n";
		continue;

	}

	$email = str_replace("'", "\'", $email);

        echo $name.", ".$surname.", ".$email,"\n";

        

        $con -> exec("INSERT INTO $dbtable (name, surname, email) VALUES ('$name', '$surname', '$email');");
	
	


}

$conn = null;

fclose($handle);


// $loaded = $con -> exec("LOAD DATA LOCAL INFILE ".$con->quote($csvfile)." INTO TABLE `$dbtable` FIELDS TERMINATED BY ".$con->quote($fieldseparator)." LINES TERMINATED BY ".$con->quote($lineseparator)." IGNORE 1 LINES (name, surname, email)");

// echo "Loaded a total of $loaded records. \n";

?>
