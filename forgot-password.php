<?php
session_start();
define('IN_COMPUYA', true);
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$mensajes = [];
$errores = [];

// Procesar formulario de recuperación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    
    if (empty($email)) {
        $errores[] = "El email es obligatorio.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El formato del email no es válido.";
    }
    
    if (empty($errores)) {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Verificar si el email existe
        $stmt = $conn->prepare("SELECT id, nombre FROM usuarios WHERE email = ? AND activo = 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $usuario = $result->fetch_assoc();
            
            // Generar token único para restablecer contraseña
            $token = bin2hex(random_bytes(32));
            $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Guardar token en la base de datos
            $stmt = $conn->prepare("INSERT INTO reset_password (usuario_id, token, expira) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $usuario['id'], $token, $expira);
            
            if ($stmt->execute()) {
                // Enviar email con el enlace de restablecimiento
                $reset_url = BASE_URL . "/reset-password.php?token=" . $token;
                
                // En un entorno de producción, aquí enviarías el email real
                // mail($email, "Restablecer contraseña", "Hola {$usuario['nombre']},\n\nPara restablecer tu contraseña, haz clic en el siguiente enlace:\n$reset_url\n\nEl enlace expirará en 1 hora.\n\nSaludos,\nEquipo de COMPUYA");
                
                $mensajes[] = "Se ha enviado un enlace a tu correo para restablecer la contraseña. El enlace expirará en 1 hora.";
            } else {
                $errores[] = "Error al procesar la solicitud. Por favor, inténtalo de nuevo más tarde.";
            }
        } else {
            // Por seguridad, no revelar si el email existe o no
            $mensajes[] = "Si el email está registrado, recibirás un enlace para restablecer tu contraseña.";
        }
    }
}

$page_title = 'Recuperar Contraseña';
include 'includes/header.php';
?>

<section class="login-section">
    <div class="container">
        <div class="login-container">
            <div class="login-box">
                <h1>Recuperar Contraseña</h1>
                
                <?php if (!empty($mensajes)): ?>
                    <div class="mensajes-container">
                        <?php foreach ($mensajes as $mensaje): ?>
                            <div class="mensaje exito"><?php echo $mensaje; ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($errores)): ?>
                    <div class="mensajes-container">
                        <?php foreach ($errores as $error): ?>
                            <div class="mensaje error"><?php echo $error; ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <form action="" method="post" class="login-form">
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-login">Enviar Enlace</button>
                    </div>
                </form>
                
                <div class="login-links">
                    <a href="login.php">Volver a Inicio de Sesión</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
include 'includes/footer.php';
?>