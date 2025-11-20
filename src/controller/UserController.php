<?php

namespace App\controller;
use App\repository\UserRepository;
use PDOException;

readonly class UserController
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {}
    private function handleError(PDOException $e): void
    {
        error_log("PDOException: " . $e->getMessage());
        echo json_encode(["success" => false, "message" => "Internal server error"]);
    }

    public function handleRegisterUserRequest(): void
    {
        header("Content-Type: application/json");
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['email']) || empty($data['name']) || empty($data['password'])) {
            echo json_encode(["success" => false, "message" => "Failed: missing data."]);
            return;
        }
        try {
            $success = $this->userRepository->registerUser(
                $data["name"],
                $data["email"],
                $data["password"]
            );
            echo json_encode([
                "success" => $success,
                "message" => $success ? "Successful" : "Failed",
            ]);
        } catch (PDOException $e) {
            $this->handleError($e);
        }

    }

    public function handleUserLoginRequest(): void
    {
        header("Content-Type: application/json");

        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['email']) || empty($data['password'])) {
            error_log("Error: missing data");
            echo json_encode(["success" => false, "message" => "Failed: missing data."]);
            return;
        }

        try {
            $user = $this->userRepository->loginUser(
                $data["email"],
                $data["password"]
            );

            if (!$user) {
                echo json_encode([
                    "success" => false,
                    "message" => "Wrong credentials"
                ]);
                return;
            }

            echo json_encode([
                "success" => true,
                "user" => [
                    "id" => $user->id,
                    "name" => $user->name,
                    "email" => $user->email
                ]
            ]);
        } catch (PDOException $e) {
            $this->handleError($e);
        }
    }

    public function handleGetAllUsersRequest(): void
    {
        header("Content-Type: application/json");

        try {
            $users = $this->userRepository->getAllUsers();
            if ($users === false) {
                echo json_encode(["success" => false, "message" => "Repository error"]);
                return;
            }
            echo json_encode(["success" => true, "users" => $users]);
        } catch (PDOException $e) {
            $this->handleError($e);
        }
    }

    public function handleUpdateUserNameRequest(): void
    {
        header("Content-Type: application/json");
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data["id"]) || empty($data["name"])) {
            echo json_encode(["success" => false, "message" => "Failed: missing id or name."]);
            return;
        }

        try {
            $success = $this->userRepository->updateUserName($data["id"], $data["name"]);
            echo json_encode(["success" => true, "message" => "Name updated successfully."]);
        } catch (PDOException $e) {
            $this->handleError($e);
        }
    }

    public function handleDeleteUserRequest(): void
    {
        header("Content-Type: application/json");
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data["id"])) {
            echo json_encode(["success" => false, "message" => "Failed: missing id."]);
            return;
        }
        try {
            $success = $this->userRepository->deleteUser($data["id"]);
            echo json_encode(["success" => true, "message" => "User deleted successfully."]);
        } catch (PDOException $e) {
            $this->handleError($e);
        }
    }
}