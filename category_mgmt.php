<?php
session_start();

// Security Check: Only Super Admins (Role 1)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: dashboard.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "asb_file_system");
$conn->set_charset("utf8mb4");
if ($conn->connect_error) { 
    die("Critical Connection Failure."); 
}

// --- CRUD OPERATIONS ---

// 1. Create / Update Category
if (isset($_POST['save_category'])) {
    $cat_name = trim($_POST['category_name']);
    $description = trim($_POST['description']);
    $cat_id = $_POST['cat_id'] ?? null;

    try {
        if (empty($cat_name)) {
            throw new Exception("Category name is required.");
        }

        if ($cat_id) {
            // Update existing category
            $stmt = $conn->prepare("UPDATE categories SET category_name = ?, description = ? WHERE id = ?");
            $stmt->bind_param("ssi", $cat_name, $description, $cat_id);
            $action = "updated";
        } else {
            // Create new category
            $stmt = $conn->prepare("INSERT INTO categories (category_name, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $cat_name, $description);
            $action = "created";
        }

        if ($stmt->execute()) {
            $_SESSION['toast'] = ['type' => 'success', 'title' => 'ප්‍රවර්ගය සුරැකිණි', 'message' => "Category successfully {$action}."];
        } else {
            throw new Exception("Database error: " . $stmt->error);
        }
        $stmt->close();
    } catch (Exception $e) {
        $_SESSION['toast'] = ['type' => 'error', 'title' => 'දෝෂයක්', 'message' => $e->getMessage()];
    }
    header("Location: category_mgmt.php");
    exit();
}

// 2. Delete Category with Foreign Key Check
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Check for foreign key constraints
    $constraints = [];
    
    // Check documents table
    $check_docs = $conn->prepare("SELECT COUNT(*) as count FROM documents WHERE category_id = ?");
    $check_docs->bind_param("i", $id);
    $check_docs->execute();
    $doc_count = $check_docs->get_result()->fetch_assoc()['count'];
    $check_docs->close();
    
    if ($doc_count > 0) {
        $constraints[] = "{$doc_count} document(s) are linked to this category / මෙම ප්‍රවර්ගයට ලේඛන {$doc_count}ක් සම්බන්ධ කර ඇත";
    }
    
    // Check role_category_access table
    $check_role_access = $conn->prepare("SELECT COUNT(*) as count FROM role_category_access WHERE category_id = ?");
    $check_role_access->bind_param("i", $id);
    $check_role_access->execute();
    $role_access_count = $check_role_access->get_result()->fetch_assoc()['count'];
    $check_role_access->close();
    
    if ($role_access_count > 0) {
        $constraints[] = "{$role_access_count} role permission(s) are assigned to this category / මෙම ප්‍රවර්ගයට භූමිකා {$role_access_count}ක් පවරා ඇත";
    }
    
    // If any constraints exist, show error
    if (count($constraints) > 0) {
        $error_message = "Cannot delete category due to foreign key constraints:\n• " . implode("\n• ", $constraints);
        $_SESSION['toast'] = [
            'type' => 'error', 
            'title' => 'විදේශීය යතුරු බාධාව', 
            'message' => $error_message
        ];
    } else {
        // Safe to delete
        try {
            $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['toast'] = [
                    'type' => 'success', 
                    'title' => 'ප්‍රවර්ගය මකා දමන ලදී', 
                    'message' => "Category has been successfully removed from the architecture."
                ];
            } else {
                throw new Exception("Delete operation failed.");
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $_SESSION['toast'] = [
                'type' => 'error', 
                'title' => 'මකාදැමීම අසාර්ථකයි', 
                'message' => "Database constraint violation: " . $e->getMessage()
            ];
        }
    }
    header("Location: category_mgmt.php");
    exit();
}

// Fetch all categories with related counts
$cat_query = "SELECT c.*, 
              COUNT(DISTINCT d.id) as doc_count,
              COUNT(DISTINCT rca.role_id) as role_access_count
              FROM categories c 
              LEFT JOIN documents d ON c.id = d.category_id 
              LEFT JOIN role_category_access rca ON c.id = rca.category_id
              GROUP BY c.id 
              ORDER BY c.id DESC";
