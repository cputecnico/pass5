<?php
// Quitar session_start() aquí, ya que debe estar solo en index.php
if (session_status() !== PHP_SESSION_ACTIVE) {
    echo '<div class="alert alert-danger">Error de sesión: por favor, vuelve a iniciar sesión.</div>';
    exit;
}
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
$usuario_id = $_SESSION['usuario_id'];
$usuario_nombre = $_SESSION['usuario'];
// Conexión a la base de datos
$conexion = @new mysqli('localhost', 'root', '', 'gestor_contrasenas');
if ($conexion->connect_error) {
    echo '<div class="alert alert-danger">No se pudo conectar a la base de datos. Por favor, verifica que el servidor esté en funcionamiento.</div>';
    exit;
}

// Búsqueda
$condicion = "WHERE usuario_id = $usuario_id";
if (isset($_GET['buscar']) && $_GET['buscar'] !== '') {
    $buscar = $conexion->real_escape_string($_GET['buscar']);
    $condicion .= " AND (sitio LIKE '%$buscar%' OR usuario LIKE '%$buscar%')";
}

$resultado = $conexion->query('SELECT * FROM contrasenas ' . $condicion . ' ORDER BY id DESC');

if ($resultado->num_rows > 0) {
    echo '<div class="table-responsive">';
    echo '<table class="table table-striped align-middle mb-0">';
    echo '<thead class="table-primary">';
    echo '<tr>';
    echo '<th scope="col"><i class="bi bi-globe"></i> Sitio/App</th>';
    echo '<th scope="col"><i class="bi bi-person"></i> Usuario</th>';
    echo '<th scope="col"><i class="bi bi-key"></i> Contraseña</th>';
    echo '<th scope="col" class="text-center">Acciones</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    while ($fila = $resultado->fetch_assoc()) {
        $id = $fila['id'];
        $contrasena_cifrada = $fila['contrasena_cifrada'];
        $iv = $fila['iv'];
        $contrasena_cifrada_b64 = base64_encode($contrasena_cifrada);
        echo '<tr>';
        echo '<td style="word-break:break-word;max-width:120px;">' . htmlspecialchars($fila['sitio']) . '</td>';
        echo '<td style="word-break:break-word;max-width:120px;">' . htmlspecialchars($fila['usuario']) . '</td>';
        echo '<td style="word-break:break-all;max-width:120px;white-space:normal;vertical-align:middle;"><span id="pwd-' . $id . '" class="d-block text-break fade-pwd" style="min-width:80px;max-width:220px;vertical-align:middle;overflow-wrap:anywhere;white-space:normal;">' . $contrasena_cifrada_b64 . '</span></td>';
        echo '<td class="text-center">';
        // Botones aún más responsivos y compactos
        echo '<div class="d-flex flex-wrap flex-md-nowrap justify-content-center gap-1">';
        echo '<button class="btn btn-sm btn-secondary px-1 py-1" type="button" style="width:36px;min-width:32px;max-width:40px;" onclick="togglePwd(' . $id . ')"><span id="eye-' . $id . '" data-estado="descifrar"><i class="bi bi-eye-slash"></i></span></button>';
        echo '<button class="btn btn-sm btn-outline-primary px-1 py-1" type="button" style="width:36px;min-width:32px;max-width:40px;" onclick="copiarClave(' . $id . ')"><i class="bi bi-clipboard"></i></button>';
        echo '<button class="btn btn-sm btn-warning px-1 py-1" type="button" style="width:36px;min-width:32px;max-width:40px;" data-bs-toggle="modal" data-bs-target="#editarModal' . $id . '"><i class="bi bi-pencil"></i></button>';
        echo '<a href="eliminar.php?id=' . $id . '" class="btn btn-sm btn-danger px-1 py-1" style="width:36px;min-width:32px;max-width:40px;" onclick="return confirm(\'¿Seguro que deseas eliminar?\')"><i class="bi bi-trash"></i></a>';
        echo '</div>';
        echo '</td>';
        echo '</tr>';
        // Modal de edición
        echo '<div class="modal fade" id="editarModal' . $id . '" tabindex="-1" aria-labelledby="editarModalLabel' . $id . '" aria-hidden="true">';
        echo '  <div class="modal-dialog">';
        echo '    <div class="modal-content">';
        echo '      <form action="modificar.php" method="POST">';
        echo '        <div class="modal-header">';
        echo '          <h5 class="modal-title" id="editarModalLabel' . $id . '">Editar Contraseña</h5>';
        echo '          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>';
        echo '        </div>';
        echo '        <div class="modal-body">';
        echo '          <input type="hidden" name="id" value="' . $id . '">' ;
        echo '          <div class="mb-3">';
        echo '            <label class="form-label">Sitio/App</label>';
        echo '            <input type="text" name="sitio" class="form-control" value="' . htmlspecialchars($fila['sitio']) . '" required>';
        echo '          </div>';
        echo '          <div class="mb-3">';
        echo '            <label class="form-label">Usuario</label>';
        echo '            <input type="text" name="usuario" class="form-control" value="' . htmlspecialchars($fila['usuario']) . '" required>';
        echo '          </div>';
        echo '          <div class="mb-3">';
        echo '            <label class="form-label">Nueva Contraseña</label>';
        echo '            <input type="password" name="contrasena" class="form-control" placeholder="Dejar en blanco para no cambiar">';
        echo '          </div>';
        echo '        </div>';
        echo '        <div class="modal-footer">';
        echo '          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>';
        echo '          <button type="submit" class="btn btn-primary">Guardar Cambios</button>';
        echo '        </div>';
        echo '      </form>';
        echo '    </div>';
        echo '  </div>';
        echo '</div>';
    }
    echo '</tbody></table></div>';
} else {
    echo '<div class="alert alert-warning text-center">No hay contraseñas guardadas.</div>';
}
$conexion->close();
?>
<script>
function animatePwdChange(span, newValue) {
    span.classList.add('hide');
    setTimeout(function() {
        span.textContent = newValue;
        span.classList.remove('hide');
    }, 300);
}
function togglePwd(id) {
    const span = document.getElementById('pwd-' + id);
    const eye = document.getElementById('eye-' + id);
    if (!eye.dataset.estado || eye.dataset.estado === 'descifrar') {
        fetch('descifrar.php?id=' + id)
            .then(response => response.text())
            .then(data => {
                if (data.startsWith('No se pudo conectar a la base de datos')) {
                    animatePwdChange(span, data);
                    eye.innerHTML = '<i class="bi bi-eye-slash"></i>';
                    eye.dataset.estado = 'descifrar';
                } else {
                    animatePwdChange(span, data);
                    eye.innerHTML = '<i class="bi bi-eye"></i>';
                    eye.dataset.estado = 'cifrar';
                }
            });
    } else {
        fetch('descifrar.php?id=' + id + '&cifrada=1')
            .then(response => response.text())
            .then(data => {
                animatePwdChange(span, data);
                eye.innerHTML = '<i class="bi bi-eye-slash"></i>';
                eye.dataset.estado = 'descifrar';
            });
    }
}
function copiarClave(id) {
    const eye = document.getElementById('eye-' + id);
    const span = document.getElementById('pwd-' + id);
    if (eye.dataset.estado === 'cifrar') {
        if (span.textContent.startsWith('No se pudo conectar a la base de datos')) {
            showToast(span.textContent, 'danger');
            return;
        }
        navigator.clipboard.writeText(span.textContent).then(function() {
            showToast('Contraseña copiada al portapapeles', 'success');
        });
    } else {
        fetch('descifrar.php?id=' + id)
            .then(response => response.text())
            .then(data => {
                if (data.startsWith('No se pudo conectar a la base de datos')) {
                    showToast(data, 'danger');
                    return;
                }
                navigator.clipboard.writeText(data).then(function() {
                    showToast('Contraseña copiada al portapapeles', 'success');
                });
                animatePwdChange(span, data);
                eye.innerHTML = '<i class="bi bi-eye-slash"></i> <span class="d-none d-md-inline">Cifrar</span>';
                eye.dataset.estado = 'cifrar';
            });
    }
}
// Toast animado
function showToast(msg, type) {
    let toast = document.createElement('div');
    toast.className = 'toast align-items-center text-bg-' + (type === 'success' ? 'success' : 'danger') + ' border-0 show position-fixed top-0 end-0 m-3';
    toast.style.zIndex = 2000;
    toast.innerHTML = '<div class="d-flex"><div class="toast-body">' + msg + '</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>';
    document.body.appendChild(toast);
    setTimeout(() => { toast.classList.remove('show'); toast.classList.add('hide'); setTimeout(() => toast.remove(), 500); }, 2200);
    toast.querySelector('.btn-close').onclick = function() { toast.remove(); };
}
// Detectar tema del sistema y permitir alternar
function setTheme(theme) {
    document.body.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
}
function autoTheme() {
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        setTheme('dark');
    } else {
        setTheme('light');
    }
}
// Inicializar tema
const savedTheme = localStorage.getItem('theme');
if (savedTheme) {
    setTheme(savedTheme);
} else {
    autoTheme();
}
// Botón para alternar tema
window.addEventListener('DOMContentLoaded', function() {
    let btn = document.createElement('button');
    btn.className = 'btn btn-sm btn-outline-secondary mb-3 float-end';
    btn.innerHTML = '<i class="bi bi-moon"></i> <span class="d-none d-md-inline">Tema</span>';
    btn.onclick = function() {
        const current = document.body.getAttribute('data-theme');
        setTheme(current === 'dark' ? 'light' : 'dark');
        btn.innerHTML = current === 'dark' ? '<i class="bi bi-moon"></i> <span class="d-none d-md-inline">Tema</span>' : '<i class="bi bi-brightness-high"></i> <span class="d-none d-md-inline">Tema</span>';
    };
    document.body.prepend(btn);
});
</script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<style id="theme-style">
:root {
    --color-bg: #fff;
    --color-text: #222;
    --color-table: #f8f9fa;
    --color-th: #e9ecef;
}
body[data-theme="dark"] {
    --color-bg: #181a1b;
    --color-text: #f1f1f1;
    --color-table: #23272b;
    --color-th: #23272b;
}
body {
    background: var(--color-bg) !important;
    color: var(--color-text) !important;
}
.table, .table-striped, .table-primary, .table th, .table td {
    background: var(--color-table) !important;
    color: var(--color-text) !important;
}
.table-primary th {
    background: var(--color-th) !important;
}
.fade-pwd {
    transition: opacity 0.3s;
    opacity: 1;
}
.fade-pwd.hide {
    opacity: 0;
}
.btn.btn-sm {
    font-size: 0.55rem !important;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding-top: 0.375rem;
    padding-bottom: 0.375rem;
}
.btn.btn-sm i {
    font-size: 1.1rem;
    margin-bottom: 2px;
}
@media (max-width: 767px) {
    .table-responsive { font-size: 0.95rem; }
    .table th, .table td { padding: 0.5rem 0.3rem; }
    .btn { font-size: 0.7rem; }
    .table td, .table th { vertical-align: middle !important; }
    .d-flex.flex-wrap.flex-md-nowrap > * { flex-basis: 48% !important; max-width: 48% !important; margin-bottom: 4px; }
    .btn.btn-sm { width: 36px !important; min-width: 32px !important; max-width: 40px !important; }
}
@media (max-width: 500px) {
    .d-flex.flex-wrap.flex-md-nowrap > * { flex-basis: 100% !important; max-width: 100% !important; }
    .btn.btn-sm { width: 40px !important; }
}
</style>
