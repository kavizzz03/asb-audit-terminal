<?php
session_start();

// Security Check: Only Super Admins (Role 1)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: dashboard.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "asb_file_system");
if ($conn->connect_error) { die("Critical Connection Failure."); }

// --- CRUD OPERATIONS ---

// 1. Create / Update
if (isset($_POST['save_category'])) {
    $cat_name = trim($_POST['category_name']);
    $description = trim($_POST['description']);
    $cat_id = $_POST['cat_id'] ?? null;

    try {
        if (empty($cat_name)) throw new Exception("Category name is required.");

        if ($cat_id) {
            $stmt = $conn->prepare("UPDATE categories SET category_name = ?, description = ? WHERE id = ?");
            $stmt->bind_param("ssi", $cat_name, $description, $cat_id);
            $action = "updated";
        } else {
            $stmt = $conn->prepare("INSERT INTO categories (category_name, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $cat_name, $description);
            $action = "created";
        }

        if ($stmt->execute()) {
            $_SESSION['swal'] = ['type' => 'success', 'title' => 'ARCHIVE SYNCED', 'text' => "Category successfully $action."];
        } else {
            throw new Exception("Database error.");
        }
    } catch (Exception $e) {
        $_SESSION['swal'] = ['type' => 'error', 'title' => 'EXECUTION FAILED', 'text' => $e->getMessage()];
    }
    header("Location: category_mgmt.php");
    exit();
}

// 2. Delete Logic (Fixed)
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['swal'] = ['type' => 'success', 'title' => 'PURGE COMPLETE', 'text' => "Category removed from architecture."];
        }
    } catch (mysqli_sql_exception $e) {
        $_SESSION['swal'] = ['type' => 'error', 'title' => 'INTEGRITY LOCK', 'text' => "Cannot delete: Linked to active documents."];
    }
    header("Location: category_mgmt.php");
    exit();
}

