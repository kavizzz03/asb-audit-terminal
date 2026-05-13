<?php
session_start();
// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: dashboard.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "asb_file_system");
$conn->set_charset("utf8mb4");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Status handling
$status = $_GET['status'] ?? '';

// 1. Grant Access
if (isset($_POST['grant_access'])) {
    $r_id = (int)$_POST['role_id'];
    $c_id = (int)$_POST['category_id'];

    $check = $conn->prepare("SELECT * FROM role_category_access WHERE role_id = ? AND category_id = ?");
    $check->bind_param("ii", $r_id, $c_id);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        $_SESSION['toast'] = ['type' => 'error', 'title' => 'දැනටමත් පවතී', 'message' => 'මෙම භූමිකාව සඳහා මෙම ප්‍රවර්ගය දැනටමත් පවරා ඇත.'];
    } else {
        $stmt = $conn->prepare("INSERT INTO role_category_access (role_id, category_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $r_id, $c_id);
        if ($stmt->execute()) {
            $_SESSION['toast'] = ['type' => 'success', 'title' => 'ප්‍රවේශය පවරන ලදී', 'message' => 'ප්‍රවේශ අවසරය සාර්ථකව පවරන ලදී.'];
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'title' => 'දෝෂයක්', 'message' => 'දත්ත සමුදායේ දෝෂයක්: ' . $stmt->error];
        }
        $stmt->close();
    }
    $check->close();
    header("Location: assign_categories.php");
    exit();
}

// 2. Revoke Access (Delete)
if (isset($_GET['revoke_role']) && isset($_GET['revoke_cat'])) {
    $r_id = (int)$_GET['revoke_role'];
    $c_id = (int)$_GET['revoke_cat'];
    
    // Check if this access is being used by any users? (Foreign key check)
    // role_category_access is referenced by queries, but no direct foreign key from users
    
    $stmt = $conn->prepare("DELETE FROM role_category_access WHERE role_id = ? AND category_id = ?");
    $stmt->bind_param("ii", $r_id, $c_id);
    if ($stmt->execute()) {
        $_SESSION['toast'] = ['type' => 'success', 'title' => 'ප්‍රවේශය ඉවත් කරන ලදී', 'message' => 'ප්‍රවේශ අවසරය සාර්ථකව ඉවත් කරන ලදී.'];
    } else {
        $_SESSION['toast'] = ['type' => 'error', 'title' => 'ඉවත් කිරීම අසාර්ථකයි', 'message' => 'දෝෂය: ' . $stmt->error];
    }
    $stmt->close();
    header("Location: assign_categories.php");
    exit();
}

