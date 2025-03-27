<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "notebooks";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["error" => "Database connection failed"]));
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(["error" => "Notebook ID is required"]);
    exit;
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT id, title, notes, pdf_path FROM notebooks WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        "id" => $row["id"],
        "title" => $row["title"],
        "notes" => json_decode($row["notes"]),
        "pdf_path" => $row["pdf_path"]
    ]);
} else {
    echo json_encode(["error" => "Notebook not found"]);
}

$conn->close();
?>
