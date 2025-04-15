<?php
session_start();
define('IN_COMPUYA', true);
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Verificar si ya hay sesión activa
if (isset($_SESSION['usuario_id'])) {
    // Redirigir al panel de administración
    header('Location: admin/index.php');
    exit;
}

$errores = [];

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // Validar campos
    if (empty($usuario)) {
        $errores[] = "El nombre de usuario es obligatorio.";
    }
    
    if (empty($password)) {
        $errores[] = "La contraseña es obligatoria.";
    }
    
    // Si no hay errores, verificar credenciales
    if (empty($errores)) {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Preparar consulta segura
        $stmt = $conn->prepare("SELECT id, nombre, usuario, password, rol FROM usuarios WHERE (usuario = ? OR email = ?) AND activo = 1");
        $stmt->bind_param("ss", $usuario, $usuario);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $usuario_data = $result->fetch_assoc();
            
            // Verificar contraseña
            if (password_verify($password, $usuario_data['password'])) {
                // Iniciar sesión
                $_SESSION['usuario_id'] = $usuario_data['id'];
                $_SESSION['usuario_nombre'] = $usuario_data['nombre'];
                $_SESSION['usuario_usuario'] = $usuario_data['usuario'];
                $_SESSION['usuario_rol'] = $usuario_data['rol'];
                
                // Actualizar último acceso
                $stmt = $conn->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?");
                $stmt->bind_param("i", $usuario_data['id']);
                $stmt->execute();
                
                // Redirigir al panel de administración
                header('Location: admin/index.php');
                exit;
            } else {
                $errores[] = "Contraseña incorrecta.";
            }
        } else {
            $errores[] = "Usuario no encontrado o inactivo.";
        }
    }
}

// Título de la página
$page_title = 'Iniciar Sesión';

// Incluir cabecera
include 'includes/header.php';
?>

<section class="login-section">
    <div class="container">
        <div class="login-container">
            <div class="login-box">
                <h1>Iniciar Sesión</h1>
                
                <?php if (!empty($errores)): ?>
                    <div class="mensajes-container">
                        <?php foreach ($errores as $error): ?>
                            <div class="mensaje error"><?php echo $error; ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <form action="" method="post" class="login-form">
                    <div class="form-group">
                        <label for="usuario">Usuario o Email:</label>
                        <input type="text" id="usuario" name="usuario" value="<?php echo isset($usuario) ? htmlspecialchars($usuario) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Contraseña:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-login">Iniciar Sesión</button>
                    </div>
                </form>
                
                <div class="login-links">
                    <a href="forgot-password.php">¿Olvidaste tu contraseña?</a>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.login-section {
    padding: 50px 0;
}

.login-container {
    display: flex;
    justify-content: center;
    align-items: center;
}

.login-box {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    padding: 30px;
    width: 100%;
    max-width: 500px;
}

.login-box h1 {
    margin-bottom: 30px;
    text-align: center;
    color: #001CBD;
    font-size: 28px;
}

.login-form .form-group {
    margin-bottom: 20px;
}

.login-form label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #444;
}

.login-form input[type="text"],
.login-form input[type="password"] {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
}

.login-form input:focus {
    border-color: #001CBD;
    outline: none;
    box-shadow: 0 0 0 2px rgba(0, 28, 189, 0.1);
}

.form-actions {
    margin-top: 30px;
}

.btn-login {
    display: block;
    width: 100%;
    padding: 12px;
    background-color: #001CBD;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.3s;
}

.btn-login:hover {
    background-color: #0016a0;
}

.login-links {
    margin-top: 20px;
    text-align: center;
}

.login-links a {
    color: #001CBD;
    text-decoration: none;
    font-size: 14px;
}

.login-links a:hover {
    text-decoration: underline;
}
</style>

<?php
// Incluir pie de página
include 'includes/footer.php';
?>