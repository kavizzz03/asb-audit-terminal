<?php
session_start();
if (!isset($_SESSION['user_id'])) { 
    header("Location: index.php"); 
    exit(); 
}

$conn = new mysqli("localhost", "root", "", "asb_file_system");
$conn->set_charset("utf8mb4");

$cat_id = $_GET['cat_id'] ?? 0;
$role_id = $_SESSION['role_id'];
$user_branch = $_SESSION['branch_id'];

$search = $_GET['search'] ?? '';
$year = $_GET['year'] ?? '';
$month = $_GET['month'] ?? '';

$limit = 50; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// 1. Access Check
$access_stmt = $conn->prepare("SELECT * FROM role_category_access WHERE role_id = ? AND category_id = ?");
$access_stmt->bind_param("ii", $role_id, $cat_id);
$access_stmt->execute();
if ($access_stmt->get_result()->num_rows === 0) {
    die("<div style='font-family:sans-serif; padding:50px; text-align:center;'>
            <h2 style='color:#dc2626;'>ACCESS DENIED</h2>
            <p>You do not have permission to view this archive.</p>
            <a href='dashboard.php'>Return to Dashboard</a>
         </div>");
}

// 2. Available Years
$year_query = $conn->prepare("SELECT DISTINCT YEAR(doc_date) as yr FROM documents WHERE category_id = ? ORDER BY yr DESC");
$year_query->bind_param("i", $cat_id);
$year_query->execute();
$year_result = $year_query->get_result();
$available_years = [];
while($yr_row = $year_result->fetch_assoc()) {
    if($yr_row['yr']) $available_years[] = $yr_row['yr'];
}

// 3. Query Building
$query_base = "FROM documents d WHERE d.category_id = ? AND (d.branch_id = ? OR d.branch_id = '3')";
$params = [$cat_id, $user_branch];
$types = "ii";

if (!empty($search)) {
    $query_base .= " AND (d.title LIKE ? OR d.doc_number LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}
if (!empty($year)) {
    $query_base .= " AND YEAR(d.doc_date) = ?";
    $params[] = $year;
    $types .= "i";
}
if (!empty($month)) {
    $query_base .= " AND MONTH(d.doc_date) = ?";
    $params[] = $month;
    $types .= "i";
}

$count_stmt = $conn->prepare("SELECT COUNT(*) as total " . $query_base);
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

$final_query = "SELECT d.* " . $query_base . " ORDER BY d.doc_date DESC LIMIT ? OFFSET ?";
$final_params = array_merge($params, [$limit, $offset]);
$final_types = $types . "ii";

$stmt = $conn->prepare($final_query);
$stmt->bind_param($final_types, ...$final_params);
$stmt->execute();
$docs = $stmt->get_result();

$cat_stmt = $conn->prepare("SELECT category_name FROM categories WHERE id = ?");
$cat_stmt->bind_param("i", $cat_id);
$cat_stmt->execute();
$cat_res = $cat_stmt->get_result();
$cat_info = $cat_res->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="logo.png">
    <title>ASB Archive | <?php echo htmlspecialchars($cat_info['category_name'] ?? 'Directory'); ?></title>
    <link rel="stylesheet" href="css\documents-styles.css">
</head>
<body>
    <div class="archive-container">
        <!-- Header Section -->
        <div class="archive-header">
            <div class="header-content">
                <div class="header-left animate-fadeInLeft">
                    <a href="dashboard.php" class="back-link">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="15 18 9 12 15 6"/>
                        </svg>
                        Back to Dashboard
                    </a>
                    <h1 class="category-title sinhala-text"><?php echo htmlspecialchars($cat_info['category_name'] ?? 'Documents'); ?></h1>
                    <p class="record-count">Registry Count: <?php echo number_format($total_records); ?> Files</p>
                </div>

                <!-- Search Form -->
                <form id="filterForm" method="GET" class="filter-form animate-fadeInRight">
                    <input type="hidden" name="cat_id" value="<?php echo $cat_id; ?>">
                    
                    <div class="search-wrapper">
                        <div class="search-icon">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"/>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                            </svg>
                        </div>
                        <input type="text" name="search" id="dynamicSearch" 
                               placeholder="Search Title or Ref..." 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               class="search-input">
                    </div>

                    <select name="year" class="filter-select" onchange="this.form.submit()">
                        <option value="">All Years</option>
                        <?php foreach($available_years as $y): ?>
                            <option value="<?php echo $y; ?>" <?php echo ($year == $y) ? 'selected' : ''; ?>><?php echo $y; ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select name="month" class="filter-select" onchange="this.form.submit()">
                        <option value="">All Months</option>
                        <?php 
                        for($m=1; $m<=12; $m++) {
                            $monthName = date("F", mktime(0, 0, 0, $m, 10));
                            echo "<option value='$m' ".($month == $m ? 'selected' : '').">$monthName</option>";
                        }
                        ?>
                    </select>
                    
                    <button type="submit" class="filter-btn">Filter</button>
                    <a href="documents.php?cat_id=<?php echo $cat_id; ?>" class="reset-btn">Reset</a>
                </form>
            </div>
        </div>

        <!-- Table Section -->
        <div class="table-wrapper animate-fadeInUp">
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Document Name</th>
                            <th class="text-center">Date Issued</th>
                            <th class="text-right">Operations</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($docs->num_rows > 0): ?>
                            <?php while($row = $docs->fetch_assoc()): ?>
                                <tr class="table-row">
                                    <td class="ref-number"><?php echo htmlspecialchars($row['doc_number']); ?></td>
                                    <td>
                                        <span class="doc-title sinhala-text"><?php echo htmlspecialchars($row['title']); ?></span>
                                        <span class="doc-badge">Secure Archive File</span>
                                    </td>
                                    <td class="text-center date-text"><?php echo date('d M Y', strtotime($row['doc_date'])); ?></td>
                                    <td class="text-right">
                                        <div class="action-buttons">
                                            <a href="track_interaction.php?id=<?php echo $row['id']; ?>&type=VIEW" target="_blank" class="action-link view-link">View</a>
                                            <a href="track_interaction.php?id=<?php echo $row['id']; ?>&type=DOWNLOAD" class="action-link download-link">Download</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="empty-state">
                                    <div class="empty-content">
                                        <div class="empty-icon">
                                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                                                <line x1="8" y1="21" x2="16" y2="21"/>
                                                <line x1="12" y1="17" x2="12" y2="21"/>
                                            </svg>
                                        </div>
                                        <span class="empty-title">No Records</span>
                                        <span class="empty-subtitle">Try adjusting your filters</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <?php if($total_pages > 1): ?>
                <div class="pagination-container">
                    <div class="pagination">
                        <?php 
                        $start_loop = max(1, $page - 2);
                        $end_loop = min($total_pages, $page + 2);
                        
                        if($page > 1): ?>
                            <a href="?cat_id=<?php echo $cat_id; ?>&page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&year=<?php echo $year; ?>&month=<?php echo $month; ?>" class="page-link prev-next">Prev</a>
                        <?php endif;

                        for($i=$start_loop; $i<=$end_loop; $i++): ?>
                            <a href="?cat_id=<?php echo $cat_id; ?>&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&year=<?php echo $year; ?>&month=<?php echo $month; ?>" 
                               class="page-link <?php echo ($page == $i) ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor;

                        if($page < $total_pages): ?>
                            <a href="?cat_id=<?php echo $cat_id; ?>&page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&year=<?php echo $year; ?>&month=<?php echo $month; ?>" class="page-link prev-next">Next</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Dynamic Search Logic (Debounced)
        let typingTimer;
        const doneTypingInterval = 500;
        const searchInput = document.getElementById('dynamicSearch');
        const filterForm = document.getElementById('filterForm');

        if (searchInput) {
            searchInput.addEventListener('keyup', () => {
                clearTimeout(typingTimer);
                typingTimer = setTimeout(submitForm, doneTypingInterval);
            });

            searchInput.addEventListener('keydown', () => {
                clearTimeout(typingTimer);
            });
        }

        function submitForm() {
            filterForm.submit();
        }

        // Keep cursor at the end of text on focus
        if (searchInput) {
            const val = searchInput.value;
            searchInput.value = '';
            searchInput.focus();
            searchInput.value = val;
        }
    </script>
</body>
</html>