<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Database Connection
$conn = new mysqli("localhost", "root", "", "asb_file_system");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to support Sinhala/Unicode
$conn->set_charset("utf8mb4");

$role_id = $_SESSION['role_id'];
$branch_id = $_SESSION['branch_id']; 
$user_name = $_SESSION['user_name'] ?? 'Authorized User';
$is_super_admin = ($role_id == 1);

// Fetch authorized categories
$cat_query = "SELECT c.* 
              FROM categories c 
              JOIN role_category_access rca ON c.id = rca.category_id 
              WHERE rca.role_id = ?";
$stmt = $conn->prepare($cat_query);
$stmt->bind_param("i", $role_id); 
$stmt->execute();
$categories = $stmt->get_result();

// Stats for Super Admin
$branch_count = 0;
$total_docs = 0;
if ($is_super_admin) {
    $bc_res = $conn->query("SELECT COUNT(*) as total FROM branches");
    $branch_count = $bc_res->fetch_assoc()['total'];
    $doc_res = $conn->query("SELECT COUNT(*) as total FROM documents");
    $total_docs = $doc_res->fetch_assoc()['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ASB Dashboard | Central Registry</title>
    <link rel="icon" type="image/png" href="logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Sinhala:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard-styles.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h1 class="sidebar-title">ASB <span class="accent-text">GROUP</span></h1>
            <p class="sidebar-subtitle">Registry Terminal v2.0</p>
        </div>
        
        <nav class="sidebar-nav">
            <p class="nav-section-title">Core Navigation / ප්‍රධාන මෙනුව</p>
            <a href="dashboard.php" class="nav-link nav-active">
                <div class="nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M21 12v3a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4v-3"/>
                        <path d="M12 2L3 8l9 6 9-6-9-6z"/>
                        <path d="M12 14v8"/>
                    </svg>
                </div>
                <span>Dashboard Overview / මුල් පිටුව</span>
            </a>

            <!-- MASTER ADMIN SECTION -->
            <?php if ($is_super_admin): ?>
                <p class="nav-section-title mt-6">Master Control / පාලන මධ්‍යස්ථානය</p>
                
                <a href="document_interactions.php" class="nav-link">
                    <div class="nav-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/>
                            <polyline points="13 2 13 9 20 9"/>
                        </svg>
                    </div>
                    <span>Document Ingress / ලේඛන ඇතුළත් කිරීම</span>
                </a>

                <a href="branch_mgmt.php" class="nav-link">
                    <div class="nav-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                        </svg>
                    </div>
                    <span>Branch Management / ශාඛා කළමනාකරණය</span>
                </a>

                <a href="category_mgmt.php" class="nav-link">
                    <div class="nav-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z"/>
                        </svg>
                    </div>
                    <span>Category Architecture / ප්‍රවර්ග</span>
                </a>
                
                <a href="user_mgmt.php" class="nav-link">
                    <div class="nav-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                            <path d="M17 3.5a4 4 0 0 1 0 7"/>
                        </svg>
                    </div>
                    <span>User Access Control / පරිශීලක කළමනාකරණය</span>
                </a>
                
                <a href="role_mgmt.php" class="nav-link">
                    <div class="nav-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                    </div>
                    <span>Security Roles / ආරක්ෂක භූමිකා</span>
                </a>

                <a href="assign_categories.php" class="nav-link">
                    <div class="nav-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
                        </svg>
                    </div>
                    <span>Assign Categories / ප්‍රවර්ග පැවරීම</span>
                </a>

                <a href="document_mgmt.php" class="nav-link">
                    <div class="nav-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                        </svg>
                    </div>
                    <span>Global Documents / සියලු ලේඛන</span>
                </a>

                <a href="user_sessions.php" class="nav-link">
                    <div class="nav-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                    <span>Active Sessions / ක්‍රියාකාරී සැසි</span>
                </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <div class="user-info">
                <p class="user-label">Signed in as / පිවිසි</p>
                <p class="user-name sinhala-text"><?php echo htmlspecialchars($user_name); ?></p>
            </div>
            <a href="logout.php" class="logout-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                <span>End Session / ඉවත් වන්න</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <header class="main-header">
            <div class="header-title-section">
                <h2 class="header-title">Authorized <span class="accent-text">Archives</span></h2>
                <p class="header-subtitle sinhala-text">ASB ගොනු පද්ධතිය | ASB File System Infrastructure</p>
            </div>
            
            <div class="header-stats">
                <?php if($is_super_admin): ?>
                <div class="stat-card">
                    <p class="stat-label">Global Vault Size / සම්පූර්ණ ලේඛන</p>
                    <p class="stat-value"><?php echo $total_docs; ?> Documents Indexed</p>
                </div>
                <?php endif; ?>

                <div class="user-badge">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                    </div>
                    <div>
                        <p class="badge-label">Security Clearance / ආරක්ෂක මට්ටම</p>
                        <p class="badge-value"><?php echo $is_super_admin ? 'Master Admin / ප්‍රධාන පරිපාලක' : 'Staff Level / කාර්ය මණ්ඩලය'; ?></p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Scrollable Body -->
        <div class="content-body">
            <div class="stats-grid">
                <div class="stat-box">
                    <p class="stat-box-label">Branch Context / ශාඛාව</p>
                    <p class="stat-box-value">Code: <?php echo $branch_id; ?></p>
                </div>
                <div class="stat-box">
                    <p class="stat-box-label">Access Level / ප්‍රවේශ මට්ටම</p>
                    <p class="stat-box-value accent">Lvl: <?php echo $role_id; ?></p>
                </div>
                <?php if($is_super_admin): ?>
                <a href="document_interactions.php" class="stat-box action-box">
                    <p class="stat-box-label">System Action / පද්ධති ක්‍රියාව</p>
                    <p class="stat-box-value action-text">
                        New Ingress / අලුත් ලේඛනය
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="16"/>
                            <line x1="8" y1="12" x2="16" y2="12"/>
                        </svg>
                    </p>
                </a>
                <?php endif; ?>
            </div>

            <!-- Search Section -->
            <div class="search-section">
                <div class="search-container">
                    <div class="search-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                    </div>
                    <input type="text" id="categorySearch" placeholder="SEARCH DIRECTORIES / ප්‍රවර්ග සොයන්න..." 
                           class="search-input">
                </div>
            </div>

            <div class="explorer-header">
                <div>
                    <h4 class="explorer-label">Resource Explorer / සම්පත් ගවේෂකය</h4>
                    <h3 class="explorer-title">Authorized <span class="accent-text">Directories</span></h3>
                </div>
                <div class="explorer-divider"></div>
            </div>

            <!-- Categories Grid -->
            <div id="categoryGrid" class="categories-grid">
                <?php if ($categories && $categories->num_rows > 0): ?>
                    <?php while($row = $categories->fetch_assoc()): ?>
                        <div class="category-card" 
                             data-name="<?php echo strtolower(htmlspecialchars($row['category_name'])); ?>" 
                             data-desc="<?php echo strtolower(htmlspecialchars($row['description'])); ?>">
                            <a href="documents.php?cat_id=<?php echo $row['id']; ?>" class="card-link">
                                <div class="card-bg-effect"></div>
                                <div class="card-content">
                                    <div class="card-icon">
                                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <path d="M4 4h16v16H4z"/>
                                            <path d="M8 8h8"/>
                                            <path d="M8 12h6"/>
                                            <path d="M8 16h4"/>
                                        </svg>
                                    </div>
                                    <h3 class="card-title sinhala-text">
                                        <?php echo htmlspecialchars($row['category_name']); ?>
                                    </h3>
                                    <p class="card-description sinhala-text">
                                        <?php echo htmlspecialchars($row['description'] ?: 'විස්තරයක් නොමැත / No description'); ?>
                                    </p>
                                </div>
                                <div class="card-footer">
                                    <span class="card-action">
                                        Initialize Access / විවෘත කරන්න
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                            <polyline points="9 18 15 12 9 6"/>
                                        </svg>
                                    </span>
                                    <span class="card-id">
                                        #<?php echo str_pad($row['id'], 2, '0', STR_PAD_LEFT); ?>
                                    </span>
                                </div>
                            </a>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z"/>
                            </svg>
                        </div>
                        <h3 class="empty-title">No Categories Available</h3>
                        <p class="empty-subtitle">ඔබට ප්‍රවේශය ඇති ප්‍රවර්ග නොමැත / No categories found for your role</p>
                    </div>
                <?php endif; ?>

                <div id="emptyState" class="empty-state hidden">
                    <div class="empty-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="11" cy="11" r="8"/>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                    </div>
                    <h3 class="empty-title">No Matches Found</h3>
                    <p class="empty-subtitle">ප්‍රතිඵල කිසිවක් හමු නොවීය / No results match your search</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('categorySearch');
            const categoryCards = document.querySelectorAll('.category-card');
            const emptyState = document.getElementById('emptyState');

            if (searchInput) {
                searchInput.addEventListener('input', function(e) {
                    const query = e.target.value.toLowerCase().trim();
                    let visibleCount = 0;

                    categoryCards.forEach(card => {
                        const name = card.getAttribute('data-name');
                        const desc = card.getAttribute('data-desc');

                        if (name && (name.includes(query) || (desc && desc.includes(query)))) {
                            card.style.display = 'block';
                            visibleCount++;
                        } else if (card.style.display !== 'none') {
                            card.style.display = 'none';
                        }
                    });

                    if (emptyState) {
                        if (visibleCount === 0 && categoryCards.length > 0) {
                            emptyState.classList.remove('hidden');
                        } else {
                            emptyState.classList.add('hidden');
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>