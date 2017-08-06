<?php 

/* -------------------------
Set defaults
----------------------------*/

require 'config.php';

$con = null;
$db_connect = true;

/* -------------------------
Set opts
----------------------------*/

$help = "
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
        die($help);
    case 'file':
        $csvfile = $opts['file'];
    case 'dry_run':
        $db_connect = false;
        break;
    case 'h':
        $dbhost = $opts['h'];
    case 'p':
        $dbpsswd = $opts['p'];
    case 'u':
        $dbuser = $opts['u'];
}

connect_db($dbhost, $dbname, $dbuser, $dbpsswd);
create_table('Table', $con);

file_exists($csvfile) or die("CSV file not found.\n");

if ($db_connect) { 

    connect_db($dbhost, $dbname, $dbuser, $dbpsswd);
}

process_csv($csvfile, $db_connect);

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

function insert_value($name, $surname, $email) {

    global $con, $dbtable;

    $ins = $con->prepare("INSERT INTO $dbtable (name, surname, email) VALUES (:name, :surname, :email);");
    $ins->bindValue(':name', $name);
    $ins->bindValue(':surname', $surname);
    $ins->bindValue(':email', $email);
    $ins->execute();
}

function process_csv($csvfile, $db_connect ) {

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

        $surname = preg_replace_callback("/^O\'([a-z])/", "upletter", $surname);

        $email = filter_var($data[2], FILTER_SANITIZE_EMAIL);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            echo "\nInvalid row: incorrect email $email.\n\n";
            continue;
        }

        if ($db_connect) {
    
            try { 
                insert_value($name, $surname, $email);
                echo "Inserted row: $name, $surname, $email.\n";
            }
            catch (PDOException $e) {
                echo "DB insert failed: ".$e->getMessage();
            }
        } else {

            echo "Valid row: $name, $surname, $email.\n";
        }
    }

    fclose($handle);

}

function upletter($match) {

    return strtoupper("$match[0]");
}

?>