$categories = $conn->query("SELECT * FROM categories ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ASB | Category Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; }
        .glass-panel { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.7); }
        .sidebar { background: #0f172a; }
        .crimson-btn { background: linear-gradient(135deg, #be123c 0%, #9f1239 100%); transition: all 0.3s; }
        .crimson-btn:hover { transform: scale(1.02); box-shadow: 0 10px 20px -5px rgba(190, 18, 60, 0.4); }
        
        /* SweetAlert Elite Styling */
        .swal2-popup { border-radius: 2.5rem !important; padding: 2rem !important; }
        .swal2-title { font-weight: 800 !important; color: #0f172a !important; font-style: italic !important; text-transform: uppercase !important; }
        .swal2-confirm { background: #be123c !important; border-radius: 1.2rem !important; font-weight: 800 !important; font-size: 11px !important; letter-spacing: 0.1em !important; padding: 1rem 2rem !important; }
    </style>
</head>
<body class="flex">

    <!-- Sidebar -->
    <div class="sidebar w-72 min-h-screen text-slate-400 p-6 hidden lg:block sticky top-0">
        <div class="mb-10 px-4">
            <h1 class="text-xl font-black text-white italic">ASB <span class="text-rose-600">GROUP</span></h1>
        </div>
        <nav class="space-y-1">
            <a href="dashboard.php" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/5 transition">
                <i class="fa-solid fa-house text-sm"></i> Dashboard
            </a>
            <a href="category_mgmt.php" class="flex items-center gap-3 p-3 bg-rose-600/10 text-rose-500 rounded-xl font-bold transition">
                <i class="fa-solid fa-folder-tree text-sm"></i> Categories
            </a>
            <a href="logout.php" class="flex items-center gap-3 p-3 rounded-xl hover:text-white transition mt-20">
                <i class="fa-solid fa-power-off text-sm"></i> Exit
            </a>
        </nav>
    </div>

    <main class="flex-1 p-8 lg:p-12">
        <div class="flex justify-between items-end mb-10">
            <div>
                <h2 class="text-3xl font-black text-slate-900 tracking-tight uppercase italic">Category <span class="text-rose-700">Architecture</span></h2>
                <p class="text-slate-400 text-xs font-bold tracking-widest mt-1 uppercase">Configure System Data Structure</p>
            </div>
            <button onclick="openModal()" class="crimson-btn text-white px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest flex items-center gap-2">
                <i class="fa-solid fa-plus"></i> Initialize Category
            </button>
        </div>

        <div class="glass-panel rounded-[2rem] shadow-sm overflow-hidden">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-900 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                        <th class="px-8 py-5">System ID</th>
                        <th class="px-8 py-5">Designation</th>
                        <th class="px-8 py-5">Description</th>
                        <th class="px-8 py-5 text-right">Operations</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php while($row = $categories->fetch_assoc()): ?>
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-8 py-6 font-black text-rose-700 text-sm">#<?php echo str_pad($row['id'], 3, '0', STR_PAD_LEFT); ?></td>
                        <td class="px-8 py-6 font-bold text-slate-800 uppercase tracking-tight"><?php echo $row['category_name']; ?></td>
                        <td class="px-8 py-6 text-slate-400 text-xs leading-relaxed max-w-xs truncate"><?php echo $row['description']; ?></td>
                        <td class="px-8 py-6 text-right space-x-2">
                            <button onclick='editCategory(<?php echo json_encode($row); ?>)' class="w-9 h-9 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-900 hover:text-white transition">
                                <i class="fa-solid fa-pen-to-square text-xs"></i>
                            </button>
                            <button onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo addslashes($row['category_name']); ?>')" class="w-9 h-9 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition">
                                <i class="fa-solid fa-trash-can text-xs"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- CRUD MODAL -->
    <div id="catModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-lg rounded-[2.5rem] shadow-2xl overflow-hidden animate__animated animate__zoomIn animate__faster">
            <div class="p-10">
                <div class="flex justify-between items-center mb-8">
                    <h3 id="modalTitle" class="text-xl font-black text-slate-900 uppercase italic">New <span class="text-rose-700">Category</span></h3>
                    <button onclick="closeModal()" class="text-slate-300 hover:text-slate-900 transition"><i class="fa-solid fa-xmark text-xl"></i></button>
                </div>

                <form method="POST" class="space-y-6">
                    <input type="hidden" name="cat_id" id="cat_id">
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Category Name</label>
                        <input type="text" name="category_name" id="category_name" required class="w-full px-5 py-4 rounded-2xl bg-slate-50 border border-slate-100 focus:border-rose-500 focus:bg-white outline-none font-bold text-slate-800 transition">
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Description Profile</label>
                        <textarea name="description" id="description" rows="4" class="w-full px-5 py-4 rounded-2xl bg-slate-50 border border-slate-100 focus:border-rose-500 focus:bg-white outline-none font-semibold text-slate-500 text-sm transition"></textarea>
                    </div>
                    <button type="submit" name="save_category" class="crimson-btn w-full py-5 rounded-2xl text-white font-black uppercase tracking-[0.2em] text-xs">Commit to Database</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('catModal');
        
        function openModal() {
            document.getElementById('modalTitle').innerHTML = 'New <span class="text-rose-700">Category</span>';
            document.getElementById('cat_id').value = '';
            document.querySelector('form').reset();
            modal.classList.remove('hidden');
        }

        function closeModal() { modal.classList.add('hidden'); }

        function editCategory(data) {
            document.getElementById('modalTitle').innerHTML = 'Edit <span class="text-rose-700">Category</span>';
            document.getElementById('cat_id').value = data.id;
            document.getElementById('category_name').value = data.category_name;
            document.getElementById('description').value = data.description;
            modal.classList.remove('hidden');
        }

        function confirmDelete(id, name) {
            Swal.fire({
                title: 'TERMINATE CATEGORY?',
                text: `You are about to purge "${name}" from the system architecture.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'CONFIRM PURGE',
                cancelButtonText: 'ABORT',
                background: '#fff',
                color: '#0f172a',
                iconColor: '#be123c',
                customClass: { confirmButton: 'swal2-confirm' }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `category_mgmt.php?delete=${id}`;
                }
            });
        }

        // Trigger Success/Error Alerts from PHP Session
        <?php if(isset($_SESSION['swal'])): ?>
            Swal.fire({
                title: '<?php echo $_SESSION['swal']['title']; ?>',
                text: '<?php echo $_SESSION['swal']['text']; ?>',
                icon: '<?php echo $_SESSION['swal']['type']; ?>',
                timer: 3000,
                showConfirmButton: false
            });
            <?php unset($_SESSION['swal']); ?>
        <?php endif; ?>
    </script>
</body>
</html>