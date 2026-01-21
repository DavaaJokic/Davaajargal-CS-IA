<?php
include "connection.php";

echo "<h2>Checking Database Structure...</h2>";

// Check users table
echo "<h3>users table:</h3>";
$result = mysqli_query($conn, "DESCRIBE users");
echo "<table border='1'><tr><th>Field</th><th>Type</th></tr>";
while($row = mysqli_fetch_assoc($result)) {
    echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
}
echo "</table>";

// Check groups table
echo "<h3>groups table:</h3>";
$result = mysqli_query($conn, "DESCRIBE groups");
echo "<table border='1'><tr><th>Field</th><th>Type</th></tr>";
while($row = mysqli_fetch_assoc($result)) {
    echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
}
echo "</table>";

// Check photos table
echo "<h3>photos table:</h3>";
$result = mysqli_query($conn, "DESCRIBE photos");
echo "<table border='1'><tr><th>Field</th><th>Type</th></tr>";
while($row = mysqli_fetch_assoc($result)) {
    echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
}
echo "</table>";
?>