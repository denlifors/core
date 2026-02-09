<?php
/**
 * Helper function to ensure is_admin column exists
 * This is called automatically when needed
 */

function ensureAdminFieldExists($db) {
    try {
        // Check if is_admin column exists
        $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'is_admin'");
        $columnExists = $stmt->fetch();
        
        if (!$columnExists) {
            // Add is_admin column
            $db->exec("ALTER TABLE users ADD COLUMN is_admin BOOLEAN DEFAULT FALSE AFTER role");
            
            // Migrate existing admins (users with role = 'admin')
            // First, check if 'admin' is still in ENUM
            $stmt = $db->query("SHOW COLUMNS FROM users WHERE Field = 'role'");
            $column = $stmt->fetch();
            
            if ($column && strpos($column['Type'], "'admin'") !== false) {
                // Find and migrate existing admins
                $stmt = $db->query("SELECT id, role FROM users WHERE role = 'admin'");
                $adminUsers = $stmt->fetchAll();
                
                foreach ($adminUsers as $admin) {
                    $newRole = ($admin['role'] === 'partner') ? 'partner' : 'user';
                    $updateStmt = $db->prepare("UPDATE users SET is_admin = TRUE, role = :role WHERE id = :id");
                    $updateStmt->execute([
                        ':role' => $newRole,
                        ':id' => $admin['id']
                    ]);
                }
            }
            
            return true; // Column was created
        }
        
        return false; // Column already exists
    } catch (Exception $e) {
        // If error occurs, return false - we'll handle it gracefully
        return false;
    }
}

