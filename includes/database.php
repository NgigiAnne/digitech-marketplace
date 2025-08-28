<?php
function getDB() {
    $host = "localhost";
    $user = "root";
    $password = "";
    $dbname = "digitech_marketplace";

    // Create connection
    $conn = new mysqli($host, $user, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}
?>