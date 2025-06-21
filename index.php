<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor de Contraseñas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-end mb-3">
            <a href="cambiar_contrasena.php" class="btn btn-warning me-2">Cambiar contraseña</a>
            <a href="logout.php" class="btn btn-outline-danger">Cerrar sesión</a>
        </div>
        <h1 class="mb-4 text-center">Gestor de Contraseñas</h1>
        <h5 class="mb-4 text-center text-success">Bienvenido, <?php echo htmlspecialchars($_SESSION['usuario']); ?>!</h5>
        <div class="mb-4" style="max-width:400px;margin:0 auto;">
            <input type="text" id="buscador" class="form-control" placeholder="Buscar sitio, usuario o clave..." autocomplete="off">
            <div id="resultados-busqueda" class="list-group position-absolute w-100" style="z-index:10;"></div>
        </div>
        <form action="guardar.php" method="POST" class="mb-4">
            <div class="row g-2">
                <div class="col-md-4">
                    <input type="text" name="sitio" class="form-control" placeholder="Sitio o App" required>
                </div>
                <div class="col-md-4">
                    <input type="text" name="usuario" class="form-control" placeholder="Usuario" required>
                </div>
                <div class="col-md-4">
                    <input type="password" name="contrasena" class="form-control" placeholder="Contraseña" required>
                    <button type="button" class="btn btn-sm btn-info ms-2" id="sugerir-contrasena">
                        <i class="bi bi-shuffle"></i> Sugerir
                    </button>
                </div>
            </div>
            <div class="mt-3 text-end">
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
        <h2 class="mb-3">Contraseñas Guardadas</h2>
        <div id="tabla-contraseñas">
            <?php include __DIR__ . '/listar.php'; ?>
        </div>
        <?php
        // Mostrar los últimos 6 logs de actividad del usuario después del saludo y antes del final del contenedor
        if (isset($_SESSION['usuario_id'])) {
            $usuario_id = $_SESSION['usuario_id'];
            $conexion = @new mysqli('localhost', 'root', '', 'gestor_contrasenas');
            if (!$conexion->connect_error) {
                $stmt = $conexion->prepare('SELECT fecha, accion, origen FROM logs WHERE usuario_id = ? ORDER BY fecha DESC LIMIT 6');
                $stmt->bind_param('i', $usuario_id);
                $stmt->execute();
                $stmt->bind_result($fecha, $accion, $origen);
                $logs = [];
                while ($stmt->fetch()) {
                    $logs[] = [
                        'fecha' => $fecha,
                        'accion' => $accion,
                        'origen' => $origen
                    ];
                }
                if (count($logs) > 0) {
                    echo '<div class="alert alert-info mt-5"><strong>Últimas actividades</strong><ul class="mb-0">';
                    foreach ($logs as $log) {
                        // Limpiar visualmente la frase ' - Usuario Windows:' si está vacía o al final
                        $origen_legible = $log['origen'] ? preg_replace('/ - Usuario Windows:\s*$/', '', htmlspecialchars($log['origen'])) : 'Desconocido';
                        echo '<li>' . htmlspecialchars($log['fecha']) . ' - ' . htmlspecialchars($log['accion']) . ' <br><span style="font-size:smaller">' . $origen_legible . '</span></li>';
                    }
                    echo '</ul></div>';
                }
                $stmt->close();
                $conexion->close();
            }
        }
        ?>
    </div>
    <script>
    const inputBuscador = document.getElementById('buscador');
    const resultadosBusqueda = document.getElementById('resultados-busqueda');
    inputBuscador.addEventListener('input', function() {
        const valor = this.value.trim();
        if (valor.length === 0) {
            resultadosBusqueda.innerHTML = '';
            mostrarTodasLasFilas();
            return;
        }
        let hayResultados = false;
        document.querySelectorAll('table tbody tr').forEach(function(row) {
            let texto = row.innerText.toLowerCase();
            if (texto.includes(valor.toLowerCase())) {
                row.style.display = '';
                hayResultados = true;
            } else {
                row.style.display = 'none';
            }
        });
        if (!hayResultados) {
            resultadosBusqueda.innerHTML = '<div class="list-group-item text-danger">No se encontraron resultados</div>';
        } else {
            resultadosBusqueda.innerHTML = '';
        }
    });
    function mostrarTodasLasFilas() {
        document.querySelectorAll('table tbody tr').forEach(function(row) {
            row.style.display = '';
        });
    }

    // Generador de contraseña sugerida
    const palabrasCastellano = [
        'sol', 'luna', 'cielo', 'mar', 'rio', 'flor', 'paz', 'fuego', 'nube', 'roca',
        'verde', 'azul', 'rojo', 'gris', 'alto', 'bajo', 'frio', 'calor', 'viento', 'lluvia'
    ];
    function generarContrasena() {
        let palabra = palabrasCastellano[Math.floor(Math.random() * palabrasCastellano.length)];
        let mayus = palabra.charAt(0).toUpperCase() + palabra.slice(1);
        let num = Math.floor(Math.random() * 900 + 100); // 3 dígitos
        let letras = 'abcdefghijklmnopqrstuvwxyz';
        let extra = '';
        for (let i = 0; i < 6 - palabra.length; i++) {
            extra += letras.charAt(Math.floor(Math.random() * letras.length));
        }
        let base = mayus + extra + num;
        base = base.slice(0,9); // Asegura 9 caracteres
        // Insertar un punto en una posición aleatoria (excepto al principio)
        let pos = Math.floor(Math.random() * (base.length - 1)) + 1;
        base = base.slice(0, pos) + '.' + base.slice(pos);
        base = base.slice(0,10); // Mantener longitud máxima de 10
        return base;
    }
    document.addEventListener('DOMContentLoaded', function() {
        // Eliminar cualquier botón sugerir existente en el HTML
        document.querySelectorAll('#sugerir-contrasena, #btn-sugerir').forEach(b => b.remove());
        const input = document.querySelector('input[name="contrasena"]');
        if (input && !document.getElementById('btn-sugerir')) {
            // Mostrar la contraseña
            input.type = 'text';
            // Botón actualizar (solo uno)
            let btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-sm btn-info ms-2';
            btn.id = 'btn-sugerir';
            btn.title = 'Generar contraseña aleatoria';
            btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i>';
            // Igualar la altura y pegar el botón al input
            btn.style.height = input.offsetHeight + 'px';
            btn.style.width = btn.style.height;
            btn.style.fontSize = '1.1em';
            btn.querySelector('i').style.fontSize = '2em';
            btn.style.marginLeft = '0'; // Quitar separación visual
            btn.style.borderTopLeftRadius = '0';
            btn.style.borderBottomLeftRadius = '0';
            input.style.borderTopRightRadius = '0';
            input.style.borderBottomRightRadius = '0';
            // Eliminar clases de separación si existen
            btn.classList.remove('ms-2');
            input.parentNode.classList.remove('gap-2');
            btn.onclick = function() {
                input.value = generarContrasena();
            };
            // Insertar el botón justo después del input
            input.insertAdjacentElement('afterend', btn);
            // Opcional: agrupar input y botón en un div flex
            input.parentNode.classList.add('d-flex', 'align-items-center');
        }
    });
    </script>
</body>
</html>
