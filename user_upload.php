#!/usr/bin/env  php 

<?php 

require 'config.php';

$con = null;
$dry_run = false;

/* -------------------------
Set opts
----------------------------*/

$help_message = "
some help here
";

$shortopts = "h:p:u:";
$longopts = array(
    "file:",
    "create_table",
    "dry_run",
    "help",
);

$opts = getopt($shortopts, $longopts);

if ( empty($opts) ) { echo "Process the script with default settings. Check 'config.php'.\n\n"; }

foreach (array_keys($opts) as $opt) switch ($opt) {

    case 'help':
        exit($help_message);
    case 'file':
        $csvfile = $opts['file'];
    case 'dry_run':
        $dry_run = true;
        break;
    case 'h':
        $dbhost = $opts['h'];
    case 'p':
        $dbpsswd = $opts['p'];
    case 'u':
        $dbuser = $opts['u'];
}

// connect_db($dbhost, $dbname, $dbuser, $dbpsswd);
// create_table('Table', $con);

file_exists($csvfile) or die("CSV file not found.\n");

if (!$dry_run) { 

    connect_db($dbhost, $dbname, $dbuser, $dbpsswd);
}

process_csv($csvfile, $dry_run);

$con = null;


/* -------------------------
Functions
----------------------------*/

function connect_db($dbhost, $dbname, $dbuser, $dbpsswd) {

    global $con;

    try {
        $con = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpsswd, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
    }
    catch (PDOException $e) {
        die("DB connection failed: ".$e->getMessage()."\n");
    }

    echo "\nConnected to server.\n\n";
}

//function create_table($dbtable, $con) {

//    $check = $con->prepare("SHOW TABLES LIKE :tablename");
//    $check->bindValue(':tablename', $dbtable);
//    $check->execute();
//    $table = $check->fetch(PDO::FETCH_NUM);

//    if (!empty($table)) {

//        echo "The table is exist. Are you sure you want to drop it? (Yes or No=default): ";
//        $handle = fopen('php://stdin', 'r');
//        $line = fget($handle);
//        $line = trim($line);
//        $line = strtolower($line);
//        fclose($handle);
//    }
//}

function insert_value($first_name, $last_name, $email) {

    global $con, $dbtable;

    $ins = $con->prepare("INSERT INTO $dbtable (name, surname, email) VALUES (:name, :surname, :email);");
    $ins->bindValue(':name', $first_name);
    $ins->bindValue(':surname', $last_name);
    $ins->bindValue(':email', $email);
    $ins->execute();
}

function process_csv($csvfile, $dry_run ) {

    ini_set('auto_detect_line_endings', true);

    $handle = fopen($csvfile, 'r') or die("Could not open the data file.\n");

    fgetcsv($handle) or die("Incorrect CSV file!\n");

    while (($data = fgetcsv($handle)) !== FALSE ) {

        $data = array_map('strtolower', $data);

        $first_name = clean_string($data[0]);
        $last_name = clean_string($data[1]);

        $last_name = preg_replace_callback("/^O\'([a-z])/", "capitalize_letter", $last_name); //

        $email = filter_var($data[2], FILTER_SANITIZE_EMAIL);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            echo "\nInvalid row: incorrect email $email.\n\n";
            continue;
        }

        if (!$dry_run) {
    
            try { 
                insert_value($first_name, $last_name, $email);
                echo "Inserted row: $first_name, $last_name, $email.\n";
            }
            catch (PDOException $e) {
                echo "DB insert failed: ".$e->getMessage(),"\n";
            }
        } else {

            echo "Valid row: $first_name, $last_name, $email.\n";
        }
    }

    fclose($handle);

}

function clean_string($string) {

    $string = trim($string);
    $string = preg_replace("/[^a-z\s-]/i", "", $string);
    $string = ucwords($string);
    return $string;
}

function capitalize_letter($match) {

    return strtoupper("$match[0]");
}


?>