// Data Fetching with counts
$permissions = $conn->query("SELECT rca.*, r.role_name, c.category_name,
                            (SELECT COUNT(*) FROM users WHERE role_id = r.id) as user_count
                            FROM role_category_access rca
                            JOIN roles r ON rca.role_id = r.id
                            JOIN categories c ON rca.category_id = c.id
                            ORDER BY r.role_name ASC");

$all_roles = $conn->query("SELECT id, role_name FROM roles");
$all_cats = $conn->query("SELECT id, category_name FROM categories");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Matrix | ASB Group</title>
    <link rel="icon" type="image/png" href="logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Sinhala:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/assign-styles.css">
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
                <a href="assign_categories.php" class="nav-link active">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    <span>Access Matrix</span>
                </a>
                <a href="document_mgmt.php" class="nav-link">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/>
                        <polyline points="13 2 13 9 20 9"/>
                    </svg>
                    <span>Documents</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <div>
                    <h2 class="page-title">Access <span class="accent-text">Matrix</span></h2>
                    <p class="page-subtitle sinhala-text">ආරක්ෂක බලය පැවරීම | Security Authorization Registry</p>
                </div>
            </header>

            <div class="two-column-grid">
                <!-- FORM SECTION -->
                <div class="form-card animate-fadeInLeft">
                    <div class="card-header">
                        <div class="header-indicator"></div>
                        <h3 class="card-title">නව ප්‍රවේශයක් / New Permission</h3>
                    </div>
                    
                    <form method="POST" class="permission-form">
                        <div class="form-group">
                            <label class="form-label">භූමිකාව / Target Role</label>
                            <select name="role_id" required class="form-select">
                                <option value="">තෝරන්න / Select Role...</option>
                                <?php 
                                if($all_roles && $all_roles->num_rows > 0) {
                                    $all_roles->data_seek(0); 
                                    while($r = $all_roles->fetch_assoc()): ?>
                                        <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['role_name']); ?></option>
                                <?php endwhile; } ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">ප්‍රවර්ගය / Category Authority</label>
                            <select name="category_id" required class="form-select">
                                <option value="">තෝරන්න / Select Category...</option>
                                <?php 
                                if($all_cats && $all_cats->num_rows > 0) {
                                    $all_cats->data_seek(0); 
                                    while($c = $all_cats->fetch_assoc()): ?>
                                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['category_name']); ?></option>
                                <?php endwhile; } ?>
                            </select>
                        </div>

                        <button type="submit" name="grant_access" class="submit-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path d="M12 5v14M5 12h14"/>
                            </svg>
                            ප්‍රවේශය පවරන්න / Bind Access Rights
                        </button>
                    </form>
                </div>

                <!-- TABLE SECTION -->
                <div class="table-card animate-fadeInRight">
                    <!-- Filters -->
                    <div class="filters-bar">
                        <div class="search-wrapper">
                            <div class="search-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="11" cy="11" r="8"/>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                </svg>
                            </div>
                            <input type="text" id="matrixSearch" placeholder="සොයන්න / Quick Search..." class="search-input">
                        </div>
                        
                        <div class="filter-group">
                            <select id="roleFilter" class="filter-select">
                                <option value="">සියලුම භූමිකා / All Roles</option>
                                <?php 
                                if($all_roles && $all_roles->num_rows > 0) {
                                    $all_roles->data_seek(0); 
                                    while($r = $all_roles->fetch_assoc()): ?>
                                        <option value="<?php echo strtoupper($r['role_name']); ?>"><?php echo htmlspecialchars($r['role_name']); ?></option>
                                <?php endwhile; } ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <select id="catFilter" class="filter-select">
                                <option value="">සියලුම ප්‍රවර්ග / All Categories</option>
                                <?php 
                                if($all_cats && $all_cats->num_rows > 0) {
                                    $all_cats->data_seek(0); 
                                    while($c = $all_cats->fetch_assoc()): ?>
                                        <option value="<?php echo strtoupper($c['category_name']); ?>"><?php echo htmlspecialchars($c['category_name']); ?></option>
                                <?php endwhile; } ?>
                            </select>
                        </div>
                    </div>

                    <!-- Permissions Table -->
                    <div class="table-container">
                        <table class="data-table" id="matrixTable">
                            <thead>
                                <tr>
                                    <th>භූමිකාව / Role</th>
                                    <th>ප්‍රවර්ගය / Category</th>
                                    <th class="text-right">ඉවත් කරන්න / Revoke</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($permissions && $permissions->num_rows > 0): ?>
                                    <?php while($row = $permissions->fetch_assoc()): ?>
                                    <tr class="table-row">
                                        <td>
                                            <div class="role-cell">
                                                <div class="role-indicator"></div>
                                                <span class="role-name"><?php echo htmlspecialchars($row['role_name']); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="category-badge">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z"/>
                                                </svg>
                                                <?php echo htmlspecialchars($row['category_name']); ?>
                                            </span>
                                        </td>
                                        <td class="text-right">
                                            <button onclick="confirmRevoke(<?php echo $row['role_id']; ?>, <?php echo $row['category_id']; ?>, '<?php echo addslashes($row['role_name']); ?>', '<?php echo addslashes($row['category_name']); ?>')" 
                                                    class="revoke-btn" title="Revoke Access">
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
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="empty-table">
                                            <div class="empty-content">
                                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                                </svg>
                                                <p>ප්‍රවේශ පැවරුම් නොමැත / No access assignments found</p>
                                                <p class="empty-hint">ඉහත පෝරමය භාවිතා කර ප්‍රවේශයක් පවරන්න</p>
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
                                <strong>තොරතුරු / Information</strong>
                                <p class="sinhala-text">මෙම ප්‍රවේශ පැවරුම් මගින් භූමිකාවන්ට ප්‍රවර්ග වෙත ප්‍රවේශ වීමේ අවසරය ලැබේ.</p>
                                <p>These access assignments determine which roles can access which document categories.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Toast Notification Container -->
    <div id="toastContainer" class="toast-container"></div>

    <script>
        // Filter Functions
        function performFilter() {
            const searchInput = document.getElementById('matrixSearch');
            const roleFilter = document.getElementById('roleFilter');
            const catFilter = document.getElementById('catFilter');
            
            if (!searchInput || !roleFilter || !catFilter) return;
            
            let search = searchInput.value.toUpperCase();
            let roleF = roleFilter.value;
            let catF = catFilter.value;
            let tbody = document.querySelector("#matrixTable tbody");
            
            if (!tbody) return;
            let rows = tbody.rows;

            for (let i = 0; i < rows.length; i++) {
                let roleText = rows[i].cells[0]?.textContent.toUpperCase() || '';
                let catText = rows[i].cells[1]?.textContent.toUpperCase() || '';
                let fullText = rows[i].innerText.toUpperCase();

                let matchesSearch = fullText.includes(search);
                let matchesRole = roleF === "" || roleText.includes(roleF);
                let matchesCat = catF === "" || catText.includes(catF);

                rows[i].style.display = (matchesSearch && matchesRole && matchesCat) ? "" : "none";
            }
        }

        const searchInput = document.getElementById('matrixSearch');
        const roleFilter = document.getElementById('roleFilter');
        const catFilter = document.getElementById('catFilter');
        
        if (searchInput) searchInput.addEventListener('keyup', performFilter);
        if (roleFilter) roleFilter.addEventListener('change', performFilter);
        if (catFilter) catFilter.addEventListener('change', performFilter);

        // Toast Notification System
        function showToast(type, title, message) {
            const container = document.getElementById('toastContainer');
            if (!container) return;
            
            const toast = document.createElement('div');
            toast.className = `toast toast-${type} animate-slideIn`;
            
            let iconPath = type === 'success' ? 
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
            }, 4000);
        }

        // Custom Confirm Dialog for Revoke
        function confirmRevoke(rId, cId, rName, cName) {
            const overlay = document.createElement('div');
            overlay.className = 'custom-confirm-overlay';
            
            const dialog = document.createElement('div');
            dialog.className = 'custom-confirm-dialog animate-fadeIn';
            dialog.innerHTML = `
                <div class="confirm-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M12 8v4M12 16h.01"/>
                        <circle cx="12" cy="12" r="10"/>
                    </svg>
                </div>
                <h3 class="confirm-title">ප්‍රවේශය ඉවත් කරන්න?</h3>
                <p class="confirm-text">
                    <strong>${rName}</strong> භූමිකාවෙන් <strong class="accent-text">${cName}</strong> ප්‍රවර්ගයට ඇති ප්‍රවේශය ඉවත් කරන්නද?
                </p>
                <p class="confirm-subtext">Revoke access to "${cName}" from role "${rName}"?</p>
                <div class="confirm-actions">
                    <button class="confirm-btn confirm-yes" data-id="${rId}" data-cat="${cId}">ඔව්, ඉවත් කරන්න</button>
                    <button class="confirm-btn confirm-no">නැත, අවලංගු කරන්න</button>
                </div>
            `;
            
            overlay.appendChild(dialog);
            document.body.appendChild(overlay);
            
            dialog.querySelector('.confirm-yes').onclick = () => {
                window.location.href = `assign_categories.php?revoke_role=${rId}&revoke_cat=${cId}`;
            };
            
            dialog.querySelector('.confirm-no').onclick = () => {
                overlay.remove();
            };
            
            overlay.onclick = (e) => {
                if (e.target === overlay) overlay.remove();
            };
        }

        // Check URL for status and show toast
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');

        <?php if(isset($_SESSION['toast'])): ?>
            showToast('<?php echo $_SESSION['toast']['type']; ?>', '<?php echo $_SESSION['toast']['title']; ?>', '<?php echo addslashes($_SESSION['toast']['message']); ?>');
            <?php unset($_SESSION['toast']); ?>
        <?php endif; ?>
        
        // Clean URL after showing
        if (window.location.search) {
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    </script>
</body>
</html>