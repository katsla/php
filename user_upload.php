#!/usr/bin/env  php 

<?php 

require 'config.php';

$connection = [ "dry_run" => false, "connect" => $db ];
$new_table = false;

/* -------------------------
Set opts
----------------------------*/

$help_message = "
Usage: user_upload.php [--help] [--create_table] [--dry_run] [--file=file] [-h] [-u] [-p]

Process and insert (optional) values from an CSV file to a database.
Optional: create table 'users' in the predefined database.
Check initial settings in config.php.

Options:

        --help            Show this message
        --create_table    Create table in DB and exit
        --dry_run         Run script without altering of DB
        --file            CSV file (default: users.csv)
        -h                Server hostname (default: localhost)
        -u                User (default: root)
        -p                Password (default: root)

Example:

        user_upload.php --file=data.csv -u user -p psswrd 

";

$shortopts = "h:p:u:n:";
$longopts = array(
    "file:",
    "create_table",
    "dry_run",
    "help",
);

$opts = getopt($shortopts, $longopts);

foreach (array_keys($opts) as $opt) switch ($opt) {

    case 'help':
        exit($help_message);
    case 'create_table':
        $new_table = true;
        break;
    case 'file':
        $csvfile = $opts['file'];
    case 'dry_run':
        $connection = [ "dry_run" => true, "connect" => [] ];
        break;
    case 'h':
        $connection["connect"]["host"] = $opts['h'];
    case 'n':
        $connection["connect"]["name"] = $opts['n'] ?? $connection["connect"]["name"];
    case "p":
        $connection["connect"]["user"] = $opts["p"] ?? $connection["connect"]["user"];
    case 'u':
        $connection["connect"]["psswd"] = $opts['u'] ?? $connection["connect"]["psswd"];
}

if ( empty($opts) ) { echo "Processing the script with default settings. Check 'config.php'.\n\n"; }

/* -------------------------
Start script
----------------------------*/

if ($new_table) {

    create_table($connection["connect"]["host"], 
                          $connection["connect"]["name"], 
                          $connection["connect"]["user"], 
                          $connection["connect"]["psswd"]);
    $con = null;
    exit("Table was created/rebuilt successfully.\n");
}

file_exists($csvfile) or die("CSV file not found.\n");

process_csv_and_insert($csvfile, $connection);

$con = null;

echo "Status: completed\n\n";


/* -------------------------
Functions
----------------------------*/

function connect_db($dbhost, $dbname, $dbuser, $dbpsswd) {

    try {
        $con = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpsswd, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
    }
    catch (PDOException $e) {
        die("DB connection failed: ".$e->getMessage()."\n");
    }

    echo "\nConnected to server.\n\n";

    return $con;
}

function create_table($dbhost, $dbname, $dbuser, $dbpsswd) {

    $con = connect_db($dbhost, $dbname, $dbuser, $dbpsswd);

    $check = $con->prepare("SHOW TABLES LIKE 'users'");
    $check->execute();
    $table = $check->fetch(PDO::FETCH_NUM);

    if (check_drop_table($table)) { 

        $con = null;
        exit("\nTable has not been rebuild.\nConnection is closed.\n"); 
    }

    $query = "
    DROP TABLE IF EXISTS users;
    create table users (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(50), surname VARCHAR(50), email VARCHAR(255) NOT NULL UNIQUE);
    ";

    try {

        $create = $con->exec($query);
    }
    catch (PDOException $e) {
        $con = null;
        die("DB create table 'users' failed: ".$e->getMessage()."\n");
    }
}

function check_drop_table($table) {
    if (!empty($table)) {

        echo "The table exists. Are you sure you want to drop it? (y/N): ";
        $handle = fopen('php://stdin', 'r');
        $line = fgetc($handle);
        $line = strtolower($line);

        fclose($handle);
        if ( $line !== 'y' ) { return true;  }
    }
    return false;
}

function insert_value($first_name, $last_name, $email, $row_num, $con) {

    try {
        $ins = $con->prepare("INSERT INTO users (name, surname, email) VALUES (:name, :surname, :email);");
        $ins->bindValue(':name', $first_name);
        $ins->bindValue(':surname', $last_name);
        $ins->bindValue(':email', $email);
        $ins->execute();

        echo "Inserted row $row_num: $first_name, $last_name, $email.\n";
    }
    catch (PDOException $e) {

        $code = $e->getCode();

        if ($code == '42S02' || $code == 'HY000') { die("Error: ".$e->getMessage()."\n"); }; //Table doesn't exist or read-only

        fwrite(STDOUT, "DB insert failed on row $row_num: ".$e->getMessage()."\n");
    }
}

function process_csv_and_insert($csvfile, $connection ) {

    ini_set('auto_detect_line_endings', true);

    $handle = fopen($csvfile, 'r') or die("Could not open the data file.\n");

    fgetcsv($handle) or die("Incorrect CSV file!\n");

    if (!$connection["dry_run"]) { 
        $con = connect_db($connection["connect"]["host"], 
                          $connection["connect"]["name"], 
                          $connection["connect"]["user"], 
                          $connection["connect"]["psswd"]);
    }

    $row_num = 1;

    while (($data = fgetcsv($handle)) !== FALSE ) {

        $data = array_map('strtolower', $data);

        $first_name = clean_string($data[0]);
        $last_name = clean_string($data[1]);

        $last_name = preg_replace_callback("/\'([a-z])/", "capitalize_letter", $last_name); //processing surnames like O'Hara

        $email = filter_var($data[2], FILTER_SANITIZE_EMAIL);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            fwrite(STDOUT, "\nInvalid row $row_num: incorrect email $email. Insertion is denied.\n\n");
            continue;
        }

        if (!$connection["dry_run"]) {

            insert_value($first_name, $last_name, $email, $row_num, $con);
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
