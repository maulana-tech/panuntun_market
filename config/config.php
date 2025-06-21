<?php

$db_server = "localhost";
$db_username = "root";
$db_password = "";
$db_name = "db_minimarket";

$mysqli = new mysqli($db_server, $db_username, $db_password, $db_name);

if ($mysqli->connect_error) {
    die("KONEKSI GAGAL: " . $mysqli->connect_error);
}

$mysqli->set_charset("utf8");

?>