$categories = $conn->query($cat_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ASB | Category Management / ප්‍රවර්ග කළමනාකරණය</title>
    <link rel="icon" type="image/png" href="logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Sinhala:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/category-styles.css">
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
                    <span>Dashboard / මුල් පිටුව</span>
                </a>
                <a href="category_mgmt.php" class="nav-link active">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z"/>
                    </svg>
                    <span>Categories / ප්‍රවර්ග</span>
                </a>
                <a href="logout.php" class="nav-link logout-link">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    <span>Exit / ඉවත් වන්න</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <div>
                    <h2 class="page-title">Category <span class="accent-text">Architecture</span></h2>
                    <p class="page-subtitle sinhala-text">ප්‍රවර්ග කළමනාකරණය | Configure System Data Structure</p>
                </div>
                <button id="openModalBtn" class="btn-primary">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    නව ප්‍රවර්ගයක් / Initialize Category
                </button>
            </header>

            <!-- Categories Table -->
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>System ID / අංකය</th>
                            <th>Designation / නම</th>
                            <th>Description / විස්තරය</th>
                            <th>Documents / ලේඛන</th>
                            <th>Role Access / භූමිකා</th>
                            <th class="text-right">Operations / ක්‍රියා</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $categories->fetch_assoc()): 
                            $has_constraints = ($row['doc_count'] > 0 || $row['role_access_count'] > 0);
                        ?>
                        <tr class="table-row <?php echo $has_constraints ? 'has-constraints' : ''; ?>">
                            <td class="id-cell">#<?php echo str_pad($row['id'], 3, '0', STR_PAD_LEFT); ?></td>
                            <td class="category-name sinhala-text"><?php echo htmlspecialchars($row['category_name']); ?></td>
                            <td class="description-cell sinhala-text"><?php echo htmlspecialchars($row['description'] ?: '—'); ?></td>
                            <td class="constraint-cell">
                                <span class="constraint-badge <?php echo $row['doc_count'] > 0 ? 'danger' : 'success'; ?>">
                                    📄 <?php echo $row['doc_count']; ?> 
                                </span>
                            </td>
                            <td class="constraint-cell">
                                <span class="constraint-badge <?php echo $row['role_access_count'] > 0 ? 'warning' : 'success'; ?>">
                                    👥 <?php echo $row['role_access_count']; ?> 
                                </span>
                            </td>
                            <td class="text-right">
                                <button class="action-btn edit-btn" onclick='editCategory(<?php echo json_encode($row); ?>)' title="Edit / සංස්කරණය">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M17 3l4 4-7 7H10v-4l7-7z"/>
                                        <path d="M3 21h18"/>
                                    </svg>
                                </button>
                                <button class="action-btn delete-btn" onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo addslashes($row['category_name']); ?>', <?php echo $row['doc_count']; ?>, <?php echo $row['role_access_count']; ?>)" title="Delete / මකන්න">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <polyline points="3 6 5 6 21 6"/>
                                        <path d="M8 6V4h8v2"/>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
                                        <line x1="10" y1="11" x2="10" y2="17"/>
                                        <line x1="14" y1="11" x2="14" y2="17"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if($categories->num_rows == 0): ?>
                        <tr>
                            <td colspan="6" class="empty-table">
                                <div class="empty-content">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path d="M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z"/>
                                    </svg>
                                    <p>ප්‍රවර්ග කිසිවක් නොමැත / No categories found</p>
                                    <p class="empty-hint">ඔබගේ පළමු ප්‍රවර්ගය සාදන්න / Create your first category!</p>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Foreign Key Info Section -->
            <div class="info-section">
                <div class="info-card">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <div>
                        <strong>Foreign Key Constraints / විදේශීය යතුරු බාධා</strong>
                        <p class="sinhala-text">ලේඛන හෝ භූමිකා පැවරුම් සහිත ප්‍රවර්ග මකා දැමිය නොහැක. පළමුව ඒවා ඉවත් කරන්න.</p>
                        <p>Categories cannot be deleted if they have linked documents or role permissions. You must remove these dependencies first.</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- CRUD MODAL -->
    <div id="catModal" class="modal hidden">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle" class="modal-title">New <span class="accent-text">Category</span></h3>
                <button onclick="closeModal()" class="modal-close">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>

            <form method="POST" class="modal-form">
                <input type="hidden" name="cat_id" id="cat_id">
                <div class="form-group">
                    <label class="form-label">Category Name / ප්‍රවර්ගයේ නම</label>
                    <input type="text" name="category_name" id="category_name" required class="form-input sinhala-text" placeholder="e.g., Financial Directives / මූල්‍ය ලේඛන">
                </div>
                <div class="form-group">
                    <label class="form-label">Description Profile / විස්තරය</label>
                    <textarea name="description" id="description" rows="4" class="form-textarea sinhala-text" placeholder="මෙම ප්‍රවර්ගය පිළිබඳ විස්තරය / Detailed description of this category..."></textarea>
                </div>
                <button type="submit" name="save_category" class="btn-submit">සුරකින්න / Commit to Database</button>
            </form>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div id="toastContainer" class="toast-container"></div>

    <script>
        const modal = document.getElementById('catModal');
        
        // Open Modal for New Category
        document.getElementById('openModalBtn').onclick = () => {
            document.getElementById('modalTitle').innerHTML = 'New <span class="accent-text">Category</span> / <span class="accent-text">නව</span> ප්‍රවර්ගය';
            document.getElementById('cat_id').value = '';
            document.getElementById('category_name').value = '';
            document.getElementById('description').value = '';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        };
        
        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
        
        function editCategory(data) {
            document.getElementById('modalTitle').innerHTML = 'Edit <span class="accent-text">Category</span> / <span class="accent-text">සංස්කරණය</span>';
            document.getElementById('cat_id').value = data.id;
            document.getElementById('category_name').value = data.category_name;
            document.getElementById('description').value = data.description || '';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        
        // Custom Confirm Dialog for Delete with foreign key constraint details
        function confirmDelete(id, name, docCount, roleCount) {
            const hasConstraints = (docCount > 0 || roleCount > 0);
            const confirmOverlay = document.createElement('div');
            confirmOverlay.className = 'custom-confirm-overlay';
            
            let constraintsList = '';
            if (docCount > 0) {
                constraintsList += `<div class="constraint-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                    </svg>
                    <span><strong>${docCount}</strong> ලේඛන(ය) මෙම ප්‍රවර්ගයට සම්බන්ධ කර ඇත</span>
                </div>`;
            }
            
            if (roleCount > 0) {
                constraintsList += `<div class="constraint-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    <span><strong>${roleCount}</strong> භූමිකා(ව) මෙම ප්‍රවර්ගයට පවරා ඇත</span>
                </div>`;
            }
            
            const dialog = document.createElement('div');
            dialog.className = 'custom-confirm-dialog animate-fadeIn';
            
            if (hasConstraints) {
                dialog.innerHTML = `
                    <div class="confirm-icon warning-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                    </div>
                    <h3 class="confirm-title">විදේශීය යතුරු බාධාවක්</h3>
                    <p class="confirm-text">"<strong>${name}</strong>" ප්‍රවර්ගය මකා දැමිය නොහැක. පහත දෑ මේ සමඟ සම්බන්ධ වී ඇත:</p>
                    <div class="constraints-list">
                        ${constraintsList}
                    </div>
                    <div class="solution-box">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 8v4l3 3M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/>
                        </svg>
                        <span>විසඳුම: පළමුව මෙම සම්බන්ධතා ඉවත් කරන්න</span>
                    </div>
                    <div class="confirm-actions">
                        <button class="confirm-btn confirm-ok">තේරුම් ගත්තා / OK</button>
                    </div>
                `;
                dialog.querySelector('.confirm-ok').onclick = () => {
                    confirmOverlay.remove();
                };
            } else {
                dialog.innerHTML = `
                    <div class="confirm-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M12 8v4M12 16h.01"/>
                            <circle cx="12" cy="12" r="10"/>
                        </svg>
                    </div>
                    <h3 class="confirm-title">ප්‍රවර්ගය මකා දමන්න?</h3>
                    <p class="confirm-text">"<strong>${name}</strong>" ප්‍රවර්ගය ස්ථිරවම මකා දැමීමට ඔබ සූදානම්ද?</p>
                    <p class="confirm-subtext">This action cannot be undone.</p>
                    <div class="confirm-actions">
                        <button class="confirm-btn confirm-yes" data-id="${id}">ඔව්, මකන්න</button>
                        <button class="confirm-btn confirm-no">නැත, අවලංගු කරන්න</button>
                    </div>
                `;
                dialog.querySelector('.confirm-yes').onclick = () => {
                    window.location.href = `category_mgmt.php?delete=${id}`;
                };
                dialog.querySelector('.confirm-no').onclick = () => {
                    confirmOverlay.remove();
                };
            }
            
            confirmOverlay.appendChild(dialog);
            document.body.appendChild(confirmOverlay);
            
            confirmOverlay.onclick = (e) => {
                if (e.target === confirmOverlay) confirmOverlay.remove();
            };
        }
        
        // Toast Notification System
        function showToast(type, title, message) {
            const container = document.getElementById('toastContainer');
            if (!container) return;
            
            const toast = document.createElement('div');
            toast.className = `toast toast-${type} animate-slideIn`;
            
            const icon = type === 'success' ? 
                '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>' :
                '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
            
            toast.innerHTML = `
                <div class="toast-icon">${icon}</div>
                <div class="toast-content">
                    <div class="toast-title">${title}</div>
                    <div class="toast-message">${message.replace(/\n/g, '<br>')}</div>
                </div>
                <button class="toast-close" onclick="this.parentElement.remove()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            `;
            
            container.appendChild(toast);
            
            const duration = type === 'error' ? 8000 : 5000;
            setTimeout(() => {
                if (toast.parentElement) toast.remove();
            }, duration);
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
    </script>
</body>
</html>