<?php
session_start();

// Redirigir si ya está logueado
if(isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// Incluir configuración de base de datos
require_once '../config/database.php';

// Inicializar variables
$error = '';

// Procesar formulario de login
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Crear conexión a la base de datos
    $database = new Database();
    $db = $database->connect();
    
    // Obtener datos del formulario
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if(empty($email) || empty($password)) {
        $error = 'Por favor ingrese email y contraseña';
    } else {
        // Consultar si existe el usuario
        $query = "SELECT id, nombre, apellido, email, password, rol FROM usuarios 
                WHERE email = :email AND rol = 'admin' AND activo = true LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verificar contraseña
            if(password_verify($password, $usuario['password'])) {
                // Iniciar sesión
                $_SESSION['admin_id'] = $usuario['id'];
                $_SESSION['admin_nombre'] = $usuario['nombre'];
                $_SESSION['admin_apellido'] = $usuario['apellido'];
                $_SESSION['admin_email'] = $usuario['email'];
                $_SESSION['admin_rol'] = $usuario['rol'];
                
                // Redirigir al dashboard
                header('Location: index.php');
                exit;
            } else {
                $error = 'Contraseña incorrecta';
            }
        } else {
            $error = 'Usuario no encontrado o no tiene permisos de administrador';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - CompuYA</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            max-width: 400px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 25px;
        }
        .login-logo img {
            max-height: 60px;
        }
        .btn-primary {
            background-color: #001C8D;
            border-color: #001C8D;
        }
        .btn-primary:hover {
            background-color: #001463;
            border-color: #001463;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <img src="../assets/img/logo.svg" alt="CompuYA">
            <h4 class="mt-2">Panel de Administración</h4>
        </div>
        
        <?php if(!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-3">
                <label for="email" class="form-label">Correo Electrónico</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
            </div>
        </form>
        
        <div class="mt-3 text-center">
            <a href="../index.php">Volver a la tienda</a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>