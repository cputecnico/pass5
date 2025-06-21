<?php
session_start();
if (!isset($_SESSION['clave_derivada']) || !isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
$clave = $_SESSION['clave_derivada'];
$usuario_id = $_SESSION['usuario_id'];

// Conexión a la base de datos
$conexion = @new mysqli('localhost', 'root', '', 'gestor_contrasenas');
if ($conexion->connect_error) {
    echo '<div class="alert alert-danger">No se pudo conectar a la base de datos. Por favor, verifica que el servidor esté en funcionamiento.</div>';
    exit;
}

$id = intval($_POST['id']);
$sitio = $_POST['sitio'];
$usuario = $_POST['usuario'];

if (!empty($_POST['contrasena'])) {
    $contrasena = $_POST['contrasena'];
    $iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $contrasena_cifrada = openssl_encrypt($contrasena, 'aes-256-cbc', $clave, 0, $iv);
    $stmt = $conexion->prepare('UPDATE contrasenas SET sitio=?, usuario=?, contrasena_cifrada=?, iv=?, usuario_id=? WHERE id=?');
    $stmt->bind_param('ssssii', $sitio, $usuario, $contrasena_cifrada, $iv, $usuario_id, $id);
    $stmt->execute();
    $stmt->close();
    $sitio_log = $sitio;
} else {
    $stmt = $conexion->prepare('UPDATE contrasenas SET sitio=?, usuario=?, usuario_id=? WHERE id=?');
    $stmt->bind_param('ssii', $sitio, $usuario, $usuario_id, $id);
    $stmt->execute();
    $stmt->close();
    $sitio_log = $sitio;
}

$origen_ip = $_SERVER['REMOTE_ADDR'] === '::1' ? '127.0.0.1' : $_SERVER['REMOTE_ADDR'];
$origen_host = gethostbyaddr($origen_ip);
$origen_user = getenv('USERNAME') ?: getenv('USER');
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$origen = $origen_ip . ' (' . $origen_host . ')';
// Solo agregar Usuario Windows si NO está vacío
if ($origen_user && trim($origen_user) !== '') {
    $origen .= ' - Usuario Windows: ' . $origen_user;
}
if ($user_agent) {
    $origen .= ' - User-Agent: ' . $user_agent;
}
// Registrar log de modificación de contraseña con sitio y origen
$conexion2 = @new mysqli('localhost', 'root', '', 'gestor_contrasenas');
if (!$conexion2->connect_error) {
    $stmt2 = $conexion2->prepare('INSERT INTO logs (usuario_id, accion, origen) VALUES (?, ?, ?)');
    $accion = 'Modificó una contraseña de ' . $sitio_log;
    $stmt2->bind_param('iss', $usuario_id, $accion, $origen);
    $stmt2->execute();
    $stmt2->close();
    $conexion2->close();
}

$conexion->close();

header('Location: index.php');
exit;
?>
