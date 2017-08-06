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



// try to connect to DB

// try {

//    $con = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpsswd, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
//}
//catch (PDOException $e) {

//    die("DB connection failed: ".$e->getMessage());
//}

// echo "\nConnected to server.\n\n";

// $conn = null;

process_csv($csvfile);

function insert_value($name, $surname, $email) {

    global $con, $dbtable;

    $ins = $con->prepare("INSERT INTO $dbtable (name, surname, email) VALUES (:name, :surname, :email);");
    $ins->bindValue(':name', $name);
    $ins->bindValue(':surname', $surname);
    $ins->bindValue(':email', $email);
    $ins->execute();
}

function process_csv($csvfile, $db_connect = false) {

ini_set('auto_detect_line_endings', true);

$handle = fopen($csvfile, 'r') or die("Could not open the data file.\n");

fgetcsv($handle) or die("Incorrect CSV file!\n");

while (($data = fgetcsv($handle)) !== FALSE ) {

    $charlist = " \t\n\r\0..\x40\x5B..\x60\x7B..\x7F"; // list of invalid characters

    $data[0] = trim($data[0], $charlist);
    $data[1] = trim($data[1], $charlist);

    $data = array_map('strtolower', $data);

    $name = ucwords($data[0]);
    $surname = ucwords($data[1]);

    $surname = preg_replace_callback("/^O\'([a-z])/", function($match) { return strtoupper("$match[0]"); }, $surname);

    $email = filter_var($data[2], FILTER_SANITIZE_EMAIL);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        echo "\nInvalid row: incorrect email $email.\n\n";
        continue;
    }

    if ($db_connect) {
    
        insert_value($name, $surname, $email);
    } else {

        echo "Valid row: $name, $surname, $email.\n";
    }
}

fclose($handle);

}

?>
