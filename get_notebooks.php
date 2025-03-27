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

// Get parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$limit = 5;
$offset = ($page - 1) * $limit;

// Search filter
$whereClause = "";
if (!empty($search)) {
    $whereClause = "WHERE title LIKE '%" . $conn->real_escape_string($search) . "%'";
}

// Fetch total records
$totalQuery = "SELECT COUNT(*) AS total FROM notebooks $whereClause";
$totalResult = $conn->query($totalQuery);
$totalRow = $totalResult->fetch_assoc();
$totalRecords = $totalRow['total'];
$totalPages = ceil($totalRecords / $limit);

// Fetch notebooks
$query = "SELECT * FROM notebooks $whereClause ORDER BY id DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($query);

$notebooks = [];
while ($row = $result->fetch_assoc()) {
    $notebooks[] = [
        "id" => $row["id"],
        "title" => $row["title"],
        "notes" => json_decode($row["notes"], true)
    ];
}

// Return JSON response
echo json_encode([
    "notebooks" => $notebooks,
    "currentPage" => $page,
    "totalPages" => $totalPages
]);

$conn->close();
?>