<?php
include("conn.php");

// SQL to drop table
$sql = "DROP TABLE IF EXISTS baseline_courses";

if ($conn->query($sql) === TRUE) {
    echo "Table 'baseline_courses' dropped successfully.";
} else {
    echo "Error dropping table: " . $conn->error;
}

// Close connection
$conn->close();
