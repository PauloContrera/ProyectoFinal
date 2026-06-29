<?php

namespace Controllers;

use Helpers\AuditLogger;
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

    public function create()
    {
        $currentUser = $_SERVER['user'];
        if (!$this->isAdminRole($currentUser)) {
            return Response::json(403, 'ACCESS_DENIED');
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            return Response::json(400, 'INVALID_DATA');
        }

        $name = trim($data['name'] ?? '');
        $username = trim($data['username'] ?? '');
        $email = trim($data['email'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $password = (string)($data['password'] ?? '');
        $role = (string)($data['role'] ?? 'client');

        if (!$this->canAssignRole($currentUser, $role)) {
            return Response::json(403, 'ACCESS_DENIED');
        }
        if (!Validator::validateName($name)) {
            return Response::json(400, 'INVALID_NAME');
        }
        if (!Validator::validateUsername($username)) {
            return Response::json(400, 'INVALID_USERNAME');
        }
        if (!Validator::validateEmail($email)) {
            return Response::json(400, 'INVALID_EMAIL');
        }
        if (!Validator::validatePassword($password)) {
            return Response::json(400, 'INVALID_PASSWORD');
        }
        if ($this->userModel->exists($username, $email)) {
            return Response::json(400, 'USER_OR_EMAIL_EXISTS');
        }

        $userId = $this->userModel->createManaged(
            $name,
            $username,
            password_hash($password, PASSWORD_DEFAULT),
            $email,
            $phone,
            $role,
            true
        );

        if (!$userId) {
            return Response::json(500, 'REGISTER_ERROR');
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $this->userModel->createVerifiedEmailRecord((int)$userId, $email, $ip);
        $this->userModel->logEvent((int)$userId, 'admin_user_created', 'Usuario creado desde administracion', $ip);
        AuditLogger::event('user_created_by_admin', 'Usuario creado desde administracion', 'info', [
            'target_user_id' => (int)$userId,
            'role' => $role,
        ], (int)$currentUser['id'], 'user', (string)$userId, 'create');

        return Response::json(201, 'USER_CREATED', [
            'user_id' => (int)$userId,
            'role' => $role,
        ]);
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

    public function manage($id)
    {
        $currentUser = $_SERVER['user'];
        if (!$this->isAdminRole($currentUser)) {
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

        $changes = 0;

        if (isset($data['name']) || isset($data['email']) || isset($data['phone'])) {
            $name = trim($data['name'] ?? $targetUser['name']);
            $email = trim($data['email'] ?? $targetUser['email']);
            $phone = trim($data['phone'] ?? ($targetUser['phone'] ?? ''));

            if (!Validator::validateName($name)) {
                return Response::json(400, 'INVALID_NAME');
            }
            if (!Validator::validateEmail($email)) {
                return Response::json(400, 'INVALID_EMAIL');
            }

            $result = $this->userModel->updateUser($id, $name, $email, $phone, $currentUser['id']);
            if ($result['success'] === true) {
                $changes++;
                $targetUser = $this->userModel->getUserById($id);
            } elseif ($result['reason'] === 'duplicate_email') {
                return Response::json(400, 'EMAIL_IN_USE');
            } elseif ($result['reason'] !== 'no_changes') {
                return Response::json(500, 'USER_UPDATE_ERROR');
            }
        }

        if (isset($data['username'])) {
            $username = trim((string)$data['username']);
            if (!Validator::validateUsername($username)) {
                return Response::json(400, 'INVALID_USERNAME');
            }
            if ($username !== $targetUser['username']) {
                if ($this->userModel->usernameExistsForAnother($username, $id)) {
                    return Response::json(400, 'USERNAME_IN_USE');
                }

                $result = $this->userModel->changeUsername($id, $username, $currentUser['id']);
                if ($result['success'] === true) {
                    $changes++;
                    $targetUser = $this->userModel->getUserById($id);
                } else {
                    if ($result['reason'] === 'duplicate_username') {
                        return Response::json(400, 'USERNAME_IN_USE');
                    }
                    return Response::json(500, 'USERNAME_CHANGE_ERROR');
                }
            }
        }

        if (isset($data['role'])) {
            $role = (string)$data['role'];
            if (!$this->canAssignRole($currentUser, $role)) {
                return Response::json(403, 'ACCESS_DENIED');
            }
            if ((int)$currentUser['id'] === (int)$id) {
                return Response::json(400, 'INVALID_DATA');
            }

            $result = $this->userModel->changeRole($id, $role, $currentUser['id']);
            if ($result['success'] === true) {
                $changes++;
                $targetUser = $this->userModel->getUserById($id);
            } elseif ($result['reason'] !== 'no_changes') {
                return Response::json(500, 'USER_UPDATE_ERROR');
            }
        }

        if (!empty($data['password'])) {
            $password = (string)$data['password'];
            if (!Validator::validatePassword($password)) {
                return Response::json(400, 'INVALID_PASSWORD');
            }
            if ((int)$currentUser['id'] === (int)$id) {
                return Response::json(400, 'INVALID_DATA');
            }

            $result = $this->userModel->setPasswordByAdmin($id, $password, $currentUser['id']);
            if ($result['success'] === true) {
                $changes++;
            } else {
                return Response::json(500, 'PASSWORD_CHANGE_ERROR');
            }
        }

        AuditLogger::event('user_admin_updated', 'Usuario actualizado desde administracion', 'info', [
            'target_user_id' => (int)$id,
            'changed_fields' => array_keys($data),
            'changes' => $changes,
        ], (int)$currentUser['id'], 'user', (string)$id, 'update');

        return Response::json(200, $changes > 0 ? 'UPDATE_SUCCESS' : 'NO_CHANGES', [
            'user' => $this->userModel->getUserById($id),
        ]);
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

    private function canAssignRole(array $currentUser, string $role): bool
    {
        if ($currentUser['role'] === 'superadmin') {
            return in_array($role, ['admin', 'client', 'visitor'], true);
        }

        if ($currentUser['role'] === 'admin') {
            return in_array($role, ['client', 'visitor'], true);
        }

        return false;
    }
}
