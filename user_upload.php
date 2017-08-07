#!/usr/bin/env  php 

<?php 

require 'config.php';

$con = null;
$dry_run = false;
$new_table = false;

/* -------------------------
Set opts
----------------------------*/

$help_message = "
some help here
";

$shortopts = "h:p:u:";
$longopts = array(
    "file:",
    "create_table::",
    "dry_run",
    "help",
);

$opts = getopt($shortopts, $longopts);

foreach (array_keys($opts) as $opt) switch ($opt) {

    case 'help':
        exit($help_message);
    case 'create_table':
        if (!empty($opts['create_table'])) {
            $dbtable = $opts['create_table'];
        }
        $new_table = true;
        break;
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

if ( empty($opts) ) { echo "Processing the script with default settings. Check 'config.php'.\n\n"; }

if ($new_table) {

    connect_db($dbhost, $dbname, $dbuser, $dbpsswd);
    create_table($dbtable);
}

file_exists($csvfile) or die("CSV file not found.\n");

if (!$dry_run) { 

    connect_db($dbhost, $dbname, $dbuser, $dbpsswd);
}

process_csv($csvfile, $dry_run);

$con = null;

echo "Status: success\n\n";


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

function create_table($dbtable) {

    global $con;

    $check = $con->prepare("SHOW TABLES LIKE '$dbtable';");
    $check->execute();
    $table = $check->fetch(PDO::FETCH_NUM);

    $test = check_exit($table);

    if (check_exit($table)) { 

        echo "I am inside check_exit\n";

        $con = null;
        exit("Connection was closed.\n"); 
    }

    $query = "
    DROP TABLE IF EXISTS $dbtable;
    create table $dbtable (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(50), surname VARCHAR(50), email VARCHAR(255) NOT NULL UNIQUE);
    ";

    try {

        echo "I have to try\n";
        $create = $con->prepare($query);
        $check->bindValue(':tablename', $dbtable);
        $check->execute();

        
    }
    catch (PDOException $e) {
        $con = null;
        die("DB create table '$dbtable' failed: ".$e->getMessage()."\n");
    }

    exit("Table was created successfully.\n");
}

function check_exit($table) {
    if (!empty($table)) {

        echo "The table exists. Are you sure you want to drop it? (Yes or No=default): ";
        $handle = fopen('php://stdin', 'r');
        $line = fgetc($handle);
        $line = strtolower($line);

        echo "Line is ".(string)$line."\n";

        fclose($handle);
    }
    if ( $line = 'y' ) { 
        return false; 
    } else {
        return true;
    }
}

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

    $row_num = 1;

    while (($data = fgetcsv($handle)) !== FALSE ) {

        $data = array_map('strtolower', $data);

        $first_name = clean_string($data[0]);
        $last_name = clean_string($data[1]);

        $last_name = preg_replace_callback("/\'([a-z])/", "capitalize_letter", $last_name); //processing surnames like O'Hara

        $email = filter_var($data[2], FILTER_SANITIZE_EMAIL);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            fwrite(STDOUT, "\nInvalid row $row_num: incorrect email $email.\n\n");
            continue;
        }

        if (!$dry_run) {
    
            try { 
                insert_value($first_name, $last_name, $email);
                echo "Inserted row $row_num: $first_name, $last_name, $email.\n";
            }
            catch (PDOException $e) {

                $code = $e->getCode();

                if ($code == '42S02' || $code == 'HY000') { die("Error: ".$e->getMessage()."\n"); }; //Table doesn't exist or read-only

                fwrite(STDOUT, "DB insert failed on row $row_num: ".$e->getMessage(),"\n");
            }
        } else {

            echo "Valid row $row_num: $first_name, $last_name, $email.\n";
        }
        $row_num++;
    }

    fclose($handle);

}

function clean_string($string) {

    $string = trim($string);
    $string = preg_replace("/[^a-z'\s-]/i", "", $string);
    $string = ucwords($string);
    return $string;
}

function capitalize_letter($match) {

    return strtoupper("$match[0]");
}


?>
