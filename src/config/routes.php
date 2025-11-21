<?php
use App\controller\UserController;

return [
    ['GET', '/users', [UserController::class, 'index']],
    ['POST', '/users/register', [UserController::class, 'register']],
    ['POST', '/users/login', [UserController::class, 'login']],
    ['PATCH', '/users/{id}', [UserController::class, 'update']],
    ['DELETE', '/users/{id}', [UserController::class, 'delete']],
];
