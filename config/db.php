<?php
$username = "sms";
$password = "sms123";
$connection_string = "localhost/xe";

$conn = oci_connect($username, $password, $connection_string);

if (!$conn) {
    $e = oci_error();
    die("Connection failed: " . $e['message']);
}
?>


