# 📖 README.md - ASB Group Document Management System

```markdown
# 🏢 ASB GROUP - Enterprise Document Management System

![Version](https://img.shields.io/badge/version-2.0-red)
![PHP](https://img.shields.io/badge/PHP-8.0+-blue)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-orange)
![License](https://img.shields.io/badge/License-Proprietary-darkred)

---

## 📋 SYSTEM OVERVIEW

The **ASB Group Enterprise Document Management System** is a comprehensive web-based solution for secure document storage, role-based access control, user management, and audit tracking. Built with PHP and MySQL, this system provides a professional interface for managing organizational documents with bilingual support (English/Sinhala).

### 🎯 Key Features

| Feature | Description |
|---------|-------------|
| 🔐 **Role-Based Access** | Three-tier access control (Super Admin, Editor, Branch Manager) |
| 📄 **Document Management** | Upload, view, download PDF documents with unique reference numbers |
| 👥 **User Management** | Create, edit, delete users with role and branch assignments |
| 🏢 **Branch Management** | Multi-branch support with protected global branch |
| 📂 **Category System** | Organize documents into customizable categories |
| 🔑 **Permission Assignment** | Grant category access to specific roles |
| 📊 **Audit Trail** | Track all document interactions and login attempts |
| 📱 **SMS Notifications** | Automatic notifications for new document uploads |
| 🌐 **Bilingual UI** | Full English and Sinhala language support |
| 🖨️ **Printable Reports** | Export audit logs and interaction reports |

---

## 🗄️ DATABASE STRUCTURE

### Database Name: `asb_file_system`

The database consists of **8 interconnected tables** that form a complete document management ecosystem.

---

### Table 1: `users` - User Accounts

```sql
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `contact_number` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `role_id` (`role_id`),
  KEY `branch_id` (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Purpose:** Stores all system user accounts with their roles and branch assignments.

**Key Columns Explained:**
- `id` - Unique identifier for each user
- `username` - Login credential (must be unique)
- `name` - Full name of the user (supports Sinhala)
- `password` - User's login password
- `contact_number` - Mobile number for SMS notifications
- `email` - Email address for communications
- `role_id` - Links to roles table (determines permissions)
- `branch_id` - Links to branches table (determines branch access)

---

### Table 2: `roles` - User Permission Levels

```sql
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Purpose:** Defines the three user permission levels in the system.

**Default Data:**
```sql
INSERT INTO `roles` (`id`, `role_name`) VALUES
(1, 'Super Admin'),      -- Full system access
(2, 'Editor'),           -- Document view/download only
(3, 'BRANCH MANAGER');   -- Branch-specific access
```

**Role Capabilities:**
- **Super Admin (ID 1):** Complete system control, user management, document upload
- **Editor (ID 2):** Can view and download documents only
- **Branch Manager (ID 3):** Can access documents assigned to their specific branch

---

### Table 3: `branches` - Company Locations

```sql
CREATE TABLE `branches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `branch_name` varchar(100) NOT NULL,
  `branch_code` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `branch_code` (`branch_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Purpose:** Stores all company branch locations.

**Key Columns:**
- `branch_name` - Name of the branch (e.g., "Head Office")
- `branch_code` - Unique code identifier for the branch

**⚠️ Protected Branch:** `id = 3` (named "all") is system protected - cannot be deleted or modified. This serves as the global branch for organization-wide documents.

---

### Table 4: `categories` - Document Classification

```sql
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Purpose:** Organizes documents into logical groups for easier access.

**Key Columns:**
- `category_name` - Name of the category (supports Sinhala)
- `description` - Detailed explanation of what belongs in this category

**Example Data:**
- Financial Reports
- HR Documents
- Standing Orders
- Sale Reports

---

### Table 5: `documents` - Central Document Repository

```sql
CREATE TABLE `documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `doc_date` date DEFAULT NULL,
  `doc_number` varchar(50) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `doc_number` (`doc_number`),
  KEY `category_id` (`category_id`),
  KEY `branch_id` (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Purpose:** Stores all uploaded documents and their metadata.

**Key Columns Explained:**
- `title` - Document name/title (supports Sinhala)
- `doc_date` - Date associated with the document
- `doc_number` - Unique reference number (MUST be unique)
- `file_path` - Server path to the uploaded PDF file
- `category_id` - Which category this document belongs to
- `branch_id` - Which branch owns this document

**Foreign Key Rules:**
- If a category has documents, that category CANNOT be deleted
- If a branch has documents, that branch CANNOT be deleted

---

### Table 6: `role_category_access` - Permission Bridge

```sql
CREATE TABLE `role_category_access` (
  `role_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  PRIMARY KEY (`role_id`,`category_id`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Purpose:** Many-to-many relationship table that determines which roles can access which categories.

**How It Works:**
- Each row represents "Role X can access Category Y"
- Super Admin (Role 1) typically has access to ALL categories
- Editors might only access specific categories
- Branch Managers access categories relevant to their branch

**Example:**
```sql
-- Super Admin can access all categories
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5)

-- Editor can access HR and Sale Reports only
(2, 2), (2, 3)
```

---

### Table 7: `document_interactions` - Activity Log

```sql
CREATE TABLE `document_interactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `doc_id` int(11) NOT NULL,
  `doc_name` varchar(255) NOT NULL,
  `category_name` varchar(255) NOT NULL,
  `interaction_type` enum('VIEW','DOWNLOAD') DEFAULT 'VIEW',
  `clicked_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_doc` (`user_id`,`doc_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Purpose:** Tracks every time a user views or downloads a document for audit purposes.

**⚠️ IMPORTANT:** This table has NO foreign key constraints! This is intentional so that audit logs remain even if users or documents are deleted from the system.

**What Gets Logged:**
- Which user performed the action (ID and Name)
- Which document was accessed (ID and Name)
- Which category the document belongs to
- Type of action (VIEW or DOWNLOAD)
- Exact date and time of the action

---

### Table 8: `access_audit_logs` - Login Attempts

```sql
CREATE TABLE `access_audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `operator_name` varchar(100) NOT NULL,
  `event_time` datetime NOT NULL,
  `status` enum('AUTHORIZED','DENIED') NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Purpose:** Records every login attempt (successful or failed) for security monitoring.

**What Gets Logged:**
- Username that attempted login
- Timestamp of the attempt
- Whether login was AUTHORIZED or DENIED
- IP address of the user
- Browser/device information (User Agent)

---

## 🔗 DATABASE RELATIONSHIP EXPLANATION

### How Tables Connect

```
                    ┌─────────────┐
                    │    roles    │ (Defines permission levels)
                    └──────┬──────┘
                           │
              ┌────────────┼────────────┐
              │            │            │
              ▼            ▼            ▼
        ┌──────────┐  ┌─────────────────────┐
        │  users   │  │ role_category_access│
        └────┬─────┘  └──────────┬──────────┘
             │                   │
             │                   ▼
             │            ┌─────────────┐
             │            │ categories  │
             │            └──────┬──────┘
             │                   │
             │                   ▼
             │            ┌─────────────┐
             │            │  documents  │
             │            └──────┬──────┘
             │                   │
             │                   ▼
             └─────────────────► document_interactions (Audit log - NO foreign key)
```

### Explained Relationship Flow

1. **Users belong to Roles** - Each user has one role (Super Admin, Editor, etc.)
2. **Users belong to Branches** - Each user is assigned to one branch
3. **Roles have access to Categories** - Through role_category_access table
4. **Categories contain Documents** - Each document belongs to one category
5. **Documents belong to Branches** - Each document is owned by one branch
6. **Users interact with Documents** - Logged in document_interactions table

### Why No Foreign Keys on document_interactions?

The document_interactions table intentionally lacks foreign key constraints so that:

1. Audit history remains even if users are deleted
2. Document interaction logs persist after document removal
3. Historical data stays intact for compliance purposes
4. No cascade delete issues affect audit trails

---

## 🔐 FOREIGN KEY CONSTRAINTS SUMMARY

| Constraint Name | Source Table | Source Column | Target Table | Action |
|----------------|--------------|---------------|--------------|--------|
| users_ibfk_1 | users | role_id | roles(id) | RESTRICT |
| users_ibfk_2 | users | branch_id | branches(id) | RESTRICT |
| documents_ibfk_1 | documents | category_id | categories(id) | RESTRICT |
| documents_ibfk_2 | documents | branch_id | branches(id) | RESTRICT |
| role_category_access_ibfk_1 | role_category_access | role_id | roles(id) | CASCADE |
| role_category_access_ibfk_2 | role_category_access | category_id | categories(id) | CASCADE |

### What RESTRICT Means:
- Cannot delete a record that is referenced elsewhere
- Example: Cannot delete a category if documents exist in it

### What CASCADE Means:
- Deleting a role also deletes all its category access entries
- Example: Delete role → All role_category_access entries for that role are removed

---

## 📂 COMPLETE SQL SCHEMA

### Run this to create the entire database:

```sql
-- Create Database
CREATE DATABASE IF NOT EXISTS `asb_file_system`;
USE `asb_file_system`;

-- 1. Roles Table
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Branches Table
CREATE TABLE `branches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `branch_name` varchar(100) NOT NULL,
  `branch_code` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `branch_code` (`branch_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Categories Table
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Users Table
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `contact_number` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `role_id` (`role_id`),
  KEY `branch_id` (`branch_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  CONSTRAINT `users_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Role Category Access Table
CREATE TABLE `role_category_access` (
  `role_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  PRIMARY KEY (`role_id`,`category_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `role_category_access_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_category_access_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Documents Table
CREATE TABLE `documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `doc_date` date DEFAULT NULL,
  `doc_number` varchar(50) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `doc_number` (`doc_number`),
  KEY `category_id` (`category_id`),
  KEY `branch_id` (`branch_id`),
  CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `documents_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Document Interactions Table
CREATE TABLE `document_interactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `doc_id` int(11) NOT NULL,
  `doc_name` varchar(255) NOT NULL,
  `category_name` varchar(255) NOT NULL,
  `interaction_type` enum('VIEW','DOWNLOAD') DEFAULT 'VIEW',
  `clicked_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_doc` (`user_id`,`doc_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_doc` (`doc_id`),
  KEY `idx_category` (`category_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Access Audit Logs Table
CREATE TABLE `access_audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `operator_name` varchar(100) NOT NULL,
  `event_time` datetime NOT NULL,
  `status` enum('AUTHORIZED','DENIED') NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 🚀 INITIAL DATA (Required for System to Work)

```sql
-- Insert Roles
INSERT INTO `roles` (`id`, `role_name`) VALUES
(1, 'Super Admin'),
(2, 'Editor'),
(3, 'BRANCH MANAGER');

-- Insert Branches (ID 3 is system protected - DO NOT DELETE)
INSERT INTO `branches` (`id`, `branch_name`, `branch_code`) VALUES
(1, 'Head office', '001'),
(2, 'panadura', '002'),
(3, 'all', '0000'),
(4, 'Glamour Gate', '004'),
(5, 'ambalangoda', '003');

-- Insert Categories
INSERT INTO `categories` (`id`, `category_name`, `description`) VALUES
(1, 'Financial Reports', 'Monthly and annual financial statements 2026'),
(2, 'HR Documents', 'Employee contracts and policy updates'),
(3, 'Sale Report 2026', '2026 April Season report'),
(4, 'Standing Orders', 'Gm Orders'),
(5, 'පරීක්ෂා කිරීම ගොනුව', 'පරීක්ෂා කිරීම 1');

-- Insert Default User (Super Admin)
INSERT INTO `users` (`id`, `username`, `name`, `password`, `contact_number`, `email`, `role_id`, `branch_id`) VALUES
(1, 'kavindu_dev', 'Kavindu', 'admin123', '94740890730', 'kavizzn@gmail.com', 1, 1);

-- Assign Category Access for Super Admin (Access to all)
INSERT INTO `role_category_access` (`role_id`, `category_id`) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5);
```

---

## 🛡️ SYSTEM PROTECTIONS (HARDCODED)

### Protected Records that CANNOT be Deleted/Modified

**1. Super Admin User (ID = 1)**
```php
// In user_mgmt.php
if ($id == 1) {
    // Cannot delete or modify
}
```

**2. Global Branch (ID = 3)**
```php
// In branch_mgmt.php
if ($id == 3) {
    // Cannot delete or modify
}
```

**Why these protections exist:**
- Ensures at least one admin always exists
- Global branch serves as fallback for system-wide documents
- Prevents accidental system lockout

---

## 📊 FOREIGN KEY CONSTRAINT BEHAVIOR

### What Happens When You Try to Delete:

| Action | Result | Reason |
|--------|--------|--------|
| Delete Category with Documents | ❌ BLOCKED | Documents reference this category |
| Delete Category with Role Access | ❌ BLOCKED | role_category_access references it |
| Delete Branch with Users | ❌ BLOCKED | Users reference this branch |
| Delete Branch with Documents | ❌ BLOCKED | Documents reference this branch |
| Delete Role with Users | ❌ BLOCKED | Users reference this role |
| Delete Role with Category Access | ✅ ALLOWED | Cascade deletes role_category_access entries |
| Delete User with Interactions | ✅ ALLOWED | No foreign key constraint on interactions |

---

## 🔧 QUICK COMMANDS FOR DEVELOPMENT

### Reset Database (Development Only)
```sql
DROP DATABASE asb_file_system;
CREATE DATABASE asb_file_system;
USE asb_file_system;
-- Then run all CREATE TABLE and INSERT statements above
```

### Check Constraint Violations
```sql
-- Find categories that cannot be deleted
SELECT c.id, c.category_name, COUNT(d.id) as doc_count
FROM categories c
LEFT JOIN documents d ON c.id = d.category_id
GROUP BY c.id
HAVING doc_count > 0;

-- Find branches that cannot be deleted
SELECT b.id, b.branch_name, 
       COUNT(DISTINCT u.id) as user_count,
       COUNT(DISTINCT d.id) as doc_count
FROM branches b
LEFT JOIN users u ON b.id = u.branch_id
LEFT JOIN documents d ON b.id = d.branch_id
GROUP BY b.id
HAVING user_count > 0 OR doc_count > 0;
```

### Backup Command
```sql
mysqldump -u root -p asb_file_system > backup_$(date +%Y%m%d).sql
```

---

## 📝 NOTES FOR DEVELOPERS

### Sinhala Language Support
- Database charset: `utf8mb4` (supports all Unicode characters)
- HTML meta: `<meta charset="UTF-8">`
- Google Font: `Noto Sans Sinhala`
- All text fields support Sinhala input/output

### File Upload Settings
- Maximum file size controlled by `php.ini`:
  - `upload_max_filesize = 10M`
  - `post_max_size = 10M`
- Only PDF files accepted (mime type: `application/pdf`)

### Session Configuration
- Timezone: `Asia/Colombo`
- Session timeout: Default PHP setting (24 minutes)

### Security Recommendations for Production
1. Hash passwords using `password_hash()`
2. Use HTTPS
3. Implement CSRF tokens
4. Add rate limiting for login attempts
5. Move database credentials to config file outside webroot
6. Implement proper error logging (not display to users)

---

*This documentation is for developers maintaining the ASB Group Document Management System.*
```

---

## 📄 HOW TO USE THIS FILE

1. **Copy all the code above** (from the first ```markdown to the last ```)

2. **Save as `README.md`** in your project root directory

3. **View on GitHub/GitLab** - It will render beautifully with all formatting

4. **For local viewing** - Use any Markdown viewer or open in VS Code with preview

---

## 📌 FILE STRUCTURE FOR PROJECT

```
your-project-folder/
│
├── README.md          ← THIS FILE (place in root)
│
├── index.php
├── dashboard.php
├── [all other PHP files]
│
├── css/
│   └── [all CSS files]
│
└── uploads/
    └── docs/
```

---

**This README contains complete database structure, relationships, foreign key explanations, and setup instructions for any developer who needs to understand or maintain this system.**