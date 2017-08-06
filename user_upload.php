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

    $con = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpsswd, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
}
catch (PDOException $e) {

    die("DB connection failed: ".$e->getMessage());
}

echo "\nConnected to server.\n\n";

fgetcsv($handle); // ignore the first line

while (($data = fgetcsv($handle)) !== FALSE ) {

    $charlist = " \t\n\r\0..\x40\x5B..\x60\x7B..\x7F";

    $name = ucwords(strtolower(trim($data[0], $charlist)));

    $surname = ucwords(strtolower(trim($data[1], $charlist)));
    $surname = preg_replace_callback("/^O\'([a-z])/", function($match) { return strtoupper("$match[0]"); }, $surname); //processing O'Hara etc.

    $email = strtolower($data[2]);
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        echo "Email $email is not valid. Row will not be added to DB.\n";
        continue;
    }

    echo $name.", ".$surname.", ".$email,"\n";

    insert_value($name, $surname, $email);
}

$conn = null;

fclose($handle);

function insert_value($name, $surname, $email) {

    global $con, $dbtable;

    $ins = $con->prepare("INSERT INTO $dbtable (name, surname, email) VALUES (:name, :surname, :email);");
    $ins->bindValue(':name', $name);
    $ins->bindValue(':surname', $surname);
    $ins->bindValue(':email', $email);
    $ins->execute();
}


// $loaded = $con -> exec("LOAD DATA LOCAL INFILE ".$con->quote($csvfile)." INTO TABLE `$dbtable` FIELDS TERMINATED BY ".$con->quote($fieldseparator)." LINES TERMINATED BY ".$con->quote($lineseparator)." IGNORE 1 LINES (name, surname, email)");

// echo "Loaded a total of $loaded records. \n";

?>
