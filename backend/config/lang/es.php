<?php

return [
    // * Login
    'INVALID_DATA' => 'Datos no válidos.',
    'INVALID_NAME' => 'Nombre inválido.',
    'INVALID_USERNAME' => 'Username inválido.',
    'INVALID_PASSWORD' => 'La contraseña debe tener al menos 6 caracteres.',
    'INVALID_EMAIL' => 'Email inválido.',
    'USER_OR_EMAIL_EXISTS' => 'El username o el email ya existen.',
    'REGISTER_SUCCESS' => 'Usuario registrado correctamente.',
    'REGISTER_ERROR' => 'Error al registrar el usuario.',
    'MISSING_LOGIN_DATA' => 'Faltan datos requeridos.',
    'LOGIN_FIELDS_REQUIRED' => 'Usuario/Email y contraseña son obligatorios.',
    'INVALID_CREDENTIALS' => 'Usuario o contraseña incorrectos.',
    'ACCOUNT_LOCKED' => 'Demasiados intentos fallidos. Cuenta bloqueada.',
    'LOGIN_SUCCESS' => 'Login exitoso.',
    'IP_BLOCKED' => 'La dirección IP está temporalmente bloqueada. Intente más tarde.',

    // * Usuarios
    'ACCESS_DENIED' => 'Acceso denegado.',
    'USER_NOT_FOUND' => 'Usuario no encontrado.',
    'USER_LIST' => 'Lista de usuarios.',
    'USER_FOUND' => 'Usuario encontrado.',
    'EMAIL_IN_USE' => 'El email ya está en uso por otro usuario.',
    'USERNAME_IN_USE' => 'El username ya está en uso por otro usuario.',
    'USERNAME_SAME' => 'El nuevo username debe ser diferente al actual.',
    'USERNAME_UPDATE_SUCCESS' => 'Username actualizado correctamente.',
    'CURRENT_PASSWORD_WRONG' => 'Contraseña actual incorrecta.',
    'PASSWORD_SAME' => 'La nueva contraseña debe ser diferente a la actual.',
    'PASSWORD_UPDATE_SUCCESS' => 'Contraseña actualizada correctamente.',
    'USER_UPDATE_ERROR' => 'Error al actualizar el usuario.',
    'UPDATE_SUCCESS' => 'Usuario actualizado correctamente.',
    'DELETE_SUCCESS' => 'Usuario eliminado correctamente.',
    'DELETE_NOT_FOUND' => 'El usuario que intenta eliminar no existe.',
    'DELETE_ERROR' => 'Error al eliminar el usuario. Inténtelo más tarde.',
    'PASSWORD_CHANGE_ERROR' => 'Error al cambiar la contraseña.',
    'USERNAME_CHANGE_ERROR' => 'Error al cambiar el username.',
    'TEST_LOG_SUCCESS' => 'Log de prueba creado.',
    'NO_CHANGES' => 'No hubo cambios que aplicar.',

    // * Verificación de correo
    'REGISTER_SUCCESS_CHECK_EMAIL' => 'Registro exitoso. Por favor revisa tu correo para verificar la cuenta.',
    'EMAIL_SEND_FAILED' => 'No se pudo enviar el correo de verificación.',
    'TOKEN_NOT_PROVIDED' => 'Token no proporcionado.',
    'INVALID_OR_EXPIRED_TOKEN' => 'Token inválido o expirado.',
    'EMAIL_ALREADY_VERIFIED' => 'El correo ya ha sido verificado.',
    'EMAIL_VERIFIED_SUCCESSFULLY' => 'Correo verificado exitosamente.',
    'EMAIL_VERIFICATION_FAILED' => 'No se pudo verificar el correo.',
    'TOKEN_REQUIRED' => 'Token requerido.',
    'INVALID_TOKEN' => 'Token inválido.',
    'EMAIL_VERIFIED_SUCCESS' => 'Correo verificado correctamente.',
    'VERIFICATION_EMAIL_RESENT' => 'Se ha reenviado el correo de verificación.',
    'TOKEN_EXPIRED' => 'El token ha expirado.',

    // * Recuperación de contraseña
    'EMAIL_REQUIRED' => 'El email es requerido.',
    'RESET_EMAIL_SENT' => 'Si el email está registrado, se ha enviado un enlace para restablecer la contraseña.',
    'EMAIL_NOT_VERIFIED' => 'El correo no está verificado. Por favor, verifíquelo antes de restablecer la contraseña.',
    'MISSING_TOKEN_OR_PASSWORD' => 'Faltan el token o la nueva contraseña.',
    'PASSWORD_RESET_SUCCESS' => 'Contraseña restablecida exitosamente.',
    'RESET_PASSWORD_ERROR' => 'Error al restablecer la contraseña.',
    'TOKEN_VALID' => 'Token válido.',
    'INTERNAL_ERROR' => 'Ocurrió un error interno, por favor intente más tarde.',

    // * Devices (Heladeras)
    'FRIDGE_CREATED' => 'Heladera creada correctamente.',
    'FRIDGE_UPDATED' => 'Heladera actualizada correctamente.',
    'FRIDGE_DELETED' => 'Heladera eliminada.',
    'FRIDGE_LIST' => 'Listado de heladeras.',
    'FRIDGE_NOT_FOUND' => 'Heladera no encontrada.',
    'DUPLICATE_FRIDGE_NAME' => 'Ya existe una heladera con ese nombre.',
    'MISSING_NAME' => 'El nombre es obligatorio.',
    'MISSING_USER_ID' => 'El user_id es obligatorio.',
    'MISSING_GROUP' => 'El grupo especificado no existe.',
    'CREATE_FAILED' => 'No se pudo crear la heladera.',
    'UPDATE_FAILED' => 'No se pudo actualizar la heladera.',
    'DELETE_FAILED' => 'No se pudo eliminar la heladera.',
    'ACCESS_GRANTED' => 'Acceso otorgado correctamente.',
    'ACCESS_REVOKED' => 'Acceso revocado correctamente.',
    'NOT_FOUND' => 'Recurso no encontrado.',


    // * Device Groups
    'GROUP_CREATED' => 'Grupo creado correctamente.',
    'GROUP_UPDATED' => 'Grupo actualizado correctamente.',
    'GROUP_DELETED' => 'Grupo eliminado correctamente.',
    'GROUP_HAS_DEVICES' => 'No se puede eliminar el grupo porque contiene dispositivos asignados.',
    'ACCESS_NOT_FOUND' => 'El usuario no tenía acceso asignado a este dispositivo.',

    // * General
    'SUCCESS' => 'Operación exitosa.',
];
