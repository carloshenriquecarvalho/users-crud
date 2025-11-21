<?php

namespace App\controller;

use App\repository\UserRepository;
use PDOException;

class UserController // Não precisa ser readonly
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {}

    // Ação: Listar todos os usuários
    public function index(): void
    {
        try {
            $users = $this->userRepository->getAllUsers();
            $this->jsonResponse(["success" => true, "users" => $users]);
        } catch (PDOException $e) {
            $this->handleError($e);
        }
    }

    // Ação: Registrar um novo usuário
    public function register(array $data): void
    {
        if (empty($data['email']) || empty($data['name']) || empty($data['password'])) {
            $this->jsonResponse(["success" => false, "message" => "Failed: missing data."], 400); // 400 Bad Request
            return;
        }
        try {
            $success = $this->userRepository->registerUser($data["name"], $data["email"], $data["password"]);
            $this->jsonResponse([
                "success" => $success,
                "message" => $success ? "User registered successfully" : "Failed to register user",
            ], $success ? 201 : 500); // 201 Created
        } catch (PDOException $e) {
            $this->handleError($e);
        }
    }

    // Ação: Fazer login
    public function login(array $data): void
    {
        if (empty($data['email']) || empty($data['password'])) {
            $this->jsonResponse(["success" => false, "message" => "Failed: missing data."], 400);
            return;
        }

        try {
            $user = $this->userRepository->loginUser($data["email"], $data["password"]);
            if (!$user) {
                $this->jsonResponse(["success" => false, "message" => "Wrong credentials"], 401); // 401 Unauthorized
                return;
            }
            $this->jsonResponse(["success" => true, "user" => $user]);
        } catch (PDOException $e) {
            $this->handleError($e);
        }
    }

    // Ação: Atualizar um usuário
    public function update(int $id, array $data): void
    {
        if (empty($data["name"])) {
            $this->jsonResponse(["success" => false, "message" => "Failed: missing name."], 400);
            return;
        }

        try {
            $success = $this->userRepository->updateUserName($id, $data["name"]);
            $this->jsonResponse([
                "success" => $success,
                "message" => $success ? "Name updated successfully." : "User not found or name is the same."
            ]);
        } catch (PDOException $e) {
            $this->handleError($e);
        }
    }

    // Ação: Deletar um usuário
    public function delete(int $id): void
    {
        try {
            $success = $this->userRepository->deleteUser($id);
            $this->jsonResponse([
                "success" => $success,
                "message" => $success ? "User deleted successfully." : "User not found."
            ]);
        } catch (PDOException $e) {
            $this->handleError($e);
        }
    }

    // --- Funções de Apoio ---
    private function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode );
        header("Content-Type: application/json");
        echo json_encode($data);
    }

    private function handleError(PDOException $e): void
    {
        error_log("PDOException: " . $e->getMessage());
        $this->jsonResponse(["success" => false, "message" => "Internal server error"], 500);
    }
}
