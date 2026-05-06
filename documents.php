<?php
session_start();
if (!isset($_SESSION['user_id'])) { 
    header("Location: index.php"); 
    exit(); 
}

$conn = new mysqli("localhost", "root", "", "asb_file_system");

$cat_id = $_GET['cat_id'] ?? 0;
$role_id = $_SESSION['role_id'];
$user_branch = $_SESSION['branch_id'];

// Filters
$search = $_GET['search'] ?? '';
$year = $_GET['year'] ?? '';
$month = $_GET['month'] ?? '';

// --- PAGINATION LOGIC (Handelling 10,000+ records) ---
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

// 2. Get Dynamic Years (Only years that actually exist in your DB for this category)
$year_query = $conn->prepare("SELECT DISTINCT YEAR(doc_date) as yr FROM documents WHERE category_id = ? ORDER BY yr DESC");
$year_query->bind_param("i", $cat_id);
$year_query->execute();
$year_result = $year_query->get_result();
$available_years = [];
while($yr_row = $year_result->fetch_assoc()) {
    if($yr_row['yr']) $available_years[] = $yr_row['yr'];
}

// 3. Dynamic Query Building
$query_base = "FROM documents d WHERE d.category_id = ? AND (d.branch_id = ? OR d.branch_id = '3')";
$params = [$cat_id, $user_branch];
$types = "ii";

