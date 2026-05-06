<?php
session_start();
// Security Check: Only Super Admins (Role 1)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: dashboard.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "asb_file_system");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch Filter Options for the dropdowns
$roles = $conn->query("SELECT * FROM roles");
$categories = $conn->query("SELECT * FROM categories");

/**
 * INTELLIGENCE QUERY:
 * Joins users, roles, and branches to provide a full audit trail
 * Uses your actual table columns: doc_name, clicked_at, interaction_type
 */
$query = "SELECT di.*, r.role_name, b.branch_name 
          FROM document_interactions di
          JOIN users u ON di.user_id = u.id
          JOIN roles r ON u.role_id = r.id
          JOIN branches b ON u.branch_id = b.id
          ORDER BY di.clicked_at DESC";

$logs = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intelligence Reports | ASB Group</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; }
        .crimson-gradient { background: linear-gradient(135deg, #be123c 0%, #7f1d1d 100%); }
        .filter-input { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 10px 15px; font-size: 10px; font-weight: 800; text-transform: uppercase; outline: none; transition: all 0.3s; }
        .filter-input:focus { border-color: #be123c; box-shadow: 0 0 0 4px rgba(190, 18, 60, 0.1); }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body class="flex min-h-screen">

    <main class="flex-1 p-10">
        
        <!-- TOP NAVIGATION BAR (Back to Dashboard) -->
        <div class="flex justify-between items-center mb-8 pb-4 border-b border-slate-200 no-print">
            <a href="dashboard.php" class="flex items-center gap-2 text-slate-500 hover:text-rose-700 transition-all group">
                <div class="w-8 h-8 bg-slate-900 rounded-lg flex items-center justify-center text-white group-hover:bg-rose-700 transition-colors">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                </div>
                <span class="text-[10px] font-black uppercase tracking-widest">Return to Dashboard</span>
            </a>
            
            <div class="flex items-center gap-3">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-tighter">Live Audit Stream Active</p>
            </div>
        </div>

        <!-- Header -->
        <header class="flex justify-between items-end mb-10">
            <div>
                <h2 class="text-4xl font-black text-slate-900 italic uppercase tracking-tighter">
                    Interaction <span class="text-rose-700">Intelligence</span>
                </h2>
                <p class="text-slate-400 text-[10px] font-black uppercase tracking-[0.3em] mt-1">Advanced Audit & Reporting Engine</p>
            </div>
            
            <button onclick="window.print()" class="no-print crimson-gradient text-white px-6 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest hover:scale-105 transition shadow-lg shadow-rose-200">
                <i class="fa-solid fa-file-export mr-2"></i> Export System Log
            </button>
        </header>

        <!-- Filter Suite -->
        <div class="no-print bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100 mb-8 grid grid-cols-1 md:grid-cols-4 gap-4 animate__animated animate__fadeIn">
            <div class="flex flex-col gap-2">
                <label class="text-[9px] font-black text-slate-400 uppercase ml-2">Search Records</label>
                <input type="text" id="globalSearch" placeholder="Search anything..." class="filter-input">
            </div>

            <div class="flex flex-col gap-2">
                <label class="text-[9px] font-black text-slate-400 uppercase ml-2">Role Level</label>
                <select id="roleFilter" class="filter-input">
                    <option value="">All Roles</option>
                    <?php while($r = $roles->fetch_assoc()): ?>
                        <option value="<?php echo strtoupper($r['role_name']); ?>"><?php echo $r['role_name']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="flex flex-col gap-2">
                <label class="text-[9px] font-black text-slate-400 uppercase ml-2">Category</label>
                <select id="catFilter" class="filter-input">
                    <option value="">All Categories</option>
                    <?php while($c = $categories->fetch_assoc()): ?>
                        <option value="<?php echo strtoupper($c['category_name']); ?>"><?php echo $c['category_name']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="flex flex-col gap-2">
                <label class="text-[9px] font-black text-slate-400 uppercase ml-2">Action Type</label>
                <select id="actionFilter" class="filter-input">
                    <option value="">All Actions</option>
                    <option value="VIEW">View</option>
                    <option value="DOWNLOAD">Download</option>
                </select>
            </div>
        </div>

        <!-- Intelligence Table -->
        <div class="bg-white rounded-[2.5rem] shadow-2xl border border-slate-100 overflow-hidden animate__animated animate__fadeInUp">
            <table class="w-full text-left" id="intelTable">
                <thead class="bg-slate-900 text-white">
                    <tr>
                        <th class="px-8 py-5 text-[9px] font-black uppercase tracking-widest">User / Branch</th>
                        <th class="px-8 py-5 text-[9px] font-black uppercase tracking-widest">Document Target</th>
                        <th class="px-8 py-5 text-[9px] font-black uppercase tracking-widest text-center">Action</th>
                        <th class="px-8 py-5 text-[9px] font-black uppercase tracking-widest text-right">Timestamp</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php if ($logs && $logs->num_rows > 0): ?>
                        <?php while($row = $logs->fetch_assoc()): ?>
                        <tr class="log-row hover:bg-slate-50 transition" 
                            data-role="<?php echo strtoupper($row['role_name']); ?>" 
                            data-cat="<?php echo strtoupper($row['category_name']); ?>" 
                            data-action="<?php echo strtoupper($row['interaction_type']); ?>">
                            
                            <td class="px-8 py-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-slate-900 flex items-center justify-center text-rose-500 font-black text-xs">
                                        <?php echo strtoupper(substr($row['user_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <p class="text-xs font-black text-slate-800 uppercase italic"><?php echo $row['user_name']; ?></p>
                                        <p class="text-[8px] text-slate-400 font-bold uppercase tracking-widest">
                                            <?php echo $row['role_name']; ?> • <span class="text-rose-700"><?php echo $row['branch_name']; ?></span>
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-8 py-5">
                                <p class="text-xs font-bold text-slate-700 uppercase truncate max-w-[250px] mb-1">
                                    <i class="fa-solid fa-file-lines mr-2 text-slate-300"></i><?php echo $row['doc_name']; ?>
                                </p>
                                <span class="text-[8px] bg-rose-50 px-2 py-0.5 rounded text-rose-700 font-black uppercase tracking-tighter">
                                    <?php echo $row['category_name']; ?>
                                </span>
                            </td>

                            <td class="px-8 py-5 text-center">
                                <span class="px-3 py-1 rounded-lg text-[9px] font-black italic <?php echo $row['interaction_type'] == 'DOWNLOAD' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700'; ?>">
                                    <?php echo $row['interaction_type']; ?>
                                </span>
                            </td>

                            <td class="px-8 py-5 text-right font-bold text-slate-400 text-[10px] uppercase">
                                <?php echo date('M d, Y', strtotime($row['clicked_at'])); ?> <br>
                                <span class="text-slate-900"><?php echo date('H:i A', strtotime($row['clicked_at'])); ?></span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="p-20 text-center">
                                <i class="fa-solid fa-database text-slate-200 text-5xl mb-4"></i>
                                <p class="text-slate-400 font-black text-xs uppercase tracking-widest">No Interaction Logs Found</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        const inputs = [
            document.getElementById('globalSearch'),
            document.getElementById('roleFilter'),
            document.getElementById('catFilter'),
            document.getElementById('actionFilter')
        ];

        inputs.forEach(input => {
            input.addEventListener('input', filterTable);
        });

        function filterTable() {
            const searchTerm = inputs[0].value.toUpperCase();
            const roleTerm = inputs[1].value.toUpperCase();
            const catTerm = inputs[2].value.toUpperCase();
            const actionTerm = inputs[3].value.toUpperCase();
            
            const rows = document.querySelectorAll('.log-row');

            rows.forEach(row => {
                const text = row.innerText.toUpperCase();
                const rowRole = row.getAttribute('data-role');
                const rowCat = row.getAttribute('data-cat');
                const rowAction = row.getAttribute('data-action');

                const matchesSearch = text.includes(searchTerm);
                const matchesRole = !roleTerm || rowRole === roleTerm;
                const matchesCat = !catTerm || rowCat === catTerm;
                const matchesAction = !actionTerm || rowAction === actionTerm;

                row.style.display = (matchesSearch && matchesRole && matchesCat && matchesAction) ? "" : "none";
            });
        }
    </script>
</body>
</html>