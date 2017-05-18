<?php

function getDB() {
	$servername = "";
	$username = "";
	$password = "";
	$db = "";

	// Create connection
	$conn = new mysqli($servername, $username, $password, $db);

	// Check connection
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	} 

	$conn->set_charset("utf8");
	return $conn;
}