if (!empty($search)) {
    $query_base .= " AND d.title LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
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

// Get Total Count for Pagination
$count_stmt = $conn->prepare("SELECT COUNT(*) as total " . $query_base);
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// Get Paginated Data
$final_query = "SELECT d.* " . $query_base . " ORDER BY d.doc_date DESC LIMIT ? OFFSET ?";
$final_params = array_merge($params, [$limit, $offset]);
$final_types = $types . "ii";

$stmt = $conn->prepare($final_query);
$stmt->bind_param($final_types, ...$final_params);
$stmt->execute();
$docs = $stmt->get_result();

// 4. Category Info (FIXED: Added get_result() to solve the Fatal Error)
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
	 <link rel="icon" type="image/png" href="logo.png">
    <title>ASB Archive | <?php echo htmlspecialchars($cat_info['category_name'] ?? 'Directory'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        .crimson-header { background: linear-gradient(90deg, #0f172a 0%, #7f1d1d 100%); }
        .glass-input { background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); }
        tr { transition: all 0.2s; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen font-sans">

    <div class="crimson-header text-white p-8 shadow-2xl mb-6">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-end gap-6">
            <div class="animate__animated animate__fadeInLeft">
                <a href="dashboard.php" class="text-[10px] font-bold text-red-400 hover:text-white transition tracking-widest uppercase">← Back to Dashboard</a>
                <h1 class="text-3xl font-black uppercase italic"><?php echo htmlspecialchars($cat_info['category_name'] ?? 'Documents'); ?></h1>
                <p class="text-[10px] text-slate-300 uppercase tracking-[0.3em] mt-1">Registry Count: <?php echo number_format($total_records); ?> Files</p>
            </div>

            <!-- Filter Form -->
            <form method="GET" class="flex flex-wrap gap-2 bg-black/30 p-4 rounded-xl border border-white/10 animate__animated animate__fadeInRight">
                <input type="hidden" name="cat_id" value="<?php echo $cat_id; ?>">
                
                <input type="text" name="search" placeholder="Search title..." value="<?php echo htmlspecialchars($search); ?>" 
                    class="glass-input p-2 rounded text-xs text-white outline-none focus:bg-white focus:text-black transition w-40">

                <select name="year" class="glass-input p-2 rounded text-xs text-white outline-none focus:bg-white focus:text-black">
                    <option value="">All Years</option>
                    <?php foreach($available_years as $y): ?>
                        <option value="<?php echo $y; ?>" <?php echo ($year == $y) ? 'selected' : ''; ?> class="text-black"><?php echo $y; ?></option>
                    <?php endforeach; ?>
                </select>

                <select name="month" class="glass-input p-2 rounded text-xs text-white outline-none focus:bg-white focus:text-black">
                    <option value="">All Months</option>
                    <?php 
                    for($m=1; $m<=12; $m++) {
                        $monthName = date("F", mktime(0, 0, 0, $m, 10));
                        echo "<option value='$m' ".($month == $m ? 'selected' : '')." class='text-black'>$monthName</option>";
                    }
                    ?>
                </select>
                
                <button type="submit" class="bg-white text-slate-900 px-6 py-2 rounded font-black text-[10px] hover:bg-red-600 hover:text-white transition shadow-lg uppercase">Filter</button>
                <a href="documents.php?cat_id=<?php echo $cat_id; ?>" class="bg-red-900/50 text-white px-4 py-2 rounded font-bold text-[10px] hover:bg-red-700 transition flex items-center uppercase">Reset</a>
            </form>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-6 pb-20 animate__animated animate__fadeInUp">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-slate-200">
            <table class="w-full text-left">
                <thead class="bg-slate-900 text-slate-400 text-[10px] uppercase tracking-widest font-bold">
                    <tr>
                        <th class="px-8 py-5">Reference</th>
                        <th class="px-8 py-5">Document Name</th>
                        <th class="px-8 py-5 text-center">Date Issued</th>
                        <th class="px-8 py-5 text-right">Operations</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    <?php if($docs->num_rows > 0): ?>
                        <?php while($row = $docs->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-8 py-5 font-mono font-bold text-red-700 italic"><?php echo $row['doc_number']; ?></td>
                                <td class="px-8 py-5">
                                    <span class="font-extrabold text-slate-800 uppercase block tracking-tight"><?php echo htmlspecialchars($row['title']); ?></span>
                                    <span class="text-[10px] text-slate-400 uppercase tracking-widest">Secure Archive File</span>
                                </td>
                                <td class="px-8 py-5 text-center text-slate-500 font-bold"><?php echo date('d M Y', strtotime($row['doc_date'])); ?></td>
                                <td class="px-8 py-5 text-right">
                                    <div class="flex justify-end items-center gap-3">
                                        <a href="download.php?id=<?php echo $row['id']; ?>&view=1" target="_blank" 
                                           class="text-[10px] font-black uppercase border-b-2 border-transparent hover:border-red-600 transition">View</a>
                                        
                                        <a href="download.php?id=<?php echo $row['id']; ?>" 
                                           class="bg-slate-900 text-white px-5 py-2 rounded-lg text-[10px] font-black hover:bg-red-700 transition shadow-md uppercase">Download</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="p-32 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <span class="text-slate-300 font-black text-4xl uppercase italic opacity-20">No Records</span>
                                    <span class="text-slate-400 font-bold uppercase tracking-widest text-xs">Try adjusting your filters</span>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <!-- Pagination Controls -->
            <?php if($total_pages > 1): ?>
            <div class="p-6 bg-slate-50 border-t border-slate-200 flex justify-center items-center gap-2">
                <?php 
                $start_loop = max(1, $page - 2);
                $end_loop = min($total_pages, $page + 2);
                
                if($page > 1): ?>
                    <a href="?cat_id=<?php echo $cat_id; ?>&page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&year=<?php echo $year; ?>&month=<?php echo $month; ?>" class="px-3 py-2 bg-white border rounded text-xs font-bold shadow-sm hover:bg-slate-100">Prev</a>
                <?php endif;

                for($i=$start_loop; $i<=$end_loop; $i++): ?>
                    <a href="?cat_id=<?php echo $cat_id; ?>&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&year=<?php echo $year; ?>&month=<?php echo $month; ?>" 
                       class="px-4 py-2 rounded font-bold text-xs transition <?php echo ($page == $i) ? 'bg-red-600 text-white shadow-lg' : 'bg-white text-slate-600 border hover:bg-slate-100'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor;

                if($page < $total_pages): ?>
                    <a href="?cat_id=<?php echo $cat_id; ?>&page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&year=<?php echo $year; ?>&month=<?php echo $month; ?>" class="px-3 py-2 bg-white border rounded text-xs font-bold shadow-sm hover:bg-slate-100">Next</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>