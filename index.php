<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/config.php';

function e(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$error = '';
$notice = '';
$editing = null;

try {
    $pdo = db();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrf = (string)($_POST['csrf'] ?? '');
        if (!hash_equals($_SESSION['csrf'], $csrf)) {
            throw new RuntimeException('La sesión del formulario venció. Recarga la página e inténtalo de nuevo.');
        }
        $action = (string)($_POST['action'] ?? '');

        if ($action === 'save') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
            $destinatario = trim((string)($_POST['destinatario'] ?? ''));
            $descripcion = trim((string)($_POST['descripcion'] ?? ''));
            $direccion = trim((string)($_POST['direccion'] ?? ''));

            if ($destinatario === '' || $descripcion === '' || $direccion === '') {
                throw new RuntimeException('Completa todos los campos antes de guardar.');
            }
            if (mb_strlen($destinatario) > 150 || mb_strlen($direccion) > 255) {
                throw new RuntimeException('El destinatario o la dirección superan la longitud permitida.');
            }

            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE envios SET destinatario = ?, descripcion = ?, direccion = ? WHERE id = ?');
                $stmt->execute([$destinatario, $descripcion, $direccion, $id]);
                $_SESSION['flash'] = 'Envío actualizado correctamente.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO envios (destinatario, descripcion, direccion) VALUES (?, ?, ?)');
                $stmt->execute([$destinatario, $descripcion, $direccion]);
                $_SESSION['flash'] = 'Envío registrado correctamente.';
            }
            header('Location: index.php');
            exit;
        }

        if ($action === 'delete') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if (!$id) {
                throw new RuntimeException('El envío seleccionado no es válido.');
            }
            $stmt = $pdo->prepare('DELETE FROM envios WHERE id = ?');
            $stmt->execute([$id]);
            $_SESSION['flash'] = 'Envío eliminado.';
            header('Location: index.php');
            exit;
        }
    }

    if (isset($_GET['editar'])) {
        $id = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT);
        if ($id) {
            $stmt = $pdo->prepare('SELECT * FROM envios WHERE id = ?');
            $stmt->execute([$id]);
            $editing = $stmt->fetch() ?: null;
        }
    }

    $search = trim((string)($_GET['q'] ?? ''));
    if ($search !== '') {
        $stmt = $pdo->prepare('SELECT * FROM envios WHERE destinatario LIKE ? OR descripcion LIKE ? OR direccion LIKE ? ORDER BY creado_en DESC, id DESC');
        $term = '%' . $search . '%';
        $stmt->execute([$term, $term, $term]);
        $envios = $stmt->fetchAll();
    } else {
        $envios = $pdo->query('SELECT * FROM envios ORDER BY creado_en DESC, id DESC')->fetchAll();
    }
    $total = (int)$pdo->query('SELECT COUNT(*) FROM envios')->fetchColumn();
    if (!empty($_SESSION['flash'])) {
        $notice = (string)$_SESSION['flash'];
        unset($_SESSION['flash']);
    }
} catch (Throwable $ex) {
    if ($ex instanceof PDOException) {
        error_log($ex->getMessage());
        $error = 'No se pudo conectar o consultar la base de datos. Verifica las credenciales, el nombre de la base de datos y los permisos del usuario.';
    } else {
        $error = $ex->getMessage();
    }
    $envios = $envios ?? [];
    $total = $total ?? 0;
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Gestión de envíos</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="overlay"></div>
<main class="container">
    <header class="topbar">
        <div class="brand-mark">↗</div>
        <div>
            <p class="eyebrow">CENTRO LOGÍSTICO</p>
            <h1>Gestión de envíos</h1>
            <p class="subtitle">Registra, consulta y administra tus entregas desde un solo lugar.</p>
        </div>
        <div class="total"><span>Envíos registrados</span><strong><?= (int)$total ?></strong></div>
    </header>

    <?php if ($notice !== ''): ?><div class="alert success"><?= e($notice) ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

    <section class="panel form-panel">
        <div class="section-heading">
            <div>
                <p class="eyebrow"><?= $editing ? 'ACTUALIZACIÓN' : 'NUEVO REGISTRO' ?></p>
                <h2><?= $editing ? 'Editar envío' : 'Registrar un envío' ?></h2>
            </div>
            <span class="step"><?= $editing ? 'Editar' : '+ Nuevo' ?></span>
        </div>
        <form method="post" action="index.php" class="shipment-form">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= e((string)($editing['id'] ?? '')) ?>">
            <label>Destinatario
                <input name="destinatario" maxlength="150" required placeholder="Nombre de quien recibe" value="<?= e($editing['destinatario'] ?? '') ?>">
            </label>
            <label>Dirección de entrega
                <input name="direccion" maxlength="255" required placeholder="Calle, número, ciudad..." value="<?= e($editing['direccion'] ?? '') ?>">
            </label>
            <label class="full">Descripción del envío
                <textarea name="descripcion" rows="3" required placeholder="Describe el paquete o su contenido"><?= e($editing['descripcion'] ?? '') ?></textarea>
            </label>
            <div class="form-actions full">
                <?php if ($editing): ?><a class="btn btn-ghost" href="index.php">Cancelar</a><?php endif; ?>
                <button class="btn btn-primary" type="submit"><?= $editing ? 'Guardar cambios' : 'Registrar envío' ?></button>
            </div>
        </form>
    </section>

    <section class="panel list-panel">
        <div class="section-heading list-heading">
            <div>
                <p class="eyebrow">SEGUIMIENTO</p>
                <h2>Envíos registrados</h2>
            </div>
            <form class="search" method="get" action="index.php">
                <input type="search" name="q" placeholder="Buscar envíos..." value="<?= e($_GET['q'] ?? '') ?>">
                <button class="btn btn-dark" type="submit">Buscar</button>
                <?php if (!empty($_GET['q'])): ?><a class="clear" href="index.php">Limpiar</a><?php endif; ?>
            </form>
        </div>

        <?php if (!$envios): ?>
            <div class="empty">
                <div class="empty-icon">□</div>
                <h3><?= !empty($_GET['q']) ? 'No se encontraron resultados' : 'Aún no hay envíos' ?></h3>
                <p><?= !empty($_GET['q']) ? 'Prueba con otro término de búsqueda.' : 'Completa el formulario para registrar el primer envío.' ?></p>
            </div>
        <?php else: ?>
            <div class="table-wrap">
            <table>
                <thead><tr><th>Destinatario</th><th>Descripción</th><th>Dirección</th><th>Fecha de registro</th><th>Acciones</th></tr></thead>
                <tbody>
                <?php foreach ($envios as $envio): ?>
                    <tr>
                        <td data-label="Destinatario"><strong><?= e($envio['destinatario']) ?></strong><small>Envío #<?= (int)$envio['id'] ?></small></td>
                        <td data-label="Descripción"><?= nl2br(e($envio['descripcion'])) ?></td>
                        <td data-label="Dirección"><?= e($envio['direccion']) ?></td>
                        <td data-label="Fecha"><?= e(date('d/m/Y H:i', strtotime($envio['creado_en']))) ?></td>
                        <td data-label="Acciones">
                            <div class="actions">
                                <a class="btn btn-small btn-edit" href="index.php?editar=<?= (int)$envio['id'] ?>">Editar</a>
                                <form method="post" action="index.php" onsubmit="return confirm('¿Seguro que deseas eliminar este envío?');">
                                    <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$envio['id'] ?>">
                                    <button class="btn btn-small btn-delete" type="submit">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </section>
    <footer>Gestión de envíos · Aplicación PHP + MySQL</footer>
</main>
</body>
</html>
