<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: dashboard.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "asb_file_system");
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$show_sms_prompt = false;
$error_message = null;
$sms_params = [];

// --- CRUD LOGIC ---
if (isset($_POST['add_doc'])) {
    $title = trim($_POST['title']);
    $date = $_POST['doc_date'];
    $number = trim($_POST['doc_number']);
    $cat_id = intval($_POST['category_id']); 
    $branch_input = intval($_POST['branch_id']); 

    // Validation
    if (empty($title) || empty($number) || empty($cat_id)) {
        $error_message = "අත්‍යවශ්‍ය ක්ෂේත්‍ර හිස්ව පැවතීම / All critical fields must be populated.";
    } 
    elseif (!isset($_FILES['doc_file']) || $_FILES['doc_file']['error'] !== UPLOAD_ERR_OK) {
        $error_message = "ගොනුව උඩුගත කිරීම අසාර්ථකයි / File upload failed. Please check file size.";
    }
    elseif ($_FILES['doc_file']['type'] !== 'application/pdf') {
        $error_message = "අවලංගු ගොනු වර්ගය: PDF පමණක් පිළිගනු ලැබේ / Invalid File Type: Only PDF documents are accepted.";
    } 
    else {
        $target_dir = "uploads/docs/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_name = time() . "_" . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($_FILES["doc_file"]["name"]));
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["doc_file"]["tmp_name"], $target_file)) {
            // Check for duplicate document number
            $check_stmt = $conn->prepare("SELECT id FROM documents WHERE doc_number = ?");
            $check_stmt->bind_param("s", $number);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $error_message = "ලේඛන අංකය [$number] දැනටමත් පවතී / Document number [$number] is already registered.";
                if (file_exists($target_file)) unlink($target_file);
            } else {
                $stmt = $conn->prepare("INSERT INTO documents (title, doc_date, doc_number, file_path, category_id, branch_id) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssii", $title, $date, $number, $target_file, $cat_id, $branch_input);
                
                if($stmt->execute()) {
                    $stmt_cat = $conn->prepare("SELECT category_name FROM categories WHERE id = ?");
                    $stmt_cat->bind_param("i", $cat_id);
                    $stmt_cat->execute();
                    $cat_data = $stmt_cat->get_result()->fetch_assoc();
                    $cat_name = $cat_data['category_name'] ?? 'General';

                    $stmt_br = $conn->prepare("SELECT branch_name FROM branches WHERE id = ?");
                    $stmt_br->bind_param("i", $branch_input);
                    $stmt_br->execute();
                    $br_data = $stmt_br->get_result()->fetch_assoc();
                    $branch_name = $br_data['branch_name'] ?? 'Universal Showrooms';

                    $show_sms_prompt = true;
                    $sms_params = [
                        'title' => $title, 'cat_name' => $cat_name, 'cat_id' => $cat_id,
                        'branch_name' => $branch_name, 'branch_id' => $branch_input 
                    ];
                    $_SESSION['toast'] = ['type' => 'success', 'title' => 'ලේඛනය එක් කරන ලදී', 'message' => "Document added successfully!"];
                } else {
                    $error_message = "දත්ත සමුදා දෝෂයක් / Database Error: " . $conn->error;
                    if (file_exists($target_file)) unlink($target_file);
                }
                $stmt->close();
            }
            $check_stmt->close();
        } else {
            $error_message = "ගොනු පද්ධති දෝෂයක් / File System Error: Could not write file to disk.";
        }
    }
}

// Delete Logic with Foreign Key Check
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Check if document has interactions (foreign key reference)
    $check_interactions = $conn->prepare("SELECT COUNT(*) as count FROM document_interactions WHERE doc_id = ?");
    $check_interactions->bind_param("i", $id);
    $check_interactions->execute();
    $interaction_result = $check_interactions->get_result();
    $interaction_count = $interaction_result->fetch_assoc()['count'];
    $check_interactions->close();
    
    if ($interaction_count > 0) {
        $_SESSION['toast'] = [
            'type' => 'error', 
            'title' => 'මකා දැමිය නොහැක', 
            'message' => "මෙම ලේඛනයට අන්තර්ක්‍රියා {$interaction_count}ක් ඇත. පළමුව ඒවා ඉවත් කරන්න / This document has {$interaction_count} interaction(s). Please remove them first."
        ];
    } else {
        $res = $conn->query("SELECT file_path FROM documents WHERE id = $id");
        if ($res && $row = $res->fetch_assoc()) {
            if (!empty($row['file_path']) && file_exists($row['file_path'])) unlink($row['file_path']);
        }
        $conn->query("DELETE FROM documents WHERE id = $id");
        $_SESSION['toast'] = ['type' => 'success', 'title' => 'ලේඛනය මකා දමන ලදී', 'message' => "Document deleted successfully!"];
    }
    header("Location: document_mgmt.php");
    exit();
}

