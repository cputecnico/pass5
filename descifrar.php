<?php
// descifrar.php
session_start();
if (!isset($_SESSION['clave_derivada']) || !isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
$clave = $_SESSION['clave_derivada'];
$usuario_id = $_SESSION['usuario_id'];
$conexion = @new mysqli('localhost', 'root', '', 'gestor_contrasenas');
if ($conexion->connect_error) {
    echo 'No se pudo conectar a la base de datos. Por favor, verifica que el servidor esté en funcionamiento.';
    exit;
}
$id = intval($_GET['id']);
$resultado = $conexion->query("SELECT contrasena_cifrada, iv, usuario_id FROM contrasenas WHERE id = $id LIMIT 1");
if ($fila = $resultado->fetch_assoc()) {
    if ($fila['usuario_id'] != $usuario_id) {
        echo 'No autorizado';
    } else {
        $contrasena_cifrada = $fila['contrasena_cifrada'];
        $iv = $fila['iv'];
        if (isset($_GET['cifrada']) && $_GET['cifrada'] == '1') {
            // Mostrar cifrada (base64)
            echo base64_encode($contrasena_cifrada);
        } else {
            // Mostrar descifrada
            $contrasena = openssl_decrypt($contrasena_cifrada, 'aes-256-cbc', $clave, 0, $iv);
            echo $contrasena !== false ? $contrasena : 'Clave incorrecta';
        }
    }
} else {
    echo 'No encontrada';
}
$conexion->close();
?>
