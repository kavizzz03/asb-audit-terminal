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

// --- CRUD LOGIC ---

// 1. Create User
if (isset($_POST['add_user'])) {
    $uname = trim($_POST['username']);
    $name = trim($_POST['name']);
    $pass = $_POST['password']; 
    $phone = $_POST['contact_number'];
    $email = trim($_POST['email']);
    $role = $_POST['role_id'];
    $branch = $_POST['branch_id'];

    // Check if username already exists
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check_stmt->bind_param("s", $uname);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $_SESSION['toast'] = ['type' => 'error', 'title' => 'Username Exists', 'message' => "Username '{$uname}' already exists. Please choose a different username."];
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, name, password, contact_number, email, role_id, branch_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssii", $uname, $name, $pass, $phone, $email, $role, $branch);
        
        if($stmt->execute()) {
            $_SESSION['toast'] = ['type' => 'success', 'title' => 'User Created', 'message' => "User Provisioned Successfully"];
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'title' => 'Error', 'message' => "Database error: " . $stmt->error];
        }
        $stmt->close();
    }
    $check_stmt->close();
    header("Location: user_mgmt.php");
    exit();
}

// 2. Update User
if (isset($_POST['update_user'])) {
    $id = $_POST['user_id'];
    if ($id != 1) {
        $uname = trim($_POST['username']);
        $name = trim($_POST['name']);
        $pass = $_POST['password'];
        $phone = $_POST['contact_number'];
        $email = trim($_POST['email']);
        $role = $_POST['role_id'];
        $branch = $_POST['branch_id'];

        // Check if username already exists for different user
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $check_stmt->bind_param("si", $uname, $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $_SESSION['toast'] = ['type' => 'error', 'title' => 'Username Exists', 'message' => "Username '{$uname}' is already taken by another user."];
        } else {
            $stmt = $conn->prepare("UPDATE users SET username=?, name=?, password=?, contact_number=?, email=?, role_id=?, branch_id=? WHERE id=?");
            $stmt->bind_param("sssssiii", $uname, $name, $pass, $phone, $email, $role, $branch, $id);
            
            if($stmt->execute()) {
                $_SESSION['toast'] = ['type' => 'success', 'title' => 'User Updated', 'message' => "User Modified Successfully"];
            } else {
                $_SESSION['toast'] = ['type' => 'error', 'title' => 'Error', 'message' => "Database error: " . $stmt->error];
            }
            $stmt->close();
        }
        $check_stmt->close();
    } else {
        $_SESSION['toast'] = ['type' => 'error', 'title' => 'Protected User', 'message' => "Cannot modify the master administrator account."];
    }
    header("Location: user_mgmt.php");
    exit();
}

// 3. Delete User - Check for actual foreign key constraints
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']); 
    
    if ($id == 1) {
        $_SESSION['toast'] = ['type' => 'error', 'title' => 'Protected User', 'message' => "Cannot delete the master administrator account."];
        header("Location: user_mgmt.php");
        exit();
    }
    
    // Check for data in related tables (even if not foreign keys, maintain data integrity)
    $constraints = [];
    
    // Check document_interactions table (has user_id column)
    $check_interactions = $conn->prepare("SELECT COUNT(*) as count FROM document_interactions WHERE user_id = ?");
    $check_interactions->bind_param("i", $id);
    $check_interactions->execute();
    $interaction_result = $check_interactions->get_result();
    if ($interaction_result) {
        $interaction_count = $interaction_result->fetch_assoc()['count'];
        if ($interaction_count > 0) {
            $constraints[] = "{$interaction_count} document interaction(s) are linked to this user";
        }
    }
    $check_interactions->close();
    
    // Check if user has any documents (as creator - if created_by column exists)
    // Note: Your documents table doesn't have created_by, so skip this check
    
    // Also check if user is referenced in any other way
    // Since there are no actual FOREIGN KEY constraints on user_id in other tables,
    // we can delete users even if they have interactions (but we'll warn)
    
    if (count($constraints) > 0) {
        // Show warning but allow deletion? Or block? Let's show warning and ask for confirmation
        $_SESSION['pending_delete'] = ['id' => $id, 'constraints' => $constraints];
        $_SESSION['toast'] = [
            'type' => 'warning',
            'title' => 'Warning: User Has Activity',
            'message' => "User has " . $interaction_count . " interaction(s). Deleting will remove this user reference."
        ];
        header("Location: user_mgmt.php?confirm=1&delete_id=" . $id);
        exit();
    } else {
        // Safe to delete
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        if($stmt->execute()) {
            $_SESSION['toast'] = ['type' => 'success', 'title' => 'User Deleted', 'message' => "User Terminated Successfully"];
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'title' => 'Deletion Failed', 'message' => "Database error: " . $stmt->error];
        }
        $stmt->close();
    }
    header("Location: user_mgmt.php");
    exit();
}

