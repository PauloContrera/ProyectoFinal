<?php

use Controllers\AuthController;
use Controllers\UserController;
use Middleware\AuthMiddleware;
use Config\Database;

function routeRegister()
{
    $authController = new AuthController();
    $authController->register();
}

function routeLogin()
{
    $authController = new AuthController();
    $authController->login();
}
function routeGetAllUsers($currentUser)
{
    $userController = new UserController((new Database())->getConnection());
    $userController->getAll($currentUser);
}

function routeGetUserById($id)
{
    AuthMiddleware::verifyToken();
    $controller = new UserController((new Database())->getConnection());
    $controller->getById($id);
}

function routeUpdateUser($id)
{
    AuthMiddleware::verifyToken();
    $controller = new UserController((new Database())->getConnection());
    $controller->update($id);
}

function routeDeleteUser($id)
{
    AuthMiddleware::verifyToken();
    $controller = new UserController((new Database())->getConnection());
    $controller->delete($id);
}

function routeGetUsers()
{
    AuthMiddleware::verifyToken();
    $controller = new UserController((new Database())->getConnection());
    $controller->getAll();
}