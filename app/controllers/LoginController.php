<?php
require_once __DIR__ . '/../models/User.php';

class LoginController {

    // Muestra el formulario de login
    public function index() {
        $error = '';
        require_once __DIR__ . '/../views/login/login.php';
    }

    // Procesa login
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');

            $userModel = new User();
            $user = $userModel->validateUser($username, $password); // MD5

            if ($user) {
                if (session_status() === PHP_SESSION_NONE) session_start();

                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'username' => $user['user'],
                    'fullname' => $user['firstname'] . ' ' . $user['apellidos'],
                    'email' => $user['email'],
                    'id_position' => $user['id_position'],
                    'is_leader'     => $user['is_leader'],
                    'department_id' => $user['department_id'] ?? null
                ];

                // Redirigimos al dashboard
                header('Location: index.php?page=app');
                exit;

            } else {
                $error = "Usuario o contraseña incorrectos";
                require_once __DIR__ . '/../views/login/login.php';
            }

        } else {
            $this->index();
        }
    }

        // Cierra la sesión del usuario
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Limpiar variables de sesión
        $_SESSION = [];
        session_unset();
        session_destroy();

        // (Opcional) limpiar cookies “recordar sesión”
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }

        // Redirigir al login con mensaje
        header('Location: index.php?page=login&msg=logout');
        exit;
    }
}
