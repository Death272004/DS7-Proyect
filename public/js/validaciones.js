/**
 * validaciones.js
 * Validaciones del lado del cliente (campos del formulario).
 * Sin manejo de sesiones — eso va en el backend.
 */

/* ============================================================
   UTILIDADES
   ============================================================ */
function mostrarErrorCampo(campo, mensaje) {
    limpiarErrorCampo(campo);
    campo.classList.add('campo--error');
    campo.classList.remove('campo--exito');
    const contenedor = campo.closest('.grupo-campo') || campo.parentElement;
    const span = document.createElement('span');
    span.className   = 'error-campo';
    span.textContent = mensaje;
    contenedor.appendChild(span);
}

function marcarCampoExito(campo) {
    limpiarErrorCampo(campo);
    campo.classList.remove('campo--error');
    campo.classList.add('campo--exito');
}

function limpiarErrorCampo(campo) {
    const contenedor = campo.closest('.grupo-campo') || campo.parentElement;
    const previo = contenedor.querySelector('.error-campo');
    if (previo) previo.remove();
    campo.classList.remove('campo--error', 'campo--exito');
}

const ICONOS_FRAMEFY = {
    alerta: '<svg class="icono" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>',
    check: '<svg class="icono" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>',
    eye: '<svg class="icono" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>',
    eyeOff: '<svg class="icono" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m2 2 20 20"/><path d="M6.7 6.7C3.8 8.7 2 12 2 12s3.5 7 10 7c1.6 0 3-.4 4.2-1"/><path d="M19.3 17.3C22 15.2 22 12 22 12s-3.5-7-10-7c-1.1 0-2.1.2-3 .5"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>',
    info: '<svg class="icono" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>',
    bookmark: '<svg class="icono" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21 12 17 5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2Z"/></svg>',
    moon: '<svg class="icono" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 7.4A9 9 0 1 1 12 3Z"/></svg>',
    sun: '<svg class="icono" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.9 4.9 1.4 1.4"/><path d="m17.7 17.7 1.4 1.4"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.3 17.7-1.4 1.4"/><path d="m19.1 4.9-1.4 1.4"/></svg>',
};

function mostrarAlertaFormulario(formulario, mensaje, tipo = 'error') {
    formulario.querySelectorAll('.alerta--dinamica').forEach(a => a.remove());
    const iconos = { error: ICONOS_FRAMEFY.alerta, exito: ICONOS_FRAMEFY.check, info: ICONOS_FRAMEFY.info };
    const div = document.createElement('div');
    div.className = `alerta alerta--${tipo} alerta--dinamica`;
    div.setAttribute('role', 'alert');
    div.innerHTML = `<span class="alerta__icono">${iconos[tipo]}</span><span>${mensaje}</span>`;
    formulario.prepend(div);
    if (tipo !== 'error') setTimeout(() => div.remove(), 4000);
}

function estaVacio(valor) {
    return valor.trim() === '';
}

function obtenerCookie(nombre) {
    const prefijo = `${nombre}=`;
    return document.cookie
        .split(';')
        .map(cookie => cookie.trim())
        .find(cookie => cookie.startsWith(prefijo))
        ?.slice(prefijo.length) || '';
}

function guardarCookie(nombre, valor, segundos = 365 * 24 * 3600) {
    document.cookie = `${nombre}=${encodeURIComponent(valor)};path=/;max-age=${segundos};SameSite=Lax`;
}

function leerGuardados() {
    try {
        const guardados = JSON.parse(decodeURIComponent(obtenerCookie('framefy_guardados') || '[]'));
        return Array.isArray(guardados) ? guardados.map(id => String(id)) : [];
    } catch (error) {
        return [];
    }
}

/* ============================================================
   REGLAS DE VALIDACIÓN
   ============================================================ */
