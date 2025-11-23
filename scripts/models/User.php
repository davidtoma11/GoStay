<?php
class User {
    private $conn;
    private $table_name = "users";
    public $id;
    public $email;
    public $password;
    public $role;
    public $first_name;
    public $last_name;

    public function __construct($db) {
        $this->conn = $db;
    }

    //Login user with email and password
    public function login($email, $password) {
        $query = "SELECT id, email, password, role, first_name, last_name 
                  FROM " . $this->table_name . " 
                  WHERE email = ? 
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verify password
            if(password_verify($password, $row['password'])) {
                // Set user properties
                $this->id = $row['id'];
                $this->email = $row['email'];
                $this->role = $row['role'];
                $this->first_name = $row['first_name'];
                $this->last_name = $row['last_name'];
                return true;
            }
        }
        return false;
    }

    // Register new user
    public function register() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET email=:email, password=:password, 
                      first_name=:first_name, last_name=:last_name, role='client'";
        
        $stmt = $this->conn->prepare($query);

        // Sanitize input
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));
        
        // Hash password for security
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);

        // Bind parameters
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Check if email already exists
    public function emailExists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->email);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}
?>