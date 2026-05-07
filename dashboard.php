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

$role_id = $_SESSION['role_id'];
$branch_id = $_SESSION['branch_id']; 
$user_name = $_SESSION['user_name'] ?? 'Authorized User';

// Check if user is Super Admin
$is_super_admin = ($role_id == 1);

/**
 * LOGIC: Fetch authorized categories based on Role ID
 */
$cat_query = "SELECT c.* 
              FROM categories c 
              JOIN role_category_access rca ON c.id = rca.category_id 
              WHERE rca.role_id = ?";

$stmt = $conn->prepare($cat_query);
$stmt->bind_param("i", $role_id); 
$stmt->execute();
$categories = $stmt->get_result();

// Stats for Super Admin View
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
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; overflow: hidden; }
        .sidebar { background: #0f172a; } 
        .crimson-gradient-bg { background: linear-gradient(135deg, #be123c 0%, #7f1d1d 100%); }
        .nav-active { background: linear-gradient(90deg, #be123c 0%, transparent 100%); border-left: 4px solid #e11d48; }
        .nav-link { transition: all 0.3s ease; border-left: 4px solid transparent; }
        .nav-link:hover { background: rgba(255, 255, 255, 0.05); color: #fff; border-left: 4px solid #9f1239; }
        .crimson-card { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .crimson-card:hover { 
            transform: translateY(-10px);
            box-shadow: 0 30px 60px -15px rgba(159, 18, 57, 0.2);
            border-color: #fecdd3;
        }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body class="bg-slate-50 flex h-screen">

    <!-- Sidebar -->
    <div class="sidebar w-80 min-h-screen text-slate-400 p-6 hidden lg:flex flex-col shadow-2xl z-20">
        <div class="mb-12 px-4">
            <h1 class="text-2xl font-extrabold text-white tracking-tighter italic">ASB <span class="text-rose-600">GROUP</span></h1>
            <p class="text-[9px] font-black text-slate-500 uppercase tracking-[0.4em] mt-1">Registry Terminal v2.0</p>
        </div>
        
        <nav class="flex-1 space-y-1 overflow-y-auto pr-2">
            <p class="text-[10px] font-bold text-slate-600 uppercase tracking-widest px-4 mb-2">Core Navigation</p>
            <a href="dashboard.php" class="nav-link nav-active flex items-center gap-3 p-3 text-white rounded-r-xl font-bold text-sm">
                <i class="fa-solid fa-chart-pie text-rose-500"></i> Dashboard Overview
            </a>

            <!-- MASTER ADMIN SECTION -->
            <?php if ($is_super_admin): ?>
                <div class="pt-8 pb-2 px-4">
                    <p class="text-[10px] font-bold text-slate-600 uppercase tracking-widest">Master Control</p>
                </div>
                
                <a href="document_interactions.php" class="nav-link flex items-center gap-3 p-3 rounded-r-xl text-sm font-semibold hover:text-white text-rose-400">
                    <i class="fa-solid fa-file-import"></i> Document Ingress
                </a>

                <a href="branch_mgmt.php" class="nav-link flex items-center gap-3 p-3 rounded-r-xl text-sm font-semibold hover:text-white">
                    <i class="fa-solid fa-building-shield text-rose-700"></i> Branch Management
                </a>

                <a href="category_mgmt.php" class="nav-link flex items-center gap-3 p-3 rounded-r-xl text-sm font-semibold hover:text-white">
                    <i class="fa-solid fa-folder-tree text-rose-700"></i> Category Architecture
                </a>
                
                <a href="user_mgmt.php" class="nav-link flex items-center gap-3 p-3 rounded-r-xl text-sm font-semibold hover:text-white">
                    <i class="fa-solid fa-users-gear text-rose-700"></i> User Access Control
                </a>
                
                <a href="role_mgmt.php" class="nav-link flex items-center gap-3 p-3 rounded-r-xl text-sm font-semibold hover:text-white">
                    <i class="fa-solid fa-shield-halved text-rose-700"></i> Security Roles
                </a>

                <a href="assign_categories.php" class="nav-link flex items-center gap-3 p-3 rounded-r-xl text-sm font-semibold hover:text-white">
                    <i class="fa-solid fa-key text-rose-700"></i> Assign Categories
                </a>

                <a href="document_mgmt.php" class="nav-link flex items-center gap-3 p-3 rounded-r-xl text-sm font-semibold hover:text-white">
                    <i class="fa-solid fa-file-shield text-rose-700"></i> Global Documents
                </a>

                <a href="user_sessions.php" class="nav-link flex items-center gap-3 p-3 rounded-r-xl text-sm font-semibold hover:text-white">
                    <i class="fa-solid fa-clock-rotate-left text-rose-700"></i> Active Sessions
                </a>
            <?php endif; ?>

        </nav>

        <!-- Sidebar Footer -->
        <div class="mt-auto pt-6 border-t border-slate-800">
            <div class="bg-slate-900/50 p-4 rounded-2xl mb-4 border border-white/5">
                <p class="text-[9px] font-black uppercase text-slate-500 mb-1">Signed in as</p>
                <p class="text-xs font-bold text-white truncate"><?php echo htmlspecialchars($user_name); ?></p>
            </div>
            <a href="logout.php" class="flex items-center gap-3 p-4 rounded-2xl text-rose-500 hover:bg-rose-500/10 transition-all font-black text-xs uppercase tracking-widest">
                <i class="fa-solid fa-power-off"></i> End Session
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <!-- Top Header -->
        <header class="bg-white border-b border-slate-200 px-10 py-6 flex justify-between items-center z-10">
            <div class="animate__animated animate__fadeInDown">
                <h2 class="text-2xl font-black text-slate-900 tracking-tight uppercase italic">Authorized <span class="text-rose-700">Archives</span></h2>
                <p class="text-slate-400 text-[10px] font-bold uppercase tracking-[0.2em]">ASB File System Infrastructure</p>
            </div>
            
            <div class="flex items-center gap-8 animate__animated animate__fadeInRight">
                <!-- Branch Stats -->
                <?php if($is_super_admin): ?>
                <div class="hidden md:block text-right">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Global Vault Size</p>
                    <p class="text-xs font-black text-slate-800 uppercase italic"><?php echo $total_docs; ?> Documents Indexed</p>
                </div>
                <?php endif; ?>

                <!-- User Profile Badge -->
                <div class="flex items-center gap-4 bg-slate-50 p-2 pr-6 rounded-2xl border border-slate-100">
                    <div class="h-10 w-10 rounded-xl crimson-gradient-bg flex items-center justify-center text-white shadow-lg shadow-rose-200">
                        <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                    </div>
                    <div>
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-tighter leading-none">Security Clearance</p>
                        <p class="text-xs font-bold text-slate-700"><?php echo $is_super_admin ? 'Master Admin' : 'Staff Level'; ?></p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Scrollable Body -->
        <div class="flex-1 overflow-y-auto p-10 bg-[#f8fafc]">
            
            <!-- Quick Glance Stats & Search -->
            <div class="flex flex-col md:flex-row gap-6 mb-12 items-end justify-between">
                <div class="flex gap-6">
                    <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm min-w-[160px]">
                        <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Branch Context</p>
                        <p class="text-xl font-black text-slate-900 uppercase italic">Code: <?php echo $branch_id; ?></p>
                    </div>
                    <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm min-w-[160px]">
                        <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Access Level</p>
                        <p class="text-xl font-black text-rose-700 uppercase italic">Lvl: <?php echo $role_id; ?></p>
                    </div>
                    <?php if($is_super_admin): ?>
                    <a href="document_interactions.php" class="crimson-gradient-bg p-6 rounded-3xl shadow-lg shadow-rose-200 min-w-[160px] group transition-all hover:scale-105">
                        <p class="text-[10px] font-black text-white/60 uppercase mb-1">System Action</p>
                        <p class="text-xl font-black text-white uppercase italic flex items-center gap-2">
                            New Ingress <i class="fa-solid fa-plus-circle group-hover:rotate-90 transition-transform"></i>
                        </p>
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Archive Search (Updated with ID) -->
                <div class="relative w-full md:w-96">
                    <i class="fa-solid fa-magnifying-glass absolute left-5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input type="text" id="categorySearch" placeholder="SEARCH DIRECTORIES..." 
                           class="w-full bg-white border border-slate-200 py-4 pl-12 pr-6 rounded-2xl text-[10px] font-black tracking-widest focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 outline-none transition-all shadow-sm uppercase">
                </div>
            </div>

            <!-- Section Title -->
            <div class="mb-8 px-2 flex justify-between items-center">
                <div>
                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.4em]">Resource Explorer</h4>
                    <h3 class="text-xl font-black text-slate-800 uppercase italic">Authorized <span class="text-rose-700">Directories</span></h3>
                </div>
                <div class="h-px bg-slate-200 flex-1 mx-8 hidden xl:block"></div>
            </div>

            <!-- Categories Grid (Updated with Container ID) -->
            <div id="categoryGrid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8 animate__animated animate__fadeInUp">
                <?php if ($categories && $categories->num_rows > 0): ?>
                    <?php while($row = $categories->fetch_assoc()): ?>
                        <!-- Added data-name and data-desc for the search filter -->
                        <div class="category-card" 
                             data-name="<?php echo strtolower(htmlspecialchars($row['category_name'])); ?>" 
                             data-desc="<?php echo strtolower(htmlspecialchars($row['description'])); ?>">
                            <a href="documents.php?cat_id=<?php echo $row['id']; ?>" 
                               class="bg-white p-8 rounded-[2.5rem] border border-slate-200 crimson-card group relative overflow-hidden flex flex-col justify-between min-h-[280px] h-full block">
                               
                                <!-- Design Element -->
                                <div class="absolute -top-10 -right-10 w-32 h-32 bg-rose-50 rounded-full opacity-50 group-hover:bg-rose-600 group-hover:scale-150 transition-all duration-700"></div>

                                <div class="relative z-10">
                                    <div class="w-14 h-14 bg-slate-900 rounded-2xl flex items-center justify-center mb-6 shadow-xl group-hover:bg-rose-600 transition-colors duration-500">
                                        <i class="fa-solid fa-folder-closed text-rose-500 group-hover:text-white text-xl"></i>
                                    </div>
                                    <h3 class="category-title text-xl font-black text-slate-800 group-hover:text-rose-700 transition-colors uppercase italic tracking-tight leading-tight">
                                        <?php echo htmlspecialchars($row['category_name']); ?>
                                    </h3>
                                    <p class="text-slate-400 mt-4 text-xs font-bold leading-relaxed line-clamp-2">
                                        <?php echo htmlspecialchars($row['description']); ?>
                                    </p>
                                </div>
                                
                                <div class="relative z-10 mt-8 flex items-center justify-between pt-6 border-t border-slate-50">
                                    <span class="text-rose-600 text-[10px] font-black tracking-[0.2em] uppercase flex items-center">
                                        Initialize Access
                                        <i class="fa-solid fa-chevron-right ml-2 transform group-hover:translate-x-2 transition-transform"></i>
                                    </span>
                                    <span class="text-slate-100 text-4xl font-black group-hover:text-rose-100 transition-colors italic">
                                        #<?php echo str_pad($row['id'], 2, '0', STR_PAD_LEFT); ?>
                                    </span>
                                </div>
                            </a>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>

                <!-- Empty State (Hidden by default, shown by JS) -->
                <div id="emptyState" class="hidden col-span-full py-32 text-center bg-white rounded-[3rem] border-4 border-dashed border-slate-200">
                    <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fa-solid fa-magnifying-glass text-3xl text-slate-200"></i>
                    </div>
                    <h3 class="text-2xl font-black text-slate-800 uppercase tracking-tighter italic">No Matches Found</h3>
                    <p class="text-slate-400 mt-2 font-bold text-xs uppercase tracking-widest">Adjust your search parameters or request access</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Live Search JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('categorySearch');
            const categoryCards = document.querySelectorAll('.category-card');
            const emptyState = document.getElementById('emptyState');
            const grid = document.getElementById('categoryGrid');

            searchInput.addEventListener('input', function(e) {
                const query = e.target.value.toLowerCase().trim();
                let visibleCount = 0;

                categoryCards.forEach(card => {
                    const name = card.getAttribute('data-name');
                    const desc = card.getAttribute('data-desc');

                    if (name.includes(query) || desc.includes(query)) {
                        card.style.display = 'block';
                        card.classList.add('animate__fadeIn');
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                // Handle Empty State Visibility
                if (visibleCount === 0) {
                    emptyState.classList.remove('hidden');
                } else {
                    emptyState.classList.add('hidden');
                }
            });
        });
    </script>
</body>
</html>