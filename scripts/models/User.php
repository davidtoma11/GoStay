<?php
class User {
    private $conn;
    private $table_name = "users";

    // User Properties
    public $id;
    public $first_name;
    public $last_name;
    public $email;
    public $password;
    public $role;
    public $reset_code;
    public $reset_expires_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // 1. Check if email exists (Used for Login & Signup)
    public function emailExists() {
        $query = "SELECT id, first_name, last_name, password, role
                  FROM " . $this->table_name . " 
                  WHERE email = :email 
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        
        $this->email = htmlspecialchars(strip_tags($this->email));
        $stmt->bindParam(':email', $this->email);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->first_name = $row['first_name'];
            $this->last_name = $row['last_name'];
            $this->password = $row['password'];
            $this->role = $row['role'];
            return true;
        }
        return false;
    }

    // 2. Create User (Called ONLY after Session verification)
    public function create() {
        $this->role = 'client';
        $query = "INSERT INTO " . $this->table_name . "
                  SET
                    first_name = :first_name,
                    last_name = :last_name,
                    email = :email,
                    password = :password,
                    role = :role,
                    created_at = NOW()";

        $stmt = $this->conn->prepare($query);

        // Sanitize input
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        
        // Password comes already hashed from Session
        
        $stmt->bindParam(':first_name', $this->first_name);
        $stmt->bindParam(':last_name', $this->last_name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $this->password);
        $stmt->bindParam(':role', $this->role);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // --- FORGOT PASSWORD METHODS ---

    // 3. Save Reset Code
    public function setResetToken($code) {
        $query = "UPDATE " . $this->table_name . " 
                  SET reset_code = :code, reset_expires_at = DATE_ADD(NOW(), INTERVAL 5 MINUTE) 
                  WHERE email = :email";

        $stmt = $this->conn->prepare($query);
        
        $code = htmlspecialchars(strip_tags($code));
        $this->email = htmlspecialchars(strip_tags($this->email));

        $stmt->bindParam(":code", $code);
        $stmt->bindParam(":email", $this->email);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // 4. Reset Password
    public function resetPassword($code, $new_password) {
        $query = "SELECT id, reset_expires_at FROM " . $this->table_name . " 
                  WHERE email = :email AND reset_code = :code LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":code", $code);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            // Check expiry
            if(new DateTime() > new DateTime($row['reset_expires_at'])) {
                return "expired";
            }

            // Update Password
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            
            $updateQuery = "UPDATE " . $this->table_name . " 
                            SET password = :password, reset_code = NULL, reset_expires_at = NULL 
                            WHERE email = :email";
            
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(":password", $hashed_password);
            $updateStmt->bindParam(":email", $this->email);

            if($updateStmt->execute()) {
                return "success";
            }
        }
        return "invalid";
    }
}
?>