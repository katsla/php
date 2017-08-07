# User upload script

This is a simple PHP project that includes a script for processing and uploading CSV file (of a certain format) into a table of a MySql database and a configuration file with defaults.
Script also could make simple manipulations with a database such as checking existence and creating a table.

## Getting Started

These instructions will get you a copy of the project up and running on your local machine for development and testing purposes.

### Prerequisites

To run the script you need to have on your local machine:

```
Linux Debian-like OS
MySql 5.5 (or above)
PHP 7 (or above)
```

### Installing

Just copy all .php files in any directory and run script user_upload.php:

```
./user_upload.php
```

Or add a path in bashrc:

```
echo "export PATH=$PATH:/path/to/dir_with_script" >> ~/.bashrc
source ~/.bashrc
user_upload.php
```

## Details

You could see all of the script functions and keys by running:

```
user_upload.php --help
```
A user could alter a host, a DB name, a username, and a password.
The DB must be created before running the script.

Default settings for a SQL connection are kept in config.php. You may change settings by editing the file instead of using the keys.
Unfortunately, on this stage of development the file has not been encrypted, so keeping your password in it will be unsafe.


## TODO

* Add validation for CSV files.
* Add multiple CSV files uploading.
* Add a secure way to put the table name into a variable.
* Move options to a function.
* Add encryption to a config file.

