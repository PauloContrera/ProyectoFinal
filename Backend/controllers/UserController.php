<?php

namespace Controllers;

use Helpers\Response;
use Helpers\Validator;
use Models\User;

class UserController
{
    private $userModel;

    public function __construct($db)
    {
        $this->userModel = new User($db);
    }

    public function getAll()
    {
        $currentUser = $_SERVER['user'];
        if (!$this->isAdminRole($currentUser)) {
            return Response::json(403, 'ACCESS_DENIED');
        }

        $users = $this->userModel->getAllUsers();
        return Response::json(200, 'USER_LIST', $users);
    }

    public function getById($id)
    {
        $currentUser = $_SERVER['user'];

        if (!$this->isAdminRole($currentUser) && (int)$currentUser['id'] !== (int)$id) {
            return Response::json(403, 'ACCESS_DENIED');
        }

        $user = $this->userModel->getUserById($id);

        if (!$user) {
            return Response::json(404, 'USER_NOT_FOUND');
        }

        unset($user['password']);
        return Response::json(200, 'USER_FOUND', $user);
    }

    public function update($id)
    {
        $currentUser = $_SERVER['user'];

        if (!$this->isAdminRole($currentUser) && (int)$currentUser['id'] !== (int)$id) {
            return Response::json(403, 'ACCESS_DENIED');
        }

        $targetUser = $this->userModel->getUserById($id);
        if (!$targetUser) {
            return Response::json(404, 'USER_NOT_FOUND');
        }
        if (!$this->canManageProfile($currentUser, $targetUser)) {
            return Response::json(403, 'ACCESS_DENIED');
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            return Response::json(400, 'INVALID_DATA');
        }

        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $phone = trim($data['phone'] ?? '');

        if (!Validator::validateName($name)) {
            return Response::json(400, 'INVALID_NAME');
        }
        if (!Validator::validateEmail($email)) {
            return Response::json(400, 'INVALID_EMAIL');
        }

        $result = $this->userModel->updateUser($id, $name, $email, $phone, $currentUser['id']);

        if ($result['success'] === false) {
            if ($result['reason'] === 'not_found') {
                return Response::json(404, 'USER_NOT_FOUND');
            }
            if ($result['reason'] === 'duplicate_email') {
                return Response::json(400, 'EMAIL_IN_USE');
            }
            if ($result['reason'] === 'no_changes') {
                return Response::json(200, 'NO_CHANGES');
            }
            return Response::json(500, 'USER_UPDATE_ERROR');
        }

        return Response::json(200, 'UPDATE_SUCCESS');
    }

    public function delete($id)
    {
        $currentUser = $_SERVER['user'];

        if (!$this->isAdminRole($currentUser)) {
            return Response::json(403, 'ACCESS_DENIED');
        }

        if ((int)$currentUser['id'] === (int)$id) {
            return Response::json(400, 'CANNOT_DELETE_SELF');
        }

        $targetUser = $this->userModel->getUserById($id);
        if (!$targetUser) {
            return Response::json(404, 'DELETE_NOT_FOUND');
        }
        if (!$this->canManageProfile($currentUser, $targetUser)) {
            return Response::json(403, 'ACCESS_DENIED');
        }

        $deleted = $this->userModel->deleteUser($id, $currentUser['id']);

        if ($deleted === 'not_found') {
            return Response::json(404, 'DELETE_NOT_FOUND');
        }
        if (!$deleted) {
            return Response::json(500, 'DELETE_ERROR');
        }
        return Response::json(200, 'DELETE_SUCCESS');
    }

    public function changeUsername($id)
    {
        $currentUser = $_SERVER['user'];

        if ((int)$currentUser['id'] !== (int)$id) {
            return Response::json(403, 'ACCESS_DENIED');
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            return Response::json(400, 'INVALID_DATA');
        }

        $newUsername = trim($data['new_username'] ?? '');

        if (!Validator::validateUsername($newUsername)) {
            return Response::json(400, 'INVALID_USERNAME');
        }

        $user = $this->userModel->getUserById($id);
        if (!$user) {
            return Response::json(404, 'USER_NOT_FOUND');
        }

        if ($user['username'] === $newUsername) {
            return Response::json(400, 'USERNAME_SAME');
        }

        $result = $this->userModel->changeUsername($id, $newUsername, $currentUser['id']);

        if ($result['success'] === false) {
            if ($result['reason'] === 'duplicate_username') {
                return Response::json(400, 'USERNAME_IN_USE');
            }
            return Response::json(500, 'USERNAME_CHANGE_ERROR');
        }

        return Response::json(200, 'USERNAME_UPDATE_SUCCESS');
    }

    public function changePassword($id)
    {
        $currentUser = $_SERVER['user'];

        if ((int)$currentUser['id'] !== (int)$id) {
            return Response::json(403, 'ACCESS_DENIED');
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            return Response::json(400, 'INVALID_DATA');
        }

        $currentPassword = (string)($data['current_password'] ?? '');
        $newPassword = (string)($data['new_password'] ?? '');

        if (!Validator::validatePassword($newPassword)) {
            return Response::json(400, 'INVALID_PASSWORD');
        }

        $user = $this->userModel->getUserByIdWithPassword($id);
        if (!$user) {
            return Response::json(404, 'USER_NOT_FOUND');
        }

        if (!password_verify($currentPassword, $user['password'])) {
            return Response::json(400, 'CURRENT_PASSWORD_WRONG');
        }

        if (password_verify($newPassword, $user['password'])) {
            return Response::json(400, 'PASSWORD_SAME');
        }

        $result = $this->userModel->changePassword($id, $currentPassword, $newPassword, $currentUser['id']);

        if ($result['success'] === false) {
            return Response::json(500, 'PASSWORD_CHANGE_ERROR');
        }

        return Response::json(200, 'PASSWORD_UPDATE_SUCCESS');
    }

    public function verifyEmail()
{
    $token = $_GET['token'] ?? null;

    if (!$token) {
        return Response::json(400, 'TOKEN_REQUIRED');
    }

    $user = $this->userModel->getByVerificationToken($token);

    if (!$user) {
        return Response::json(404, 'INVALID_TOKEN');
    }

    $this->userModel->verifyEmail($user['id']);

    return Response::json(200, 'EMAIL_VERIFIED_SUCCESS');
}




    public function testLog()
    {
        $currentUser = $_SERVER['user'];
        if (!$this->isAdminRole($currentUser)) {
            return Response::json(403, 'ACCESS_DENIED');
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $this->userModel->logEvent((int)$currentUser['id'], 'test_log', 'Prueba de auditoria ejecutada', $ip);

        return Response::json(200, 'TEST_LOG_SUCCESS');
    }

    private function isAdminRole(array $user): bool
    {
        return in_array($user['role'], ['admin', 'superadmin'], true);
    }

    private function canManageProfile(array $currentUser, array $targetUser): bool
    {
        if ((int)$currentUser['id'] === (int)$targetUser['id']) {
            return true;
        }

        if ($currentUser['role'] === 'superadmin') {
            return true;
        }

        if ($currentUser['role'] === 'admin') {
            return !in_array($targetUser['role'], ['admin', 'superadmin'], true);
        }

        return false;
    }
}
