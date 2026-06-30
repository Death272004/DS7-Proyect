<?php
/**
 * views/partials/footer.php
 * Incluir al final de cada vista.
 * La ruta al JS es relativa: views/subcarpeta/archivo.php -> ../../public/js/
 */
$rutaPublic = '../../public';
?>
<footer class="footer" role="contentinfo">
    <p>&copy; <?= date('Y') ?> CineMatch &mdash; Proyecto Final &mdash; Desarrollo de Software VII</p>
</footer>

<script src="<?= $rutaPublic ?>/js/validaciones.js"></script>
</body>
</html>