// Fetch lists
$docs = $conn->query("SELECT d.*, c.category_name, b.branch_name, 
                      (SELECT COUNT(*) FROM document_interactions WHERE doc_id = d.id) as interaction_count 
                      FROM documents d 
                      LEFT JOIN categories c ON d.category_id = c.id 
                      LEFT JOIN branches b ON d.branch_id = b.id 
                      ORDER BY d.id DESC");
$cats = $conn->query("SELECT id, category_name FROM categories");
$brs = $conn->query("SELECT id, branch_name FROM branches");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registry Terminal | ASB Group</title>
    <link rel="icon" type="image/png" href="logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Sinhala:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/document-add.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1 class="sidebar-title">ASB <span class="accent-text">GROUP</span></h1>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-link">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                    <span>Dashboard</span>
                </a>
                <a href="document_mgmt.php" class="nav-link active">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/>
                        <polyline points="13 2 13 9 20 9"/>
                    </svg>
                    <span>Document Master</span>
                </a>
                <a href="user_mgmt.php" class="nav-link">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                        <path d="M17 3.5a4 4 0 0 1 0 7"/>
                    </svg>
                    <span>Users</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <div>
                    <h2 class="page-title">Document <span class="accent-text">Vault</span></h2>
                    <p class="page-subtitle sinhala-text">ලේඛන ගබඩාව | Binary Archive & Security</p>
                </div>
                <button id="openModalBtn" class="btn-primary">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    නව ලේඛනයක් / Upload New Archive
                </button>
            </header>

            <!-- FILTER BAR -->
            <div class="filters-bar">
                <div class="search-wrapper">
                    <div class="search-icon">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                    </div>
                    <input type="text" id="searchInput" placeholder="මාතෘකාවෙන් හෝ අංකයෙන් සොයන්න / Search by Title or Ref Number..." 
                        class="search-input">
                </div>
                <select id="catFilter" class="filter-select">
                    <option value="">සියලුම ප්‍රවර්ග / All Categories</option>
                    <?php if($cats && $cats->num_rows > 0) { 
                        $cats->data_seek(0); 
                        while($c = $cats->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($c['category_name']); ?>"><?php echo htmlspecialchars($c['category_name']); ?></option>
                    <?php endwhile; } ?>
                </select>
                <select id="branchFilter" class="filter-select">
                    <option value="">සියලුම ශාඛා / All Branches</option>
                    <?php if($brs && $brs->num_rows > 0) { 
                        $brs->data_seek(0); 
                        while($b = $brs->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($b['branch_name']); ?>"><?php echo htmlspecialchars($b['branch_name']); ?></option>
                    <?php endwhile; } ?>
                </select>
            </div>

            <!-- Document List Container -->
            <div id="docContainer" class="documents-grid">
                <?php if($docs && $docs->num_rows > 0): while($row = $docs->fetch_assoc()): 
                    $has_interactions = $row['interaction_count'] > 0;
                ?>
                    <div class="doc-card <?php echo $has_interactions ? 'has-interactions' : ''; ?>"
                         data-title="<?php echo strtolower(htmlspecialchars($row['title'])); ?>"
                         data-number="<?php echo strtolower(htmlspecialchars($row['doc_number'])); ?>"
                         data-category="<?php echo htmlspecialchars($row['category_name']); ?>"
                         data-branch="<?php echo htmlspecialchars($row['branch_name']); ?>">
                        
                        <div class="doc-icon">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/>
                                <polyline points="13 2 13 9 20 9"/>
                            </svg>
                        </div>
                        
                        <div class="doc-info">
                            <h4 class="doc-title sinhala-text"><?php echo htmlspecialchars($row['title']); ?></h4>
                            <div class="doc-meta">
                                <span class="meta-badge">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="4" y1="9" x2="20" y2="9"/>
                                        <line x1="4" y1="15" x2="20" y2="15"/>
                                        <line x1="10" y1="3" x2="8" y2="21"/>
                                        <line x1="14" y1="3" x2="12" y2="21"/>
                                    </svg>
                                    <?php echo htmlspecialchars($row['doc_number']); ?>
                                </span>
                                <span class="meta-badge">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                        <line x1="16" y1="2" x2="16" y2="6"/>
                                        <line x1="8" y1="2" x2="8" y2="6"/>
                                        <line x1="3" y1="10" x2="21" y2="10"/>
                                    </svg>
                                    <?php echo htmlspecialchars($row['doc_date']); ?>
                                </span>
                                <span class="meta-badge category">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z"/>
                                    </svg>
                                    <?php echo htmlspecialchars($row['category_name']); ?>
                                </span>
                                <span class="meta-badge branch">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                                    </svg>
                                    <?php echo htmlspecialchars($row['branch_name']); ?>
                                </span>
                                <?php if($has_interactions): ?>
                                <span class="interaction-badge">
                                    📊 <?php echo $row['interaction_count']; ?> අන්තර්ක්‍රියා
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="doc-actions">
                            <a href="<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank" class="action-btn view-btn">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                නරඹන්න
                            </a>
                            <button onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo addslashes($row['title']); ?>', <?php echo $row['interaction_count']; ?>)" class="action-btn delete-btn">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <polyline points="3 6 5 6 21 6"/>
                                    <path d="M8 6V4h8v2"/>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
                                    <line x1="10" y1="11" x2="10" y2="17"/>
                                    <line x1="14" y1="11" x2="14" y2="17"/>
                                </svg>
                                මකන්න
                            </button>
                        </div>
                    </div>
                <?php endwhile; endif; ?>
                
                <div id="noResults" class="no-results hidden">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <p>ගැලපෙන ලේඛන නොමැත / No documents match your filters</p>
                </div>
            </div>
            
            <!-- Info Section -->
            <div class="info-section">
                <div class="info-card">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <div>
                        <strong>තොරතුරු / Information</strong>
                        <p class="sinhala-text">ලේඛන අංක අනන්‍ය විය යුතුය. අන්තර්ක්‍රියා ඇති ලේඛන මකා දැමිය නොහැක.</p>
                        <p>Document numbers must be unique. Documents with interactions cannot be deleted.</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- UPLOAD MODAL -->
    <div id="uploadModal" class="modal hidden">
        <div class="modal-content animate-fadeInUp">
            <div class="modal-header">
                <h3 class="modal-title">Archive <span class="accent-text">Initialization</span></h3>
                <button onclick="closeModal()" class="modal-close">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="modal-form">
                <div class="form-row">
                    <div class="form-group full-width">
                        <label class="form-label">ලේඛන මාතෘකාව / Title</label>
                        <input type="text" name="title" placeholder="Document Title" required class="form-input sinhala-text">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">යොමු අංකය / Ref Number</label>
                        <input type="text" name="doc_number" placeholder="Document Number" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">දිනය / Date</label>
                        <input type="date" name="doc_date" required class="form-input">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">ප්‍රවර්ගය / Category</label>
                        <select name="category_id" required class="form-select">
                            <option value="">තෝරන්න / Select Category</option>
                            <?php if($cats && $cats->num_rows > 0) { 
                                $cats->data_seek(0); 
                                while($c = $cats->fetch_assoc()): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['category_name']); ?></option>
                            <?php endwhile; } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">ශාඛාව / Branch</label>
                        <select name="branch_id" required class="form-select">
                            <option value="">තෝරන්න / Select Branch</option>
                            <?php if($brs && $brs->num_rows > 0) { 
                                $brs->data_seek(0); 
                                while($b = $brs->fetch_assoc()): ?>
                                    <option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['branch_name']); ?></option>
                            <?php endwhile; } ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group full-width">
                    <label class="form-label">PDF ගොනුව / PDF File</label>
                    <div id="dropZone" class="drop-zone">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="12" y1="18" x2="12" y2="12"/>
                            <line x1="9" y1="15" x2="15" y2="15"/>
                        </svg>
                        <p class="drop-text">PDF ගොනුවක් තෝරන්න / Select PDF Archive</p>
                        <p id="fileName" class="file-name"></p>
                    </div>
                    <input type="file" name="doc_file" id="fileInput" accept=".pdf" class="hidden-input" required>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" name="add_doc" class="btn-submit">ගබඩාවට එක් කරන්න / Commit to Vault</button>
                    <button type="button" class="btn-cancel" onclick="closeModal()">අවලංගු කරන්න / Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div id="toastContainer" class="toast-container"></div>

    <script>
        // Modal Functions
        const modal = document.getElementById('uploadModal');
        
        function openModal() {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        
        function closeModal() { 
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
        
        document.getElementById('openModalBtn').onclick = openModal;
        
        // File Upload Drop Zone
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const fileName = document.getElementById('fileName');
        
        dropZone.onclick = () => fileInput.click();
        
        dropZone.ondragover = (e) => {
            e.preventDefault();
            dropZone.classList.add('drag-over');
        };
        
        dropZone.ondragleave = () => {
            dropZone.classList.remove('drag-over');
        };
        
        dropZone.ondrop = (e) => {
            e.preventDefault();
            dropZone.classList.remove('drag-over');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                if (files[0]) fileName.textContent = files[0].name;
            }
        };
        
        fileInput.onchange = () => { 
            if(fileInput.files.length) {
                fileName.textContent = fileInput.files[0].name;
                fileName.classList.add('has-file');
            }
        };
        
        // Filter Functions
        const searchInput = document.getElementById('searchInput');
        const catFilter = document.getElementById('catFilter');
        const branchFilter = document.getElementById('branchFilter');
        const docCards = document.querySelectorAll('.doc-card');
        const noResults = document.getElementById('noResults');
        
        function performFilter() {
            const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
            const selectedCat = catFilter ? catFilter.value : '';
            const selectedBranch = branchFilter ? branchFilter.value : '';
            let visibleCount = 0;
            
            docCards.forEach(card => {
                const title = card.getAttribute('data-title') || '';
                const number = card.getAttribute('data-number') || '';
                const category = card.getAttribute('data-category') || '';
                const branch = card.getAttribute('data-branch') || '';
                
                const matchesSearch = title.includes(searchTerm) || number.includes(searchTerm);
                const matchesCat = selectedCat === "" || category === selectedCat;
                const matchesBranch = selectedBranch === "" || branch === selectedBranch;
                
                if (matchesSearch && matchesCat && matchesBranch) {
                    card.style.display = 'flex';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            if (noResults) {
                noResults.classList.toggle('hidden', visibleCount > 0);
            }
        }
        
        if (searchInput) searchInput.addEventListener('keyup', performFilter);
        if (catFilter) catFilter.addEventListener('change', performFilter);
        if (branchFilter) branchFilter.addEventListener('change', performFilter);
        
        // Toast Notification System
        function showToast(type, title, message) {
            const container = document.getElementById('toastContainer');
            if (!container) return;
            
            const toast = document.createElement('div');
            toast.className = `toast toast-${type} animate-slideIn`;
            
            const iconPath = type === 'success' ? 
                '<path d="M20 6L9 17l-5-5"/>' :
                '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>';
            
            toast.innerHTML = `
                <div class="toast-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">${iconPath}</svg>
                </div>
                <div class="toast-content">
                    <div class="toast-title">${title}</div>
                    <div class="toast-message">${message}</div>
                </div>
                <button class="toast-close" onclick="this.parentElement.remove()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            `;
            
            container.appendChild(toast);
            
            setTimeout(() => {
                if (toast.parentElement) toast.remove();
            }, 5000);
        }
        
        // Custom Delete Confirmation with Foreign Key Check
        function confirmDelete(id, title, interactionCount) {
            const hasInteractions = interactionCount > 0;
            const overlay = document.createElement('div');
            overlay.className = 'custom-confirm-overlay';
            
            let dialog;
            
            if (hasInteractions) {
                dialog = document.createElement('div');
                dialog.className = 'custom-confirm-dialog animate-fadeIn';
                dialog.innerHTML = `
                    <div class="confirm-icon warning-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                    </div>
                    <h3 class="confirm-title">මකා දැමිය නොහැක!</h3>
                    <p class="confirm-text">"<strong>${title}</strong>" ලේඛනයට <strong>${interactionCount}</strong> අන්තර්ක්‍රියා(ක්) ඇත.</p>
                    <div class="solution-box">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 8v4l3 3M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/>
                        </svg>
                        <span>පළමුව අන්තර්ක්‍රියා ඉවත් කරන්න / Remove interactions before deleting.</span>
                    </div>
                    <div class="confirm-actions">
                        <button class="confirm-btn confirm-ok">හරි / OK</button>
                    </div>
                `;
                dialog.querySelector('.confirm-ok').onclick = () => overlay.remove();
            } else {
                dialog = document.createElement('div');
                dialog.className = 'custom-confirm-dialog animate-fadeIn';
                dialog.innerHTML = `
                    <div class="confirm-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M12 8v4M12 16h.01"/>
                            <circle cx="12" cy="12" r="10"/>
                        </svg>
                    </div>
                    <h3 class="confirm-title">ලේඛනය මකා දමන්න?</h3>
                    <p class="confirm-text">"<strong>${title}</strong>" ලේඛනය ස්ථිරවම මකා දමනු ඇත.</p>
                    <p class="confirm-subtext">This action cannot be undone.</p>
                    <div class="confirm-actions">
                        <button class="confirm-btn confirm-yes" data-id="${id}">ඔව්, මකන්න</button>
                        <button class="confirm-btn confirm-no">නැත, අවලංගු කරන්න</button>
                    </div>
                `;
                dialog.querySelector('.confirm-yes').onclick = () => {
                    window.location.href = `document_mgmt.php?delete=${id}`;
                };
                dialog.querySelector('.confirm-no').onclick = () => overlay.remove();
            }
            
            overlay.appendChild(dialog);
            document.body.appendChild(overlay);
            
            overlay.onclick = (e) => {
                if (e.target === overlay) overlay.remove();
            };
        }
        
        // Close modal when clicking outside
        window.onclick = (event) => {
            if (event.target === modal) closeModal();
        }
        
        // PHP Session Toast Trigger
        <?php if(isset($_SESSION['toast'])): ?>
            showToast('<?php echo $_SESSION['toast']['type']; ?>', '<?php echo $_SESSION['toast']['title']; ?>', '<?php echo addslashes($_SESSION['toast']['message']); ?>');
            <?php unset($_SESSION['toast']); ?>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
        showToast('error', 'දෝෂයක් / Error', "<?php echo addslashes($error_message); ?>");
        <?php endif; ?>
        
        <?php if ($show_sms_prompt): ?>
        const params = <?php echo json_encode($sms_params); ?>;
        const smsOverlay = document.createElement('div');
        smsOverlay.className = 'custom-confirm-overlay';
        smsOverlay.innerHTML = `
            <div class="custom-confirm-dialog animate-fadeIn">
                <div class="confirm-icon success-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 6L9 17l-5-5"/>
                    </svg>
                </div>
                <h3 class="confirm-title">ලේඛනය එක් කරන ලදී!</h3>
                <p class="confirm-text">"<strong><?php echo addslashes($sms_params['title']); ?></strong>" සාර්ථකව එක් කරන ලදී.</p>
                <p class="confirm-subtext">දැනුම්දීමේ SMS එකක් යැවීමට අවශ්‍යද?</p>
                <div class="confirm-actions">
                    <button class="confirm-btn confirm-yes" id="sendSmsBtn">ඔව්, යවන්න</button>
                    <button class="confirm-btn confirm-no" id="cancelSmsBtn">නැත, අවසන් කරන්න</button>
                </div>
            </div>
        `;
        document.body.appendChild(smsOverlay);
        
        document.getElementById('sendSmsBtn').onclick = () => {
            window.location.href = "send_notification.php?" + new URLSearchParams(params).toString();
        };
        document.getElementById('cancelSmsBtn').onclick = () => {
            window.location.href = "document_mgmt.php";
        };
        <?php endif; ?>
    </script>
</body>
</html>