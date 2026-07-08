<?php
include __DIR__ . '/../partials/header.php';

$generosDisponibles = $generosDisponibles ?? [];
$generosSeleccionados = $generosSeleccionados ?? [];
$tipoPreferido = in_array($tipoPreferido ?? 'ambos', ['pelicula', 'serie', 'ambos'], true) ? ($tipoPreferido ?? 'ambos') : 'ambos';
$duracionPreferida = in_array($duracionPreferida ?? 'media', ['corta', 'media', 'larga'], true) ? ($duracionPreferida ?? 'media') : 'media';
$temasSeleccionados = $temasSeleccionados ?? [];
$erroresPreferencias = $erroresPreferencias ?? [];

$tipos = [
    'pelicula' => ['label' => 'Películas', 'icono' => 'clapperboard'],
    'serie' => ['label' => 'Series', 'icono' => 'film'],
    'ambos' => ['label' => 'Ambos', 'icono' => 'list-plus'],
];

$temas = [
    'aventura' => ['label' => 'Aventura', 'icono' => 'compass'],
    'misterio' => ['label' => 'Misterio', 'icono' => 'search'],
    'inspirador' => ['label' => 'Inspirador', 'icono' => 'star'],
    'futurista' => ['label' => 'Futurista', 'icono' => 'rocket'],
    'familiar' => ['label' => 'Familiar', 'icono' => 'users'],
];

$duraciones = [
    'corta' => ['label' => 'Corta', 'detalle' => 'Menos de 90 min'],
    'media' => ['label' => 'Media', 'detalle' => '90 a 150 min'],
    'larga' => ['label' => 'Larga', 'detalle' => 'Más de 150 min'],
];
?>

<main class="preferencias-page">
    <section class="preferencias-contenedor">
        <header class="preferencias-header">
            <h1>Cuéntanos tus gustos</h1>
            <p>Selecciona tus preferencias para personalizar tus recomendaciones.</p>
        </header>

        <?php if (!empty($erroresPreferencias['general'])): ?>
            <div class="alerta alerta--error" role="alert">
                <span class="alerta__icono"><?= app_icon('shield') ?></span>
                <span><?= htmlspecialchars($erroresPreferencias['general'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        <?php endif; ?>

        <form id="preferencias-form-real" method="POST" action="<?= htmlspecialchars($url('preferencias'), ENT_QUOTES, 'UTF-8') ?>" class="preferencias-card" novalidate>
            <?= csrf_input() ?>
            <input type="hidden" name="accion" value="guardar_preferencias">

            <section class="preferencias-fila">
                <div class="preferencias-fila__intro">
                    <span class="preferencias-fila__icono"><?= app_icon('clapperboard') ?></span>
                    <div>
                        <h2>Géneros favoritos</h2>
                        <p>Elige todos los que te gusten.</p>
                    </div>
                </div>
                <div class="preferencias-opciones preferencias-opciones--generos">
                    <?php foreach ($generosDisponibles as $genero): ?>
                        <?php
                            $gId = (int)$genero['id'];
                            $gNombre = htmlspecialchars($genero['nombre'], ENT_QUOTES, 'UTF-8');
                            $checked = in_array($gId, $generosSeleccionados, true);
                        ?>
                        <input type="checkbox"
                               class="preferencia-check"
                               id="pref-genero-<?= $gId ?>"
                               name="generos[]"
                               value="<?= $gId ?>"
                               <?= $checked ? 'checked' : '' ?>>
                        <label for="pref-genero-<?= $gId ?>">
                            <?= app_genero_icon((string)$genero['nombre']) ?>
                            <?= $gNombre ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="preferencias-fila">
                <div class="preferencias-fila__intro">
                    <span class="preferencias-fila__icono"><?= app_icon('play') ?></span>
                    <div>
                        <h2>¿Qué prefieres ver?</h2>
                        <p>Elige el tipo de contenido que más disfrutas.</p>
                    </div>
                </div>
                <div class="preferencias-opciones preferencias-opciones--compactas">
                    <?php foreach ($tipos as $valor => $tipo): ?>
                        <input type="radio"
                               class="preferencia-check"
                               id="pref-tipo-<?= $valor ?>"
                               name="tipo_preferido"
                               value="<?= $valor ?>"
                               <?= $tipoPreferido === $valor ? 'checked' : '' ?>>
                        <label for="pref-tipo-<?= $valor ?>">
                            <?= app_icon($tipo['icono']) ?>
                            <?= htmlspecialchars($tipo['label'], ENT_QUOTES, 'UTF-8') ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="preferencias-fila">
                <div class="preferencias-fila__intro">
                    <span class="preferencias-fila__icono"><?= app_icon('sparkles') ?></span>
                    <div>
                        <h2>Estados de ánimo o temas</h2>
                        <p>Selecciona los que mejor te representen.</p>
                    </div>
                </div>
                <div class="preferencias-opciones">
                    <?php foreach ($temas as $valor => $tema): ?>
                        <input type="checkbox"
                               class="preferencia-check"
                               id="pref-tema-<?= $valor ?>"
                               name="temas[]"
                               value="<?= $valor ?>"
                               <?= in_array($valor, $temasSeleccionados, true) ? 'checked' : '' ?>>
                        <label for="pref-tema-<?= $valor ?>">
                            <?= app_icon($tema['icono']) ?>
                            <?= htmlspecialchars($tema['label'], ENT_QUOTES, 'UTF-8') ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="preferencias-fila">
                <div class="preferencias-fila__intro">
                    <span class="preferencias-fila__icono"><?= app_icon('calendar') ?></span>
                    <div>
                        <h2>Duración preferida</h2>
                        <p>¿Cuánto tiempo sueles tener para ver contenido?</p>
                    </div>
                </div>
                <div class="preferencias-opciones preferencias-opciones--compactas">
                    <?php foreach ($duraciones as $valor => $duracion): ?>
                        <input type="radio"
                               class="preferencia-check"
                               id="pref-duracion-<?= $valor ?>"
                               name="duracion_preferida"
                               value="<?= $valor ?>"
                               <?= $duracionPreferida === $valor ? 'checked' : '' ?>>
                        <label for="pref-duracion-<?= $valor ?>">
                            <?= app_icon('calendar') ?>
                            <span>
                                <?= htmlspecialchars($duracion['label'], ENT_QUOTES, 'UTF-8') ?>
                                <small><?= htmlspecialchars($duracion['detalle'], ENT_QUOTES, 'UTF-8') ?></small>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </section>
        </form>

        <div class="preferencias-acciones">
            <button class="btn btn--primario preferencias-submit" type="submit" form="preferencias-form-real">
                <?= app_icon('sparkles') ?> Ver mis recomendaciones <?= app_icon('chevron-right') ?>
            </button>
            <form id="preferencias-omitir" method="POST" action="<?= htmlspecialchars($url('preferencias'), ENT_QUOTES, 'UTF-8') ?>">
                <?= csrf_input() ?>
                <input type="hidden" name="accion" value="omitir_preferencias">
                <button class="btn btn--cristal preferencias-omitir" type="submit">Más tarde</button>
            </form>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../partials/footer.php'; ?>