const REGEX = {
    // Solo letras (incluye acentos y ñ), espacios, entre 2 y 50 caracteres
    nombre:    /^[a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]{2,50}$/,
    // Email estándar
    email:     /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/,
    // Mínimo 8 caracteres, al menos 1 mayúscula, 1 minúscula, 1 número y 1 carácter especial ($#€%-_)
    contrasena:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[$#€%\-_]).{8,}$/,
    // Año de 4 dígitos
    anio:      /^\d{4}$/,
    // Duración 1-999 minutos
    duracion:  /^[1-9][0-9]{0,2}$/,
};

const MENSAJES = {
    nombre:    'Solo letras y espacios, entre 2 y 50 caracteres.',
    email:     'Ingresa un correo electrónico válido.',
    contrasena:'Mínimo 8 caracteres, con mayúscula, minúscula, número y carácter especial ($#€%-_).',
    anio:      'Ingresa un año válido de 4 dígitos.',
    duracion:  'La duración debe estar entre 1 y 999 minutos.',
};

/* ============================================================
   INDICADOR DE FUERZA DE CONTRASEÑA
   ============================================================ */
function calcularFuerza(contrasena) {
    let puntos = 0;
    if (contrasena.length >= 8)                         puntos++;
    if (contrasena.length >= 12)                        puntos++;
    if (/[A-Z]/.test(contrasena))                      puntos++;
    if (/[a-z]/.test(contrasena))                      puntos++;
    if (/\d/.test(contrasena))                         puntos++;
    if (/[$#€%\-_]/.test(contrasena))                  puntos++;
    return Math.min(Math.floor(puntos / 1.5), 4);
}

function actualizarBarraFuerza(inputContrasena, contenedor) {
    const barra  = contenedor.querySelector('.barra-contrasena__relleno');
    const texto  = contenedor.querySelector('.texto-fuerza');
    if (!barra) return;

    const niveles = [
        { pct: 0,   color: 'transparent', label: '' },
        { pct: 25,  color: '#e05c5c',     label: 'Muy débil' },
        { pct: 50,  color: '#e0914b',     label: 'Débil' },
        { pct: 75,  color: '#e8b84b',     label: 'Aceptable' },
        { pct: 100, color: '#4caf82',     label: 'Segura ✓' },
    ];
    const nivel = niveles[calcularFuerza(inputContrasena.value)];
    barra.style.width           = nivel.pct + '%';
    barra.style.backgroundColor = nivel.color;
    if (texto) texto.textContent = nivel.label;
}

/* ============================================================
   TOGGLE VER / OCULTAR CONTRASEÑA
   ============================================================ */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.btn-ver-contrasena').forEach(btn => {
        btn.addEventListener('click', () => {
            const wrapper = btn.closest('.campo-contrasena-wrapper');
            const input   = wrapper.querySelector('input');
            const mostrar = input.type === 'password';
            input.type      = mostrar ? 'text' : 'password';
            btn.innerHTML = mostrar ? ICONOS_FRAMEFY.eyeOff : ICONOS_FRAMEFY.eye;
        });
    });

    /* ============================================================
       TABS: LOGIN / REGISTRO
       ============================================================ */
    const tabs = document.querySelectorAll('.auth-tab');
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => {
                t.classList.remove('activo');
                t.setAttribute('aria-selected', 'false');
            });
            document.querySelectorAll('.auth-formulario').forEach(f => f.classList.remove('activo'));
            tab.classList.add('activo');
            tab.setAttribute('aria-selected', 'true');
            document.getElementById('tab-' + tab.dataset.tab)?.classList.add('activo');

            const shell = tab.closest('.auth-shell');
            if (shell) {
                shell.classList.toggle('auth-shell--login', tab.dataset.tab === 'login');
                shell.classList.toggle('auth-shell--registro', tab.dataset.tab === 'registro');
            }
        });
    });

    document.querySelectorAll('[data-auth-switch]').forEach(btn => {
        btn.addEventListener('click', () => {
            const tabDestino = document.querySelector(`.auth-tab[data-tab="${btn.dataset.authSwitch}"]`);
            tabDestino?.click();
        });
    });

    /* ============================================================
       FORMULARIO LOGIN
       ============================================================ */
    const formLogin = document.getElementById('formulario-login');
    if (formLogin) {
        const campoEmail      = formLogin.querySelector('#login-email');
        const campoContrasena = formLogin.querySelector('#login-contrasena');
        const botonSubmit     = formLogin.querySelector('.auth-submit');
        const contadorBloqueo = formLogin.querySelector('.auth-bloqueo-countdown');
        let segundosBloqueo   = parseInt(formLogin.dataset.bloqueoSegundos || '0', 10);

        const controlesLogin = formLogin.querySelectorAll('input, button');

        const actualizarBloqueoLogin = () => {
            if (!contadorBloqueo || !botonSubmit) return;

            if (segundosBloqueo <= 0) {
                controlesLogin.forEach(control => control.disabled = false);
                formLogin.classList.remove('auth-login-bloqueado');
                formLogin.dataset.bloqueoSegundos = '0';
                botonSubmit.textContent = 'Iniciar sesión';
                contadorBloqueo.textContent = 'Ya puedes intentar iniciar sesión nuevamente.';
                return;
            }

            const textoTiempo = segundosBloqueo >= 60
                ? '1 minuto'
                : `${segundosBloqueo} segundo${segundosBloqueo === 1 ? '' : 's'}`;
            contadorBloqueo.innerHTML = `Panel bloqueado. Disponible en <strong>${textoTiempo}</strong>.`;
            botonSubmit.textContent = 'Panel bloqueado';
        };

        if (segundosBloqueo > 0) {
            controlesLogin.forEach(control => control.disabled = true);
            actualizarBloqueoLogin();
            const intervaloBloqueo = setInterval(() => {
                segundosBloqueo -= 1;
                actualizarBloqueoLogin();
                if (segundosBloqueo <= 0) clearInterval(intervaloBloqueo);
            }, 1000);
        }

        campoEmail.addEventListener('blur', () => {
            if (segundosBloqueo > 0) return;
            if (estaVacio(campoEmail.value)) {
                mostrarErrorCampo(campoEmail, 'El correo es obligatorio.');
            } else if (!REGEX.email.test(campoEmail.value.trim())) {
                mostrarErrorCampo(campoEmail, MENSAJES.email);
            } else {
                marcarCampoExito(campoEmail);
            }
        });

        campoContrasena.addEventListener('blur', () => {
            if (segundosBloqueo > 0) return;
            if (estaVacio(campoContrasena.value)) {
                mostrarErrorCampo(campoContrasena, 'La contraseña es obligatoria.');
            } else {
                marcarCampoExito(campoContrasena);
            }
        });

        formLogin.addEventListener('submit', (e) => {
            e.preventDefault();
            if (segundosBloqueo > 0) return;
            let valido = true;

            if (estaVacio(campoEmail.value)) {
                mostrarErrorCampo(campoEmail, 'El correo es obligatorio.'); valido = false;
            } else if (!REGEX.email.test(campoEmail.value.trim())) {
                mostrarErrorCampo(campoEmail, MENSAJES.email); valido = false;
            } else {
                marcarCampoExito(campoEmail);
            }

            if (estaVacio(campoContrasena.value)) {
                mostrarErrorCampo(campoContrasena, 'La contraseña es obligatoria.'); valido = false;
            } else {
                marcarCampoExito(campoContrasena);
            }

            if (valido) formLogin.submit();
        });
    }

    /* ============================================================
       FORMULARIO REGISTRO
       ============================================================ */
    const formRegistro = document.getElementById('formulario-registro');
    if (formRegistro) {
        const campoNombre     = formRegistro.querySelector('#registro-nombre');
        const campoEmail      = formRegistro.querySelector('#registro-email');
        const campoPass       = formRegistro.querySelector('#registro-contrasena');
        const campoConfirmar  = formRegistro.querySelector('#registro-confirmar');
        const barraContenedor = formRegistro.querySelector('.bloque-fuerza');

        // Fuerza en tiempo real
        if (campoPass && barraContenedor) {
            campoPass.addEventListener('input', () => actualizarBarraFuerza(campoPass, barraContenedor));
        }

        campoNombre.addEventListener('blur', () => {
            if (estaVacio(campoNombre.value)) {
                mostrarErrorCampo(campoNombre, 'El nombre es obligatorio.');
            } else if (!REGEX.nombre.test(campoNombre.value.trim())) {
                mostrarErrorCampo(campoNombre, MENSAJES.nombre);
            } else {
                marcarCampoExito(campoNombre);
            }
        });

        campoEmail.addEventListener('blur', () => {
            if (estaVacio(campoEmail.value)) {
                mostrarErrorCampo(campoEmail, 'El correo es obligatorio.');
            } else if (!REGEX.email.test(campoEmail.value.trim())) {
                mostrarErrorCampo(campoEmail, MENSAJES.email);
            } else {
                marcarCampoExito(campoEmail);
            }
        });

        campoPass.addEventListener('blur', () => {
            if (estaVacio(campoPass.value)) {
                mostrarErrorCampo(campoPass, 'La contraseña es obligatoria.');
            } else if (!REGEX.contrasena.test(campoPass.value)) {
                mostrarErrorCampo(campoPass, MENSAJES.contrasena);
            } else {
                marcarCampoExito(campoPass);
            }
        });

        campoConfirmar.addEventListener('blur', () => {
            if (estaVacio(campoConfirmar.value)) {
                mostrarErrorCampo(campoConfirmar, 'Confirma tu contraseña.');
            } else if (campoConfirmar.value !== campoPass.value) {
                mostrarErrorCampo(campoConfirmar, 'Las contraseñas no coinciden.');
            } else {
                marcarCampoExito(campoConfirmar);
            }
        });

        formRegistro.addEventListener('submit', (e) => {
            e.preventDefault();
            let valido = true;

            // Nombre
            if (estaVacio(campoNombre.value)) {
                mostrarErrorCampo(campoNombre, 'El nombre es obligatorio.'); valido = false;
            } else if (!REGEX.nombre.test(campoNombre.value.trim())) {
                mostrarErrorCampo(campoNombre, MENSAJES.nombre); valido = false;
            } else { marcarCampoExito(campoNombre); }

            // Email
            if (estaVacio(campoEmail.value)) {
                mostrarErrorCampo(campoEmail, 'El correo es obligatorio.'); valido = false;
            } else if (!REGEX.email.test(campoEmail.value.trim())) {
                mostrarErrorCampo(campoEmail, MENSAJES.email); valido = false;
            } else { marcarCampoExito(campoEmail); }

            // Contraseña
            if (estaVacio(campoPass.value)) {
                mostrarErrorCampo(campoPass, 'La contraseña es obligatoria.'); valido = false;
            } else if (!REGEX.contrasena.test(campoPass.value)) {
                mostrarErrorCampo(campoPass, MENSAJES.contrasena); valido = false;
            } else { marcarCampoExito(campoPass); }

            // Confirmar
            if (estaVacio(campoConfirmar.value)) {
                mostrarErrorCampo(campoConfirmar, 'Confirma tu contraseña.'); valido = false;
            } else if (campoConfirmar.value !== campoPass.value) {
                mostrarErrorCampo(campoConfirmar, 'Las contraseñas no coinciden.'); valido = false;
            } else { marcarCampoExito(campoConfirmar); }

            if (valido) formRegistro.submit();
        });
    }

    /* ============================================================
       FORMULARIO PERFIL
       ============================================================ */
    const formPerfil = document.getElementById('formulario-perfil');
    if (formPerfil) {
        const campoNombre    = formPerfil.querySelector('#perfil-nombre');
        const campoEmail     = formPerfil.querySelector('#perfil-email');
        const campoPassNueva = formPerfil.querySelector('#perfil-contrasena-nueva');
        const campoConfirmar = formPerfil.querySelector('#perfil-confirmar-contrasena');
        const barraContenedor = formPerfil.querySelector('.bloque-fuerza');

        if (campoPassNueva && barraContenedor) {
            campoPassNueva.addEventListener('input', () => actualizarBarraFuerza(campoPassNueva, barraContenedor));
        }

        if (campoNombre) {
            campoNombre.addEventListener('blur', () => {
                if (estaVacio(campoNombre.value)) {
                    mostrarErrorCampo(campoNombre, 'El nombre es obligatorio.');
                } else if (!REGEX.nombre.test(campoNombre.value.trim())) {
                    mostrarErrorCampo(campoNombre, MENSAJES.nombre);
                } else { marcarCampoExito(campoNombre); }
            });
        }

        if (campoEmail) {
            campoEmail.addEventListener('blur', () => {
                if (estaVacio(campoEmail.value)) {
                    mostrarErrorCampo(campoEmail, 'El correo es obligatorio.');
                } else if (!REGEX.email.test(campoEmail.value.trim())) {
                    mostrarErrorCampo(campoEmail, MENSAJES.email);
                } else { marcarCampoExito(campoEmail); }
            });
        }

        formPerfil.addEventListener('submit', (e) => {
            e.preventDefault();
            let valido = true;

            if (campoNombre) {
                if (estaVacio(campoNombre.value)) {
                    mostrarErrorCampo(campoNombre, 'El nombre es obligatorio.'); valido = false;
                } else if (!REGEX.nombre.test(campoNombre.value.trim())) {
                    mostrarErrorCampo(campoNombre, MENSAJES.nombre); valido = false;
                } else { marcarCampoExito(campoNombre); }
            }

            if (campoEmail) {
                if (estaVacio(campoEmail.value)) {
                    mostrarErrorCampo(campoEmail, 'El correo es obligatorio.'); valido = false;
                } else if (!REGEX.email.test(campoEmail.value.trim())) {
                    mostrarErrorCampo(campoEmail, MENSAJES.email); valido = false;
                } else { marcarCampoExito(campoEmail); }
            }

            // Contraseña solo si se escribió algo
            if (campoPassNueva && !estaVacio(campoPassNueva.value)) {
                if (!REGEX.contrasena.test(campoPassNueva.value)) {
                    mostrarErrorCampo(campoPassNueva, MENSAJES.contrasena); valido = false;
                } else { marcarCampoExito(campoPassNueva); }

                if (campoConfirmar && campoConfirmar.value !== campoPassNueva.value) {
                    mostrarErrorCampo(campoConfirmar, 'Las contraseñas no coinciden.'); valido = false;
                } else if (campoConfirmar) { marcarCampoExito(campoConfirmar); }
            }

            if (valido) formPerfil.submit();
        });
    }

    /* ============================================================
       FORMULARIO PREFERENCIAS (géneros)
       ============================================================ */
    const formPreferencias = document.getElementById('formulario-preferencias');
    if (formPreferencias) {
        formPreferencias.addEventListener('submit', (e) => {
            e.preventDefault();
            const seleccionados = formPreferencias.querySelectorAll('input[name="generos[]"]:checked');
            if (seleccionados.length === 0) {
                mostrarAlertaFormulario(formPreferencias, 'Selecciona al menos un género favorito.', 'error');
                return;
            }
            if (seleccionados.length > 10) {
                mostrarAlertaFormulario(formPreferencias, 'Puedes seleccionar máximo 10 géneros.', 'error');
                return;
            }
            formPreferencias.submit();
        });
    }

    /* ============================================================
       CATÁLOGO: GUARDADOS Y DESTACADOS
       ============================================================ */
    const botonesGuardar = document.querySelectorAll('.js-guardar-contenido');
    if (botonesGuardar.length) {
        let idsGuardados = leerGuardados();

        const pintarEstadoGuardado = (boton) => {
            const id = String(boton.dataset.id || '');
            const estaGuardado = idsGuardados.includes(id);
            boton.classList.toggle('guardado', estaGuardado);
            boton.setAttribute('aria-pressed', estaGuardado ? 'true' : 'false');

            if (boton.classList.contains('btn')) {
                boton.innerHTML = `${ICONOS_FRAMEFY.bookmark} ${estaGuardado ? 'Guardado' : 'Guardar'}`;
            }
        };

        botonesGuardar.forEach(boton => {
            pintarEstadoGuardado(boton);
            boton.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();

                const id = String(boton.dataset.id || '');
                if (!id) return;

                idsGuardados = idsGuardados.includes(id)
                    ? idsGuardados.filter(item => item !== id)
                    : [...idsGuardados, id];

                guardarCookie('framefy_guardados', JSON.stringify(idsGuardados));
                document.querySelectorAll(`.js-guardar-contenido[data-id="${CSS.escape(id)}"]`).forEach(pintarEstadoGuardado);
            });
        });
    }

    document.querySelectorAll('.catalogo-destacados-rotador').forEach(rotador => {
        const destacados = Array.from(rotador.querySelectorAll('.catalogo-destacado'));
        if (destacados.length <= 1) return;

        let indiceActivo = Math.max(0, destacados.findIndex(item => item.classList.contains('activo')));
        const intervalo = Math.max(1500, parseInt(rotador.dataset.intervalo || '3000', 10));

        setInterval(() => {
            destacados[indiceActivo].classList.remove('activo');
            indiceActivo = (indiceActivo + 1) % destacados.length;
            destacados[indiceActivo].classList.add('activo');
        }, intervalo);
    });

    /* ============================================================
       FORMULARIO CONTENIDO (admin)
       ============================================================ */
    const formContenido = document.getElementById('formulario-contenido');
    if (formContenido) {
        const campoTitulo       = formContenido.querySelector('#contenido-titulo');
        const campoSinopsis     = formContenido.querySelector('#contenido-sinopsis');
        const campoAnio         = formContenido.querySelector('#contenido-anio');
        const campoDuracion     = formContenido.querySelector('#contenido-duracion');
        const campoCalificacion = formContenido.querySelector('#contenido-calificacion');
        const campoTipo         = formContenido.querySelector('#contenido-tipo');
        const campoGenero       = formContenido.querySelector('#contenido-genero');
        const campoId           = formContenido.querySelector('#contenido-id');
        const campoImagen       = formContenido.querySelector('#contenido-imagen');
        const campoTrailer      = formContenido.querySelector('#contenido-trailer');
        const modalTitulo       = document.getElementById('modal-titulo');
        const botonSubmit       = document.getElementById('contenido-submit');
        const botonAgregar      = document.getElementById('btn-agregar-contenido');

        const limpiarFormularioContenido = () => {
            formContenido.reset();
            formContenido.querySelectorAll('.error-campo').forEach(error => error.remove());
            formContenido.querySelectorAll('.campo--error, .campo--exito').forEach(campo => {
                campo.classList.remove('campo--error', 'campo--exito');
            });
            if (campoId) campoId.value = '';
            if (modalTitulo) modalTitulo.textContent = 'Agregar título';
            if (botonSubmit) botonSubmit.textContent = 'Guardar';
        };

        botonAgregar?.addEventListener('click', limpiarFormularioContenido);

        document.querySelectorAll('.btn-editar-contenido').forEach(btn => {
            btn.addEventListener('click', () => {
                limpiarFormularioContenido();
                if (campoId) campoId.value = btn.dataset.id || '';
                if (campoTitulo) campoTitulo.value = btn.dataset.titulo || '';
                if (campoTipo) campoTipo.value = btn.dataset.tipo || '';
                if (campoAnio) campoAnio.value = btn.dataset.anio || '';
                if (campoDuracion) campoDuracion.value = btn.dataset.duracion || '';
                if (campoCalificacion) campoCalificacion.value = btn.dataset.calificacion || '';
                if (campoGenero) campoGenero.value = btn.dataset.genero || '';
                if (campoSinopsis) campoSinopsis.value = btn.dataset.sinopsis || '';
                if (campoImagen) campoImagen.value = btn.dataset.imagen || '';
                if (campoTrailer) campoTrailer.value = btn.dataset.trailer || '';
                if (modalTitulo) modalTitulo.textContent = 'Editar título';
                if (botonSubmit) botonSubmit.textContent = 'Actualizar';
                abrirModal('modal-contenido');
            });
        });

        formContenido.addEventListener('submit', (e) => {
            e.preventDefault();
            let valido = true;
            const anioActual = new Date().getFullYear();

            // Título
            if (!campoTitulo || estaVacio(campoTitulo.value)) {
                if (campoTitulo) mostrarErrorCampo(campoTitulo, 'El título es obligatorio.');
                valido = false;
            } else if (campoTitulo.value.trim().length > 200) {
                mostrarErrorCampo(campoTitulo, 'El título no puede superar 200 caracteres.');
                valido = false;
            } else { marcarCampoExito(campoTitulo); }

            // Tipo
            if (!campoTipo || estaVacio(campoTipo.value)) {
                if (campoTipo) mostrarErrorCampo(campoTipo, 'Selecciona el tipo de contenido.');
                valido = false;
            } else { marcarCampoExito(campoTipo); }

            // Género
            if (!campoGenero || estaVacio(campoGenero.value)) {
                if (campoGenero) mostrarErrorCampo(campoGenero, 'Selecciona un género.');
                valido = false;
            } else { marcarCampoExito(campoGenero); }

            // Año
            if (campoAnio && !estaVacio(campoAnio.value)) {
                const anioVal = parseInt(campoAnio.value, 10);
                if (!REGEX.anio.test(campoAnio.value) || anioVal < 1888 || anioVal > anioActual + 2) {
                    mostrarErrorCampo(campoAnio, `El año debe estar entre 1888 y ${anioActual + 2}.`);
                    valido = false;
                } else { marcarCampoExito(campoAnio); }
            }

            // Duración
            if (campoDuracion && !estaVacio(campoDuracion.value)) {
                if (!REGEX.duracion.test(campoDuracion.value)) {
                    mostrarErrorCampo(campoDuracion, MENSAJES.duracion); valido = false;
                } else { marcarCampoExito(campoDuracion); }
            }

            // Calificación
            if (campoCalificacion && !estaVacio(campoCalificacion.value)) {
                const cal = parseFloat(campoCalificacion.value);
                if (isNaN(cal) || cal < 0 || cal > 10) {
                    mostrarErrorCampo(campoCalificacion, 'La calificación debe ser entre 0 y 10.');
                    valido = false;
                } else { marcarCampoExito(campoCalificacion); }
            }

            // Sinopsis
            if (!campoSinopsis || estaVacio(campoSinopsis.value)) {
                if (campoSinopsis) mostrarErrorCampo(campoSinopsis, 'La sinopsis es obligatoria.');
                valido = false;
            } else if (campoSinopsis.value.trim().length < 10) {
                mostrarErrorCampo(campoSinopsis, 'La sinopsis debe tener al menos 10 caracteres.');
                valido = false;
            } else { marcarCampoExito(campoSinopsis); }

            if (valido) formContenido.submit();
        });
    }

    /* ============================================================
       BUSCADOR DEL CATÁLOGO (filtro en tiempo real)
       ============================================================ */
    const campoBusqueda = document.getElementById('busqueda-catalogo');
    if (campoBusqueda) {
        campoBusqueda.addEventListener('input', () => {
            const termino = campoBusqueda.value.trim().toLowerCase();
            document.querySelectorAll('.tarjeta-pelicula, .catalogo-card').forEach(card => {
                const titulo = (
                    card.querySelector('.tarjeta-pelicula__titulo')?.textContent ||
                    card.querySelector('.catalogo-card__info h3')?.textContent ||
                    card.getAttribute('aria-label') ||
                    ''
                ).toLowerCase();
                card.style.display = titulo.includes(termino) ? '' : 'none';
            });
        });
    }

    /* ============================================================
       TOGGLE TEMA CLARO / OSCURO (cookie)
       ============================================================ */
    const btnTema = document.getElementById('btn-toggle-tema');
    if (btnTema) {
        btnTema.addEventListener('click', () => {
            const esClaro   = document.body.classList.toggle('tema-claro');
            const nuevoTema = esClaro ? 'claro' : 'oscuro';
            document.cookie = `tema=${nuevoTema};path=/;max-age=${365*24*3600};SameSite=Lax`;
            btnTema.innerHTML = esClaro ? ICONOS_FRAMEFY.moon : ICONOS_FRAMEFY.sun;
        });
    }

    /* ============================================================
       MODAL
       ============================================================ */
    document.querySelectorAll('.modal-fondo').forEach(fondo => {
        fondo.addEventListener('click', (e) => {
            if (e.target === fondo) cerrarModal(fondo.id);
        });
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-fondo.visible').forEach(m => cerrarModal(m.id));
        }
    });

    /* ============================================================
       CONFIRMAR ELIMINACIÓN (admin)
       ============================================================ */
    document.querySelectorAll('.btn-eliminar').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const nombre = btn.dataset.nombre || 'este elemento';
            if (!confirm(`¿Eliminar "${nombre}"? Esta acción no se puede deshacer.`)) {
                e.preventDefault();
            }
        });
    });

}); // fin DOMContentLoaded

/* ============================================================
   FUNCIONES GLOBALES DE MODAL
   ============================================================ */
function abrirModal(idModal) {
    document.getElementById(idModal)?.classList.add('visible');
    document.body.style.overflow = 'hidden';
}
function cerrarModal(idModal) {
    document.getElementById(idModal)?.classList.remove('visible');
    document.body.style.overflow = '';
}