// Handle forced deletion with interactions
if (isset($_GET['confirm']) && $_GET['confirm'] == 1 && isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    
    if ($id != 1) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        if($stmt->execute()) {
            $_SESSION['toast'] = ['type' => 'success', 'title' => 'User Deleted', 'message' => "User deleted successfully. Note: Interaction history remains in logs."];
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'title' => 'Deletion Failed', 'message' => "Database error: " . $stmt->error];
        }
        $stmt->close();
        unset($_SESSION['pending_delete']);
    }
    header("Location: user_mgmt.php");
    exit();
}

// Fetch users with counts
$users_query = "SELECT u.*, r.role_name, b.branch_name,
                (SELECT COUNT(*) FROM document_interactions WHERE user_id = u.id) as interaction_count
                FROM users u 
                LEFT JOIN roles r ON u.role_id = r.id 
                LEFT JOIN branches b ON u.branch_id = b.id
                ORDER BY u.id ASC";
$users = $conn->query($users_query);

$roles_list = $conn->query("SELECT id, role_name FROM roles");
$branches_list = $conn->query("SELECT id, branch_name FROM branches");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ASB | User Control</title>
    <link rel="icon" type="image/png" href="logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Sinhala:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/user-styles.css">
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
                <a href="user_mgmt.php" class="nav-link active">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                        <path d="M17 3.5a4 4 0 0 1 0 7"/>
                    </svg>
                    <span>User Management</span>
                </a>
                <a href="branch_mgmt.php" class="nav-link">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                    </svg>
                    <span>Branch Network</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <div>
                    <h2 class="page-title">Operator <span class="accent-text">Registry</span></h2>
                    <p class="page-subtitle sinhala-text">පරිශීලක කළමනාකරණය | Access Control & Identity Management</p>
                </div>
                <button id="openAddModalBtn" class="btn-primary">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    නව පරිශීලක
                </button>
            </header>

            <!-- User Table -->
            <div class="table-container animate-fadeIn">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>User Profile</th>
                            <th>Contact & ID</th>
                            <th>Authorization</th>
                            <th>Activity</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($users && $users->num_rows > 0): ?>
                            <?php while($row = $users->fetch_assoc()): 
                                $has_activity = ($row['interaction_count'] > 0) && $row['id'] != 1;
                            ?>
                            <tr class="<?php echo ($row['id'] == 1) ? 'master-row' : ''; ?> <?php echo $has_activity ? 'has-activity' : ''; ?>">
                                <tr>
                                    <div class="user-cell">
                                        <div class="user-avatar">
                                            <?php echo strtoupper(substr($row['username'] ?? 'U', 0, 1)); ?>
                                        </div>
                                        <div>
                                            <p class="user-name sinhala-text"><?php echo htmlspecialchars($row['name']); ?></p>
                                            <p class="user-username">@<?php echo htmlspecialchars($row['username']); ?></p>
                                        </div>
                                    </div>
                                 </td>
                                <td>
                                    <p class="user-email"><?php echo htmlspecialchars($row['email']); ?></p>
                                    <p class="user-phone"><?php echo htmlspecialchars($row['contact_number']); ?></p>
                                 </td>
                                <td>
                                    <span class="role-badge"><?php echo htmlspecialchars($row['role_name']); ?></span>
                                    <span class="branch-badge"><?php echo htmlspecialchars($row['branch_name']); ?></span>
                                 </td>
                                <td class="activity-cell">
                                    <?php if($row['id'] != 1): ?>
                                        <div class="activity-stats">
                                            <span class="activity-badge <?php echo $row['interaction_count'] > 0 ? 'has-data' : 'no-data'; ?>">
                                                📄 <?php echo $row['interaction_count']; ?> interaction(s)
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <span class="protected-label">System Protected</span>
                                    <?php endif; ?>
                                 </td>
                                <td class="text-right">
                                    <?php if($row['id'] != 1): ?>
                                        <button class="action-btn edit-btn" onclick='openEditModal(<?php echo json_encode($row); ?>)'>
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <path d="M17 3l4 4-7 7H10v-4l7-7z"/>
                                                <path d="M3 21h18"/>
                                            </svg>
                                        </button>
                                        <button class="action-btn delete-btn" onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo addslashes($row['name']); ?>', <?php echo $row['interaction_count']; ?>)">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <polyline points="3 6 5 6 21 6"/>
                                                <path d="M8 6V4h8v2"/>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
                                                <line x1="10" y1="11" x2="10" y2="17"/>
                                                <line x1="14" y1="11" x2="14" y2="17"/>
                                            </svg>
                                        </button>
                                    <?php else: ?>
                                        <span class="locked-icon">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                            </svg>
                                        </span>
                                    <?php endif; ?>
                                 </td>
                             </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="empty-table">
                                    <div class="empty-content">
                                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                            <circle cx="12" cy="7" r="4"/>
                                        </svg>
                                        <p>No users found. Create your first user!</p>
                                    </div>
                                 </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
                        <strong>Information / තොරතුරු</strong>
                        <p class="sinhala-text">Users with document interactions will show a warning before deletion. Deleting a user will remove them from the system but interaction logs will remain.</p>
                        <p>ද්‍රව්‍ය අන්තර්ක්‍රියා ඇති පරිශීලකයින් මකා දැමීමට පෙර අනතුරු ඇඟවීමක් පෙන්වයි.</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- USER MODAL -->
    <div id="userModal" class="modal hidden">
        <div class="modal-content animate-zoomIn">
            <div class="modal-header">
                <h3 id="modalTitle" class="modal-title">System <span class="accent-text">Provisioning</span></h3>
                <button onclick="closeModal()" class="modal-close">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            
            <form method="POST" class="modal-form">
                <input type="hidden" name="user_id" id="u_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Username / පරිශීලක නාමය</label>
                        <input type="text" name="username" id="u_username" required class="form-input" placeholder="Enter username">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Full Name / සම්පූර්ණ නම</label>
                        <input type="text" name="name" id="u_name" required class="form-input sinhala-text" placeholder="Enter full name">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Password / මුරපදය</label>
                        <input type="text" name="password" id="u_pass" required class="form-input" placeholder="Enter password">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contact / දුරකථන අංකය</label>
                        <input type="text" name="contact_number" id="u_phone" pattern="[0-9]{10,12}" placeholder="0712345678" required class="form-input">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Role / භූමිකාව</label>
                        <select name="role_id" id="u_role" class="form-select">
                            <?php 
                            if($roles_list && $roles_list->num_rows > 0) {
                                $roles_list->data_seek(0); 
                                while($r = $roles_list->fetch_assoc()): ?>
                                    <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['role_name']); ?></option>
                            <?php endwhile; } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Branch / ශාඛාව</label>
                        <select name="branch_id" id="u_branch" class="form-select">
                            <?php 
                            if($branches_list && $branches_list->num_rows > 0) {
                                $branches_list->data_seek(0); 
                                while($b = $branches_list->fetch_assoc()): ?>
                                    <option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['branch_name']); ?></option>
                            <?php endwhile; } ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group full-width">
                    <label class="form-label">Email Address / විද්‍යුත් තැපෑල</label>
                    <input type="email" name="email" id="u_email" required class="form-input" placeholder="user@example.com">
                </div>
                
                <div class="modal-actions">
                    <button type="submit" id="submitBtn" name="add_user" class="btn-submit">Execute / ක්‍රියාත්මක කරන්න</button>
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel / අවලංගු කරන්න</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div id="toastContainer" class="toast-container"></div>

    <script>
        // Modal Functions
        const modal = document.getElementById('userModal');
        
        function openAddModal() {
            document.getElementById('modalTitle').innerHTML = 'System <span class="accent-text">Provisioning</span>';
            document.getElementById('submitBtn').name = 'add_user';
            document.getElementById('u_id').value = '';
            document.querySelector('.modal-form').reset();
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        
        if(document.getElementById('openAddModalBtn')) {
            document.getElementById('openAddModalBtn').onclick = openAddModal;
        }
        
        function openEditModal(user) {
            document.getElementById('modalTitle').innerHTML = 'Modify <span class="accent-text">Identity</span>';
            document.getElementById('submitBtn').name = 'update_user';
            document.getElementById('u_id').value = user.id;
            document.getElementById('u_username').value = user.username;
            document.getElementById('u_name').value = user.name;
            document.getElementById('u_pass').value = user.password;
            document.getElementById('u_phone').value = user.contact_number;
            document.getElementById('u_email').value = user.email;
            document.getElementById('u_role').value = user.role_id;
            document.getElementById('u_branch').value = user.branch_id;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        
        function closeModal() { 
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
        
        // Toast Notification System
        function showToast(type, title, message) {
            const container = document.getElementById('toastContainer');
            if (!container) return;
            
            const toast = document.createElement('div');
            let iconColor = type === 'success' ? '#10b981' : (type === 'warning' ? '#f59e0b' : '#ef4444');
            let iconPath = type === 'success' ? 
                '<path d="M20 6L9 17l-5-5"/>' :
                (type === 'warning' ?
                '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>' :
                '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>');
            
            toast.className = `toast toast-${type} animate-slideIn`;
            toast.innerHTML = `
                <div class="toast-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">${iconPath}</svg>
                </div>
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
        
        // Custom Delete Confirmation
        function confirmDelete(id, name, interactionCount) {
            const hasActivity = (interactionCount > 0);
            const overlay = document.createElement('div');
            overlay.className = 'custom-confirm-overlay';
            
            let activityMessage = '';
            if (hasActivity) {
                activityMessage = `<div class="activity-item warning-box">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 8v4M12 16h.01"/>
                        <circle cx="12" cy="12" r="10"/>
                    </svg>
                    <span><strong>${interactionCount}</strong> document interaction(s) found for this user</span>
                </div>`;
            }
            
            let dialog = document.createElement('div');
            dialog.className = 'custom-confirm-dialog animate-fadeIn';
            
            if (hasActivity) {
                dialog.innerHTML = `
                    <div class="confirm-icon warning-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                    </div>
                    <h3 class="confirm-title">Warning: User Has Activity</h3>
                    <p class="confirm-text">User "<strong>${name}</strong>" has existing activity records:</p>
                    ${activityMessage}
                    <div class="solution-box">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 8v4l3 3M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/>
                        </svg>
                        <span>Deleting this user will remove them from the system. Interaction logs will remain in the database.</span>
                    </div>
                    <div class="confirm-actions">
                        <button class="confirm-btn confirm-yes" data-id="${id}">DELETE ANYWAY</button>
                        <button class="confirm-btn confirm-no">CANCEL</button>
                    </div>
                `;
                dialog.querySelector('.confirm-yes').onclick = () => {
                    window.location.href = `user_mgmt.php?confirm=1&delete_id=${id}`;
                };
                dialog.querySelector('.confirm-no').onclick = () => {
                    overlay.remove();
                };
            } else {
                dialog.innerHTML = `
                    <div class="confirm-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M12 8v4M12 16h.01"/>
                            <circle cx="12" cy="12" r="10"/>
                        </svg>
                    </div>
                    <h3 class="confirm-title">Delete User?</h3>
                    <p class="confirm-text">You are about to delete <strong>${name}</strong> from the system. This action is irreversible.</p>
                    <div class="confirm-actions">
                        <button class="confirm-btn confirm-yes" data-id="${id}">CONFIRM DELETE</button>
                        <button class="confirm-btn confirm-no">CANCEL</button>
                    </div>
                `;
                dialog.querySelector('.confirm-yes').onclick = () => {
                    window.location.href = `user_mgmt.php?delete=${id}`;
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