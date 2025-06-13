<?php

namespace Controllers;

use Helpers\Response;
use Helpers\Validator;
use Models\User;
use Helpers\Auth;

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
        if ($currentUser['role'] !== 'admin') {
            return Response::json(403, 'Acceso denegado.');
        }

        $users = $this->userModel->getAllUsers();
        return Response::json(200, 'Lista de usuarios', $users);
    }




    public function getById($id)
    {
        $currentUser = $_SERVER['user'];

        if ($currentUser['role'] !== 'admin' && $currentUser['id'] != $id) {
            return Response::json(403, 'Acceso denegado.');
        }

        $user = $this->userModel->getUserById($id);

        if (!$user) {
            return Response::json(404, 'Usuario no encontrado.');
        }

        unset($user['password']); // No mostramos la contraseña
        return Response::json(200, 'Usuario encontrado', $user);
    }

    public function update($id)
    {
        $currentUser = $_SERVER['user'];

        if ($currentUser['role'] !== 'admin' && $currentUser['id'] != $id) {
            return Response::json(403, 'Acceso denegado.');
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            return Response::json(400, 'Datos no válidos.');
        }

        // Sanitización
        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $phone = trim($data['phone'] ?? '');

        if (!Validator::validateName($name)) {
            return Response::json(400, 'Nombre inválido.');
        }
        if (!Validator::validateEmail($email)) {
            return Response::json(400, 'Email inválido.');
        }

        $result = $this->userModel->updateUser($id, $name, $email, $phone);
        if ($result['success'] === false) {
            if ($result['reason'] === 'not_found') {
                return Response::json(404, 'Usuario no encontrado');
            }
            if ($result['reason'] === 'duplicate_email') {
                return Response::json(400, 'El email ya está en uso por otro usuario');
            }
        } else {
            return Response::json(200, 'Usuario actualizado correctamente.');
        }
    }

    public function delete($id)
    {
        $currentUser = $_SERVER['user'];

        if ($currentUser['role'] !== 'admin') {
            return Response::json(403, 'Acceso denegado.');
        }

        $deleted = $this->userModel->deleteUser($id);

        if ($deleted === 'not_found') {
            return Response::json(404, 'El usuario que intenta eliminar no existe.');
        }
        if (!$deleted) {
            return Response::json(500, 'Error al eliminar el usuario. Inténtelo más tarde.');
        }
        return Response::json(200, 'Usuario eliminado correctamente.');
    }
// En el UserController, solo para testear
public function testLog()
{
    $userId = 13; // ID existente
    $changedBy = $_SERVER['user']['id'];
    $this->userModel->logChange($userId, $changedBy, 'test_log', 'valor antiguo', 'valor nuevo');

    Response::json(200, 'Log de prueba creado');
}

}
