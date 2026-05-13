<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: dashboard.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "asb_file_system");
$conn->set_charset("utf8mb4");
$feedback = ['type' => '', 'msg' => ''];

// CRUD LOGIC

// 1. Create Branch
if (isset($_POST['add_branch'])) {
    $name = $_POST['branch_name'];
    $code = $_POST['branch_code'];
    $stmt = $conn->prepare("INSERT INTO branches (branch_name, branch_code) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $code);
    if($stmt->execute()) {
        $_SESSION['toast'] = ['type' => 'success', 'title' => 'ශාඛාව ලියාපදිංචි කරන ලදී', 'message' => "Branch successfully registered."];
        header("Location: branch_mgmt.php");
        exit();
    }
}

// 2. Update Branch (Protecting ID 3)
if (isset($_POST['update_branch'])) {
    $id = $_POST['branch_id'];
    if ($id != 3) {
        $name = $_POST['branch_name'];
        $code = $_POST['branch_code'];
        $stmt = $conn->prepare("UPDATE branches SET branch_name = ?, branch_code = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $code, $id);
        if($stmt->execute()) {
            $_SESSION['toast'] = ['type' => 'success', 'title' => 'ශාඛාව යාවත්කාලීන කරන ලදී', 'message' => "Branch infrastructure updated."];
        }
    } else {
        $_SESSION['toast'] = ['type' => 'error', 'title' => 'ආරක්ෂිත ශාඛාව', 'message' => "Cannot update the system protected branch."];
    }
    header("Location: branch_mgmt.php");
    exit();
}

// 3. Delete Branch with Foreign Key Checks
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    
    // Check if branch is protected (ID 3)
    if ($id == 3) {
        $_SESSION['toast'] = [
            'type' => 'error', 
            'title' => 'ආරක්ෂිත ශාඛාව', 
            'message' => "Branch ID 3 is system protected and cannot be deleted."
        ];
        header("Location: branch_mgmt.php");
        exit();
    }
    
    // Check for foreign key constraints
    $constraints = [];
    
    // Check users table
    $check_users = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE branch_id = ?");
    $check_users->bind_param("i", $id);
    $check_users->execute();
    $user_count = $check_users->get_result()->fetch_assoc()['count'];
    $check_users->close();
    
    if ($user_count > 0) {
        $constraints[] = "{$user_count} පරිශීලක(යෝ) මෙම ශාඛාවට පවරා ඇත / {$user_count} user(s) are assigned to this branch";
    }
    
    // Check documents table
    $check_docs = $conn->prepare("SELECT COUNT(*) as count FROM documents WHERE branch_id = ?");
    $check_docs->bind_param("i", $id);
    $check_docs->execute();
    $doc_count = $check_docs->get_result()->fetch_assoc()['count'];
    $check_docs->close();
    
    if ($doc_count > 0) {
        $constraints[] = "{$doc_count} ලේඛන(ය) මෙම ශාඛාවට සම්බන්ධ කර ඇත / {$doc_count} document(s) are linked to this branch";
    }
    
    // If any constraints exist, show error
    if (count($constraints) > 0) {
        $error_message = "Cannot delete branch due to foreign key constraints:\n• " . implode("\n• ", $constraints);
        $_SESSION['toast'] = [
            'type' => 'error', 
            'title' => 'විදේශීය යතුරු බාධාව', 
            'message' => $error_message
        ];
    } else {
        // Safe to delete
        if($conn->query("DELETE FROM branches WHERE id = $id")) {
            $_SESSION['toast'] = [
                'type' => 'success', 
                'title' => 'ශාඛාව මකා දමන ලදී', 
                'message' => "Branch has been successfully purged from the network."
            ];
        } else {
            $_SESSION['toast'] = [
                'type' => 'error', 
                'title' => 'මකාදැමීම අසාර්ථකයි', 
                'message' => "Database error: " . $conn->error
            ];
        }
    }
    header("Location: branch_mgmt.php");
    exit();
}

// Fetch branches with dependency counts
$branch_query = "SELECT b.*, 
                COUNT(DISTINCT u.id) as user_count,
                COUNT(DISTINCT d.id) as doc_count
                FROM branches b 
                LEFT JOIN users u ON b.id = u.branch_id 
                LEFT JOIN documents d ON b.id = d.branch_id
                GROUP BY b.id 
                ORDER BY b.id ASC";
$branches = $conn->query($branch_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ASB | Branch Control / ශාඛා කළමනාකරණය</title>
    <link rel="icon" type="image/png" href="logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Sinhala:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/branch-styles.css">
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
                <a href="branch_mgmt.php" class="nav-link active">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                    </svg>
                    <span>Branch Management / ශාඛා</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <div>
                    <h2 class="page-title">Branch <span class="accent-text">Network</span></h2>
                    <p class="page-subtitle sinhala-text">ශාඛා කළමනාකරණය | Infrastructure Logic & Terminal Control</p>
                </div>
                <button id="openAddModal" class="btn-primary">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    නව ශාඛාවක් / Register New Branch
                </button>
            </header>

            <!-- Branch Table -->
            <div class="table-container animate-fadeIn">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID / අංකය</th>
                            <th>Branch Name / ශාඛාවේ නම</th>
                            <th>Branch Code / කේතය</th>
                            <th>Users / පරිශීලකයින්</th>
                            <th>Documents / ලේඛන</th>
                            <th class="text-right">Actions / ක්‍රියා</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $branches->fetch_assoc()): 
                            $has_constraints = ($row['user_count'] > 0 || $row['doc_count'] > 0);
                        ?>
                        <tr class="<?php echo ($row['id'] == 3) ? 'restricted-row' : ''; ?> <?php echo $has_constraints && $row['id'] != 3 ? 'has-constraints' : ''; ?>">
                            <td class="id-cell">#<?php echo str_pad($row['id'], 3, '0', STR_PAD_LEFT); ?></td>
                            <td>
                                <span class="branch-name sinhala-text"><?php echo htmlspecialchars($row['branch_name']); ?></span>
                                <?php if($row['id'] == 3): ?>
                                    <span class="protected-badge">System Protected / පද්ධති ආරක්ෂිත</span>
                                <?php endif; ?>
                            </td>
                            <td><code class="branch-code"><?php echo htmlspecialchars($row['branch_code']); ?></code></td>
                            <td class="constraint-cell">
                                <span class="constraint-badge <?php echo $row['user_count'] > 0 ? 'danger' : 'success'; ?>">
                                    👥 <?php echo $row['user_count']; ?> 
                                </span>
                            </td>
                            <td class="constraint-cell">
                                <span class="constraint-badge <?php echo $row['doc_count'] > 0 ? 'danger' : 'success'; ?>">
                                    📄 <?php echo $row['doc_count']; ?> 
                                </span>
                            </td>
                            <td class="text-right">
                                <?php if($row['id'] != 3): ?>
                                    <button class="action-btn edit-btn" onclick="editBranch(<?php echo $row['id']; ?>, '<?php echo addslashes($row['branch_name']); ?>', '<?php echo addslashes($row['branch_code']); ?>')" title="Edit / සංස්කරණය">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <path d="M17 3l4 4-7 7H10v-4l7-7z"/>
                                            <path d="M3 21h18"/>
                                        </svg>
                                    </button>
                                    <button class="action-btn delete-btn" onclick="confirmPurge(<?php echo $row['id']; ?>, '<?php echo addslashes($row['branch_name']); ?>', <?php echo $row['user_count']; ?>, <?php echo $row['doc_count']; ?>)" title="Delete / මකන්න">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <polyline points="3 6 5 6 21 6"/>
                                            <path d="M8 6V4h8v2"/>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
                                            <line x1="10" y1="11" x2="10" y2="17"/>
                                            <line x1="14" y1="11" x2="14" y2="17"/>
                                        </svg>
                                    </button>
                                <?php else: ?>
                                    <span class="locked-icon" title="Protected / ආරක්ෂිත">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                        </svg>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if($branches->num_rows == 0): ?>
                        <tr>
                            <td colspan="6" class="empty-table">
                                <div class="empty-content">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                                    </svg>
                                    <p>ශාඛා කිසිවක් නොමැත / No branches found</p>
                                    <p class="empty-hint">ඔබගේ පළමු ශාඛාව සාදන්න / Create your first branch!</p>
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
                        <p class="sinhala-text">පරිශීලකයින් හෝ ලේඛන සහිත ශාඛා මකා දැමිය නොහැක. පළමුව ඒවා ඉවත් කරන්න.</p>
                        <p>Branches cannot be deleted if they have linked users or documents. You must reassign or remove these dependencies first.</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- ADD MODAL -->
    <div id="addModal" class="modal hidden">
        <div class="modal-content animate-zoomIn">
            <h3 class="modal-title">New <span class="accent-text">Registry</span> / <span class="accent-text">නව</span> ශාඛාව</h3>
            <form method="POST" class="modal-form">
                <div class="form-group">
                    <label class="form-label">Branch Designation / ශාඛාවේ නම</label>
                    <input type="text" name="branch_name" required class="form-input sinhala-text" placeholder="e.g., Head Office / ප්‍රධාන කාර්යාලය">
                </div>
                <div class="form-group">
                    <label class="form-label">Branch Code / ශාඛා කේතය</label>
                    <input type="text" name="branch_code" required class="form-input" placeholder="e.g., H001">
                </div>
                <div class="modal-actions">
                    <button type="submit" name="add_branch" class="btn-submit">අනුමත කරන්න / Authorize Branch</button>
                    <button type="button" class="btn-cancel" onclick="closeModal('addModal')">අවලංගු කරන්න / Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT MODAL -->
    <div id="editModal" class="modal hidden">
        <div class="modal-content animate-zoomIn">
            <h3 class="modal-title">Modify <span class="accent-text">Branch</span> / <span class="accent-text">සංස්කරණය</span></h3>
            <form method="POST" class="modal-form">
                <input type="hidden" name="branch_id" id="edit_id">
                <div class="form-group">
                    <label class="form-label">Designation / ශාඛාවේ නම</label>
                    <input type="text" name="branch_name" id="edit_name" required class="form-input sinhala-text">
                </div>
                <div class="form-group">
                    <label class="form-label">Code / කේතය</label>
                    <input type="text" name="branch_code" id="edit_code" required class="form-input">
                </div>
                <div class="modal-actions">
                    <button type="submit" name="update_branch" class="btn-submit">සුරකින්න / Commit Changes</button>
                    <button type="button" class="btn-cancel" onclick="closeModal('editModal')">අවලංගු කරන්න / Abort</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div id="toastContainer" class="toast-container"></div>

    <script>
        // Modal Functions
        const addModal = document.getElementById('addModal');
        const editModal = document.getElementById('editModal');
        
        document.getElementById('openAddModal').onclick = () => {
            addModal.classList.remove('hidden');
            addModal.classList.add('flex');
        };
        
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
        
        function editBranch(id, name, code) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_code').value = code;
            editModal.classList.remove('hidden');
            editModal.classList.add('flex');
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
        
        // Custom Confirm Dialog with Foreign Key Constraint Details
        function confirmPurge(id, name, userCount, docCount) {
            const hasConstraints = (userCount > 0 || docCount > 0);
            const overlay = document.createElement('div');
            overlay.className = 'custom-confirm-overlay';
            
            let constraintsList = '';
            if (userCount > 0) {
                constraintsList += `<div class="constraint-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    <span><strong>${userCount}</strong> පරිශීලක(යෝ) මෙම ශාඛාවට පවරා ඇත</span>
                </div>`;
            }
            
            if (docCount > 0) {
                constraintsList += `<div class="constraint-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                    </svg>
                    <span><strong>${docCount}</strong> ලේඛන(ය) මෙම ශාඛාවට සම්බන්ධ කර ඇත</span>
                </div>`;
            }
            
            let dialog;
            
            if (hasConstraints) {
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
                    <h3 class="confirm-title">විදේශීය යතුරු බාධාවක්</h3>
                    <p class="confirm-text">"<strong>${name}</strong>" ශාඛාව මකා දැමිය නොහැක. පහත දෑ මේ සමඟ සම්බන්ධ වී ඇත:</p>
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
                    overlay.remove();
                };
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
                    <h3 class="confirm-title">ශාඛාව මකා දමන්න?</h3>
                    <p class="confirm-text">"<strong>${name}</strong>" ශාඛාව ස්ථිරවම මකා දැමීමට ඔබ සූදානම්ද?</p>
                    <p class="confirm-subtext">This action cannot be undone.</p>
                    <div class="confirm-actions">
                        <button class="confirm-btn confirm-yes" data-id="${id}">ඔව්, මකන්න</button>
                        <button class="confirm-btn confirm-no">නැත, අවලංගු කරන්න</button>
                    </div>
                `;
                dialog.querySelector('.confirm-yes').onclick = () => {
                    window.location.href = "branch_mgmt.php?delete_id=" + id;
                };
                dialog.querySelector('.confirm-no').onclick = () => {
                    overlay.remove();
                };
            }
            
            overlay.appendChild(dialog);
            document.body.appendChild(overlay);
            
            overlay.onclick = (e) => {
                if (e.target === overlay) overlay.remove();
            };
        }
        
        // Close modals when clicking outside
        window.onclick = (event) => {
            if (event.target === addModal) closeModal('addModal');
            if (event.target === editModal) closeModal('editModal');
        }
        
        // PHP Session Toast Trigger
        <?php if(isset($_SESSION['toast'])): ?>
            showToast('<?php echo $_SESSION['toast']['type']; ?>', '<?php echo $_SESSION['toast']['title']; ?>', '<?php echo addslashes($_SESSION['toast']['message']); ?>');
            <?php unset($_SESSION['toast']); ?>
        <?php endif; ?>
    </script>
</body>
</html>