<?php

$serverName = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbusername = "rethink";
$dbpassword = "yovat2=Ups";
$dbname = "test_db";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = mysqli_connect($serverName, $dbusername, $dbpassword, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
