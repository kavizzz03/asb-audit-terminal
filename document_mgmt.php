<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: dashboard.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "asb_file_system");

// --- CRUD LOGIC ---

// 1. Create Document with File Upload
if (isset($_POST['add_doc'])) {
    $title = $_POST['title'];
    $date = $_POST['doc_date'];
    $number = $_POST['doc_number'];
    $cat_id = $_POST['category_id'];
    $branch_id = $_POST['branch_id'];

    if (isset($_FILES['doc_file']) && $_FILES['doc_file']['type'] == 'application/pdf') {
        $target_dir = "uploads/docs/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_name = time() . "_" . basename($_FILES["doc_file"]["name"]);
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["doc_file"]["tmp_name"], $target_file)) {
            $stmt = $conn->prepare("INSERT INTO documents (title, doc_date, doc_number, file_path, category_id, branch_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssii", $title, $date, $number, $target_file, $cat_id, $branch_id);
            $stmt->execute();
        }
    }
}

// 2. Delete Document & Physical File
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $res = $conn->query("SELECT file_path FROM documents WHERE id = $id");
    if ($row = $res->fetch_assoc()) {
        if (file_exists($row['file_path'])) {
            unlink($row['file_path']); // Deletes actual file
        }
    }
    $conn->query("DELETE FROM documents WHERE id = $id");
    header("Location: document_mgmt.php");
}

