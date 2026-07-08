<?php include __DIR__ . '/../partials/header.php'; ?>

<main class="pagina-inicio pagina-inicio--landing">

    <section class="hero-framefy" aria-label="Bienvenida a Framefy">
        <div class="hero-framefy__fondo"></div>
        <div class="hero-framefy__contenido contenedor">
            <h1 class="hero-framefy__titulo">
                Encuentra tu próxima <span>historia</span> favorita
            </h1>
            <p class="hero-framefy__texto">
                Framefy recomienda películas y series según tus gustos, intereses y géneros favoritos.
                Inicia sesión o regístrate para entrar a la app.
            </p>
            <div class="hero-framefy__acciones">
                <?php if (!empty($nombreUsuario)): ?>
                    <a href="<?= htmlspecialchars($url('catalogo'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn--primario btn--hero">Entrar a Framefy <span aria-hidden="true">→</span></a>
                    <a href="<?= htmlspecialchars($url('perfil'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn--cristal btn--hero">Mi perfil</a>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($url('auth'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn--cristal btn--hero">Iniciar sesión</a>
                    <a href="<?= htmlspecialchars($url('auth', ['tab' => 'registro']), ENT_QUOTES, 'UTF-8') ?>" class="btn btn--primario btn--hero">Regístrate <span aria-hidden="true">→</span></a>
                <?php endif; ?>
            </div>

            <div class="hero-framefy__beneficios" aria-label="Beneficios de Framefy">
                <article>
                    <span class="beneficio-icono">♙</span>
                    <div>
                        <h2>Recomendaciones personalizadas</h2>
                        <p>Hechas a tu medida con tus géneros y actividad reciente.</p>
                    </div>
                </article>
                <article>
                    <span class="beneficio-icono">▻</span>
                    <div>
                        <h2>Películas y series</h2>
                        <p>Encuentra opciones para ver sin perder tiempo buscando.</p>
                    </div>
                </article>
                <article>
                    <span class="beneficio-icono">▣</span>
                    <div>
                        <h2>Disponible en tus plataformas</h2>
                        <p>Organiza lo que quieres ver sin complicarte.</p>
                    </div>
                </article>
            </div>

            <div class="hero-framefy__plataformas">
                <span>Disponible en:</span>
                <img src="<?= htmlspecialchars($asset('img/logo-netflix.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Netflix" class="logo-plataforma">
                <img src="<?= htmlspecialchars($asset('img/logo-prime-video.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Prime Video" class="logo-plataforma">
                <img src="<?= htmlspecialchars($asset('img/logo-disney-plus.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Disney+" class="logo-plataforma">
                <img src="<?= htmlspecialchars($asset('img/logo-hbo-max.png'), ENT_QUOTES, 'UTF-8') ?>" alt="HBO Max" class="logo-plataforma">
                <img src="<?= htmlspecialchars($asset('img/logo-apple-tv.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Apple TV+" class="logo-plataforma">
            </div>
        </div>
    </section>

</main>

<?php include __DIR__ . '/../partials/footer.php'; ?>
