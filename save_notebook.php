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

// Get input data
$id = isset($_POST['id']) ? intval($_POST['id']) : null;
$title = isset($_POST['title']) ? $_POST['title'] : null;
$notes = isset($_POST['notes']) ? $_POST['notes'] : '[]';  // Default to empty array if not received
$pdfPath = null;

if (!$title) {
    echo json_encode(["error" => "Title is required"]);
    exit;
}

// Retrieve existing notebook if updating
if ($id) {
    $result = $conn->query("SELECT pdf_path, notes FROM notebooks WHERE id = $id");
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $pdfPath = $row['pdf_path'];  // Keep existing PDF if no new file uploaded
        if (empty($notes) || $notes == 'null') {
            $notes = $row['notes'];  // Keep existing notes if no new ones provided
        }
    }
}

// Handle PDF upload (if a new file is chosen)
if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] == 0) {
    $pdfDir = "uploads/";
    $pdfPath = $pdfDir . basename($_FILES['pdf']['name']);
    move_uploaded_file($_FILES['pdf']['tmp_name'], $pdfPath);
}

// **Convert notes to JSON safely**
$notesJson = json_encode(json_decode($notes, true), JSON_UNESCAPED_UNICODE);

// **If ID exists, update instead of inserting**
if ($id) {
    $query = "UPDATE notebooks SET title=?, notes=?, pdf_path=? WHERE id=?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssi", $title, $notesJson, $pdfPath, $id);
} else {
    $query = "INSERT INTO notebooks (title, notes, pdf_path) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $title, $notesJson, $pdfPath);
}

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Notebook saved successfully"]);
} else {
    echo json_encode(["error" => "Database error: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
