<?php
class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $first_name;
    public $last_name;
    public $email;
    public $password;
    public $role;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Check if email exists and load user data
    public function emailExists() {
        // Secure query using named placeholder
        $query = "SELECT id, first_name, last_name, password, role 
                  FROM " . $this->table_name . " 
                  WHERE email = :email 
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        
        // Sanitize input
        $this->email = htmlspecialchars(strip_tags($this->email));
        
        // Bind parameter
        $stmt->bindParam(':email', $this->email);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->first_name = $row['first_name'];
            $this->last_name = $row['last_name'];
            $this->password = $row['password']; // Stores the hash
            $this->role = $row['role'];
            return true;
        }
        return false;
    }

    // Register new user
    public function register() {
        $query = "INSERT INTO " . $this->table_name . "
                SET
                    first_name = :first_name,
                    last_name = :last_name,
                    email = :email,
                    password = :password,
                    role = :role,
                    created_at = NOW()";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs (XSS Protection)
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        
        // Hash password (Security critical)
        // Ensure password is hashed before saving
        if (password_needs_rehash($this->password, PASSWORD_BCRYPT) || strlen($this->password) < 60) {
             $this->password = password_hash($this->password, PASSWORD_BCRYPT);
        }

        // Bind parameters
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
}
?>