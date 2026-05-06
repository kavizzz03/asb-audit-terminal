<?php
session_start();
// Security Check: Only Super Admin (Role 1)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: dashboard.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "asb_file_system");

// --- CRUD LOGIC ---

// 1. Grant Access (Create)
if (isset($_POST['grant_access'])) {
    $r_id = $_POST['role_id'];
    $c_id = $_POST['category_id'];

    // Check if link already exists to prevent duplicate errors
    $check = $conn->query("SELECT * FROM role_category_access WHERE role_id = $r_id AND category_id = $c_id");
    if ($check->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO role_category_access (role_id, category_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $r_id, $c_id);
        $stmt->execute();
    }
}

// 2. Revoke Access (Delete)
if (isset($_GET['revoke_role']) && isset($_GET['revoke_cat'])) {
    $r_id = $_GET['revoke_role'];
    $c_id = $_GET['revoke_cat'];
    $conn->query("DELETE FROM role_category_access WHERE role_id = $r_id AND category_id = $c_id");
    header("Location: assign_categories.php");
}

// Fetch Existing Permissions
$permissions = $conn->query("SELECT rca.*, r.role_name, c.category_name 
                             FROM role_category_access rca
                             JOIN roles r ON rca.role_id = r.id
                             JOIN categories c ON rca.category_id = c.id
                             ORDER BY r.role_name ASC");

// Fetch for Select Menus
$all_roles = $conn->query("SELECT id, role_name FROM roles");
$all_cats = $conn->query("SELECT id, category_name FROM categories");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Security Matrix | ASB Group</title>
	 <link rel="icon" type="image/png" href="logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; }
        .crimson-gradient { background: linear-gradient(135deg, #be123c 0%, #7f1d1d 100%); }
        .glass-panel { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); }
    </style>
</head>
<body class="flex">

    <!-- Sidebar -->
    <div class="w-80 min-h-screen bg-[#0f172a] p-6 text-slate-400 hidden lg:block">
        <div class="mb-12 px-4 italic font-black text-white text-xl">ASB <span class="text-rose-600">GROUP</span></div>
        <nav class="space-y-2 text-sm font-bold">
            <a href="dashboard.php" class="flex items-center gap-3 p-3 hover:bg-white/5 rounded-xl transition"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <a href="assign_categories.php" class="flex items-center gap-3 p-3 bg-rose-600/10 text-rose-500 rounded-xl"><i class="fa-solid fa-key"></i> Access Matrix</a>
            <a href="document_mgmt.php" class="flex items-center gap-3 p-3 hover:bg-white/5 rounded-xl transition"><i class="fa-solid fa-file-shield"></i> Documents</a>
        </nav>
    </div>

    <main class="flex-1 p-10 h-screen overflow-y-auto">
        <header class="flex justify-between items-center mb-10">
            <div>
                <h2 class="text-3xl font-black text-slate-900 tracking-tight italic uppercase">Access <span class="text-rose-700">Matrix</span></h2>
                <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest mt-1">Cross-Reference Permission Management</p>
            </div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- ASSIGNMENT FORM -->
            <div class="lg:col-span-1">
                <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 sticky top-0 animate__animated animate__fadeInLeft">
                    <h3 class="text-sm font-black text-slate-800 uppercase italic mb-6">Grant New Permission</h3>
                    <form method="POST" class="space-y-6">
                        <div>
                            <label class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] block mb-2">Select Role</label>
                            <select name="role_id" required class="w-full bg-slate-50 p-4 rounded-2xl border-none outline-none font-bold text-xs uppercase">
                                <?php while($r = $all_roles->fetch_assoc()): ?>
                                    <option value="<?php echo $r['id']; ?>"><?php echo $r['role_name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div>
                            <label class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] block mb-2">Authorized Category</label>
                            <select name="category_id" required class="w-full bg-slate-50 p-4 rounded-2xl border-none outline-none font-bold text-xs uppercase">
                                <?php while($c = $all_cats->fetch_assoc()): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo $c['category_name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <button type="submit" name="grant_access" class="w-full crimson-gradient py-5 rounded-2xl text-white text-[10px] font-black uppercase tracking-widest hover:scale-[1.02] transition shadow-lg shadow-rose-200">
                            Bind Permission
                        </button>
                    </form>
                </div>
            </div>

            <!-- PERMISSION TABLE -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-[2.5rem] overflow-hidden shadow-sm border border-slate-100 animate__animated animate__fadeIn">
                    <div class="p-6 border-b border-slate-50 flex justify-between items-center">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Active Links</span>
                        <input type="text" id="matrixSearch" placeholder="Search Role or Category..." class="bg-slate-50 px-4 py-2 rounded-xl text-[10px] font-bold outline-none border border-transparent focus:border-rose-300 w-64">
                    </div>
                    <table class="w-full text-left" id="matrixTable">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-8 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">Role Name</th>
                                <th class="px-8 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">Category Access</th>
                                <th class="px-8 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php while($row = $permissions->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-8 py-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-2 h-2 rounded-full bg-rose-600"></div>
                                        <span class="text-xs font-black text-slate-700 uppercase italic"><?php echo $row['role_name']; ?></span>
                                    </div>
                                </td>
                                <td class="px-8 py-5">
                                    <span class="px-4 py-1.5 bg-slate-900 text-white text-[9px] font-black rounded-lg uppercase tracking-wider">
                                        <i class="fa-solid fa-folder-open mr-2 text-rose-500"></i><?php echo $row['category_name']; ?>
                                    </span>
                                </td>
                                <td class="px-8 py-5 text-right">
                                    <a href="" 
                                       onclick="return confirm('Revoke this access right?')"
                                       class="text-slate-300 hover:text-rose-600 transition">
                                        <i class="fa-solid fa-link-slash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <script>
        // Real-time Search functionality
        document.getElementById('matrixSearch').addEventListener('keyup', function() {
            let filter = this.value.toUpperCase();
            let rows = document.querySelector("#matrixTable tbody").rows;
            
            for (let i = 0; i < rows.length; i++) {
                let role = rows[i].cells[0].textContent.toUpperCase();
                let cat = rows[i].cells[1].textContent.toUpperCase();
                if (role.indexOf(filter) > -1 || cat.indexOf(filter) > -1) {
                    rows[i].style.display = "";
                } else {
                    rows[i].style.display = "none";
                }      
            }
        });
    </script>
</body>
</html>