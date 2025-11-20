<?php

namespace App\repository;
use PDO;
use App\model\User;
use PDOException;
use Exception;

readonly class UserRepository
{
    public function __construct(
        private readonly ?PDO $conn
    ){}

    private const string SQL_INSERT_USER = "INSERT INTO users (name_user, email_user, password_hash_user) VALUES (:name_user, :email_user, :password_hash_user)";
    private const string SQL_FETCH_USER = "SELECT * FROM users WHERE email_user = :email_user";
    private const string SQL_FETCH_ALL_USERS = "SELECT * FROM users";
    private const string SQL_UPDATE_USER_NAME = "UPDATE users SET name_user = :name_user WHERE id_user = :id_user";
    private const string SQL_DELETE_USER = "DELETE FROM users WHERE id_user = :id_user";

    public function registerUser(string $name, string $email, string $password): bool
    {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $this->conn->prepare(self::SQL_INSERT_USER);
            $stmt->execute([
                ":name_user" => $name,
                ":email_user" => $email,
                ":password_hash_user" => $password_hash
            ]);
            return true;
        } catch (PDOException $e) {
            if ($e->getCode() == '23000'){
                error_log("Error: Email already exists" . $e->getMessage());
            } else {
                error_log("Error: " . $e->getMessage());
            }
            return false;
        }
    }

    public function loginUser(string $email_user, string $password): ?User
    {
        if (empty($email_user) || empty($password)) {
            error_log("Error: Empty username or password");
            return null;
        }
        try {
            $stmt = $this->conn->prepare(self::SQL_FETCH_USER);
            $stmt->execute([
                ":email_user" => $email_user,
            ]);
            $userData = $stmt->fetch();
            if (!$userData || !password_verify($password, $userData["password_hash_user"])) {
                error_log("Error: Wrong username or password or user does not exist");
                return null;
            }
            return new User($userData['id_user'], $userData["name_user"], $userData["email_user"]);
        } catch (PDOException $e) {
            error_log("Failed to login: " . $e->getMessage());
            return null;
        }
    }

    public function getAllUsers(): ?array
    {
        $stmt = $this->conn->prepare(self::SQL_FETCH_ALL_USERS);
        try {
            $stmt->execute();
            $rows = $stmt->fetchAll();
            if (empty($rows)) {
                error_log("No users found");
                return null;
            }
            return $rows;
        } catch (PDOException $e) {
            error_log("Failed to get users: " . $e->getMessage());
            return null;
        }
    }

    public function updateUserName($id, $newName): bool
    {
        $stmt = $this->conn->prepare(self::SQL_UPDATE_USER_NAME);
        try {
            $stmt->execute([
                ":name_user" => $newName,
                ":id_user" => $id,
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Failed to update user name: " . $e->getMessage());
            return false;
        }
    }
    public function deleteUser($id_user): bool
    {
        try {
            $stmt = $this->conn->prepare(self::SQL_DELETE_USER);
            $stmt->execute([
                ":id_user" => $id_user
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Failed to delete user: " . $e->getMessage());
            return false;
        }
    }
}