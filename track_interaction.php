<?php
session_start();
date_default_timezone_set('Asia/Colombo');
$conn = new mysqli("localhost", "root", "", "asb_file_system");
$conn->set_charset("utf8mb4");

if (isset($_GET['id']) && isset($_SESSION['user_id'])) {
    $doc_id = (int)$_GET['id'];
    $user_id = $_SESSION['user_id'];
    $type = $_GET['type'] ?? 'VIEW';
    $user_name = $_SESSION['user_name'] ?? 'System User'; 

    // Fetch document and category details
    $info_stmt = $conn->prepare("SELECT d.title, c.category_name FROM documents d 
                                JOIN categories c ON d.category_id = c.id 
                                WHERE d.id = ?");
    $info_stmt->bind_param("i", $doc_id);
    $info_stmt->execute();
    $data = $info_stmt->get_result()->fetch_assoc();

    if ($data) {
        $doc_name = $data['title'];
        $cat_name = $data['category_name'];
        $now = date('Y-m-d H:i:s');

        // Update if exists, otherwise insert
        $track_stmt = $conn->prepare("INSERT INTO document_interactions 
            (user_id, user_name, doc_id, doc_name, category_name, interaction_type, clicked_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            interaction_type = VALUES(interaction_type),
            clicked_at = VALUES(clicked_at)");
        
        $track_stmt->bind_param("isissss", $user_id, $user_name, $doc_id, $doc_name, $cat_name, $type, $now);
        $track_stmt->execute();
    }

    // Redirect to the actual file action
    $redirect = ($type == 'VIEW') ? "download.php?id=$doc_id&view=1" : "download.php?id=$doc_id";
    header("Location: $redirect");
    exit();
}