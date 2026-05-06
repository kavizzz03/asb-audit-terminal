<?php
// 1. Database Connection
$conn = new mysqli("localhost", "root", "", "asb_file_system");

// 2. We are looking for 'id' to match the link in documents.php
if (isset($_GET['id'])) {
    
    $id = intval($_GET['id']); 

    // Fetch the document path and title
    $stmt = $conn->prepare("SELECT file_path, title FROM documents WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result) {
        $path = $result['file_path'];
        $title = $result['title'];
        
        // view=1 shows in browser, otherwise it downloads
        $mode = isset($_GET['view']) ? 'inline' : 'attachment';

        if (file_exists($path)) {
            
            // CLEAN THE BUFFER - Removes XAMPP warnings and white spaces
            if (ob_get_length()) ob_clean();
            
            // Set PDF Headers
            header('Content-Type: application/pdf');
            header('Content-Disposition: ' . $mode . '; filename="' . basename($path) . '"');
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: ' . filesize($path));
            header('Accept-Ranges: bytes');
            header('Pragma: no-cache');
            header('Expires: 0');

            // Stream the file
            readfile($path);
            exit;
            
        } else {
            die("Error: File not found on server disk at: " . htmlspecialchars($path));
        }
    } else {
        die("Error: Database record #" . $id . " does not exist.");
    }
} else {
    // This triggers if the URL is just download.php instead of download.php?id=XX
    die("Error: No file ID provided in the URL.");
}
?>