// Fetch Data
$docs = $conn->query("SELECT d.*, c.category_name, b.branch_name 
                      FROM documents d 
                      LEFT JOIN categories c ON d.category_id = c.id 
                      LEFT JOIN branches b ON d.branch_id = b.id");

$cats = $conn->query("SELECT id, category_name FROM categories");
$brs = $conn->query("SELECT id, branch_name FROM branches");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Registry Terminal | ASB Group</title>
	 <link rel="icon" type="image/png" href="logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; }
        .crimson-gradient { background: linear-gradient(135deg, #be123c 0%, #7f1d1d 100%); }
        .drop-zone { border: 2px dashed #e2e8f0; transition: all 0.3s; }
        .drop-zone--over { border-color: #be123c; background: #fff1f2; }
    </style>
</head>
<body class="flex">

    <!-- Sidebar -->
    <div class="w-80 min-h-screen bg-[#0f172a] p-6 text-slate-400 hidden lg:block">
        <div class="mb-12 px-4 italic font-black text-white text-xl">ASB <span class="text-rose-600">GROUP</span></div>
        <nav class="space-y-2">
            <a href="dashboard.php" class="flex items-center gap-3 p-3 hover:bg-white/5 rounded-xl transition text-sm"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <a href="document_mgmt.php" class="flex items-center gap-3 p-3 bg-rose-600/10 text-rose-500 rounded-xl font-bold text-sm"><i class="fa-solid fa-file-shield"></i> Document Master</a>
            <a href="user_mgmt.php" class="flex items-center gap-3 p-3 hover:bg-white/5 rounded-xl transition text-sm"><i class="fa-solid fa-user-gear"></i> Users</a>
        </nav>
    </div>

    <main class="flex-1 p-10 h-screen overflow-y-auto">
        <header class="flex justify-between items-center mb-10">
            <div>
                <h2 class="text-3xl font-black text-slate-900 tracking-tight italic uppercase">Document <span class="text-rose-700">Vault</span></h2>
                <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest mt-1">Binary Archive & PDF Security</p>
            </div>
            <button onclick="openModal()" class="crimson-gradient px-8 py-4 rounded-2xl text-white text-[10px] font-black uppercase tracking-widest shadow-xl">
                <i class="fa-solid fa-cloud-arrow-up mr-2"></i> Upload New Archive
            </button>
        </header>

        <div class="grid grid-cols-1 gap-4">
            <?php while($row = $docs->fetch_assoc()): ?>
            <div class="bg-white p-6 rounded-[2rem] border border-slate-100 flex items-center justify-between hover:shadow-lg transition group">
                <div class="flex items-center gap-6">
                    <div class="w-14 h-14 bg-slate-50 rounded-2xl flex items-center justify-center text-rose-600 group-hover:bg-rose-600 group-hover:text-white transition-all duration-500">
                        <i class="fa-solid fa-file-pdf text-2xl"></i>
                    </div>
                    <div>
                        <h4 class="font-black text-slate-800 uppercase italic tracking-tight"><?php echo $row['title']; ?></h4>
                        <div class="flex gap-4 mt-1">
                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest"><i class="fa-solid fa-hashtag mr-1"></i> <?php echo $row['doc_number']; ?></span>
                            <span class="text-[9px] font-black text-rose-500 uppercase tracking-widest"><i class="fa-solid fa-calendar mr-1"></i> <?php echo $row['doc_date']; ?></span>
                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest"><i class="fa-solid fa-folder-open mr-1"></i> <?php echo $row['category_name']; ?></span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <a href="<?php echo $row['file_path']; ?>" target="_blank" class="px-6 py-2 bg-slate-900 text-white text-[9px] font-black uppercase rounded-xl hover:bg-rose-700 transition">View PDF</a>
                    <a href="" onclick="return confirm('Wipe document and physical file?')" class="p-3 text-slate-300 hover:text-rose-600 transition"><i class="fa-solid fa-trash-can"></i></a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </main>

    <!-- UPLOAD MODAL -->
    <div id="uploadModal" class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white w-full max-w-2xl rounded-[3rem] p-10 shadow-2xl animate__animated animate__fadeInUp">
            <h3 class="text-2xl font-black text-slate-900 italic uppercase mb-8">Archive <span class="text-rose-700">Initialization</span></h3>
            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <input type="text" name="title" placeholder="Document Title" required class="w-full bg-slate-50 p-4 rounded-2xl border-none outline-none font-bold text-sm uppercase">
                    <input type="text" name="doc_number" placeholder="Ref Number" required class="w-full bg-slate-50 p-4 rounded-2xl border-none outline-none font-bold text-sm">
                </div>
                
                <div class="grid grid-cols-3 gap-4">
                    <input type="date" name="doc_date" required class="w-full bg-slate-50 p-4 rounded-2xl border-none outline-none font-bold text-sm">
                    <select name="category_id" class="w-full bg-slate-50 p-4 rounded-2xl border-none outline-none font-bold text-sm uppercase">
                        <?php while($c = $cats->fetch_assoc()) echo "<option value='{$c['id']}'>{$c['category_name']}</option>"; ?>
                    </select>
                    <select name="branch_id" class="w-full bg-slate-50 p-4 rounded-2xl border-none outline-none font-bold text-sm uppercase">
                        <?php while($b = $brs->fetch_assoc()) echo "<option value='{$b['id']}'>{$b['branch_name']}</option>"; ?>
                    </select>
                </div>

                <!-- Drag & Drop Zone -->
                <div id="dropZone" class="drop-zone rounded-[2rem] p-12 text-center cursor-pointer">
                    <i class="fa-solid fa-file-pdf text-4xl text-slate-200 mb-4"></i>
                    <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Drag PDF here or click to browse</p>
                    <p id="fileName" class="text-rose-600 text-xs font-bold mt-2"></p>
                    <input type="file" name="doc_file" id="fileInput" accept=".pdf" class="hidden" required>
                </div>

                <div class="flex gap-4">
                    <button type="submit" name="add_doc" class="flex-1 crimson-gradient py-5 rounded-2xl text-white text-[10px] font-black uppercase tracking-widest">Commit to Vault</button>
                    <button type="button" onclick="closeModal()" class="px-10 py-5 bg-slate-100 rounded-2xl text-slate-500 text-[10px] font-black uppercase">Abort</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('uploadModal');
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const fileNameDisp = document.getElementById('fileName');

        function openModal() { modal.classList.remove('hidden'); }
        function closeModal() { modal.classList.add('hidden'); }

        dropZone.onclick = () => fileInput.click();
        
        fileInput.onchange = () => {
            if(fileInput.files.length) fileNameDisp.innerText = "Selected: " + fileInput.files[0].name;
        };

        dropZone.ondragover = (e) => { e.preventDefault(); dropZone.classList.add('drop-zone--over'); };
        dropZone.ondragleave = () => dropZone.classList.remove('drop-zone--over');
        dropZone.ondrop = (e) => {
            e.preventDefault();
            dropZone.classList.remove('drop-zone--over');
            if(e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                fileNameDisp.innerText = "Dropped: " + e.dataTransfer.files[0].name;
            }
        };
    </script>
</body>
</html>