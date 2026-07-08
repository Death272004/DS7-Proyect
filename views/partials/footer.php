<?php
$asset = $asset ?? static fn (string $ruta): string => app_asset($ruta);
?>
<footer class="footer" role="contentinfo">
    <p>&copy; <?= date('Y') ?> Framefy &mdash; Proyecto Final &mdash; Desarrollo de Software VII</p>
</footer>

<script src="<?= htmlspecialchars($asset('js/validaciones.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
