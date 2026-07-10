<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud de Crédito — CREDIMAS ORIENTE</title>
    <style>
        :root {
            --content-width: 60%;
            --content-max: 640px;
            --navy: #1e3a5f;
            --yellow: #facc15;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { min-height: 100%; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: #1f2937;
            background: #f9fafb;
            display: grid;
            grid-template-columns: 1fr min(var(--content-width), var(--content-max)) 1fr;
        }
        @media (max-width: 640px) {
            body { grid-template-columns: 16px 1fr 16px; }
        }

        .content { grid-column: 2; padding: 48px 0 32px; }

        .page-header { text-align: center; margin-bottom: 28px; }
        .logo { display: inline-flex; align-items: center; gap: 12px; margin-bottom: 20px; }
        .logo-icon { width: 44px; height: 44px; background: var(--navy); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--yellow); font-weight: 700; font-size: 20px; }
        .logo-name { font-size: 20px; font-weight: 700; color: var(--navy); letter-spacing: .2px; }
        h1 { font-size: 26px; font-weight: 700; color: var(--navy); margin-bottom: 10px; }
        .consent { font-size: 13px; line-height: 1.6; color: #6b7280; max-width: 480px; margin: 0 auto; }
        .consent a { color: var(--navy); font-weight: 600; }

        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 28px; }

        .field { margin-bottom: 18px; }
        .field:last-of-type { margin-bottom: 0; }
        label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
        .opt { font-weight: 400; color: #9ca3af; }
        input[type="text"], input[type="email"], input[type="tel"], input[type="number"] {
            width: 100%; padding: 11px 14px; border: 1px solid #d1d5db; border-radius: 9px;
            font-size: 14px; font-family: inherit; color: #1f2937;
        }
        input:focus { outline: none; border-color: var(--navy); box-shadow: 0 0 0 3px rgba(30, 58, 95, .12); }
        input:invalid:not(:placeholder-shown) { border-color: #f87171; }

        .credito-options { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
        .credito-option {
            border: 1px solid #d1d5db; border-radius: 10px; padding: 12px 6px; text-align: center;
            cursor: pointer; font-size: 12.5px; font-weight: 600; color: #374151; line-height: 1.3;
            transition: border-color .15s, background .15s, color .15s;
        }
        .credito-option input { position: absolute; opacity: 0; pointer-events: none; }
        .credito-option .emoji { display: block; font-size: 18px; margin-bottom: 4px; }
        .credito-option.selected { border-color: var(--navy); background: #eff6ff; color: var(--navy); }

        .requisitos-box {
            margin-top: 12px; background: #f9fafb; border-left: 3px solid var(--yellow);
            border-radius: 0 8px 8px 0; padding: 12px 16px; font-size: 12.5px; color: #4b5563; display: none;
        }
        .requisitos-box.visible { display: block; }
        .requisitos-box strong { color: #374151; }
        .requisitos-box ul { padding-left: 18px; margin-top: 6px; }
        .requisitos-box li { margin-bottom: 3px; }

        .submit-btn {
            width: 100%; padding: 13px; background: var(--navy); color: #fff; font-weight: 600;
            font-size: 14.5px; border: none; border-radius: 10px; cursor: pointer; margin-top: 22px;
            font-family: inherit; transition: background .15s;
        }
        .submit-btn:hover { background: #16314f; }

        .success-panel { display: none; text-align: center; padding: 44px 24px; }
        .success-panel.visible { display: block; }
        .success-icon {
            width: 52px; height: 52px; border-radius: 50%; background: #dcfce7; color: #16a34a;
            font-size: 26px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;
        }
        .success-panel h2 { font-size: 18px; color: var(--navy); margin-bottom: 8px; }
        .success-panel p { font-size: 13.5px; color: #6b7280; line-height: 1.6; }

        .page-footer { text-align: center; margin-top: 32px; color: #9ca3af; font-size: 12px; }
        .page-footer .footer-links { margin-bottom: 8px; }
        .page-footer a { color: #6b7280; text-decoration: none; }
        .page-footer a:hover { text-decoration: underline; }
        .page-footer .sep { margin: 0 8px; color: #d1d5db; }

        .floating-chat-btn {
            position: fixed; bottom: 22px; right: 22px; width: 56px; height: 56px; border-radius: 50%;
            background: #25D366; display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 14px rgba(0,0,0,.22); text-decoration: none; cursor: pointer;
        }
        .floating-chat-btn svg { width: 28px; height: 28px; }
    </style>
</head>
<body>

<div class="content">

    <header class="page-header">
        <div class="logo">
            <div class="logo-icon">C</div>
            <span class="logo-name">CREDIMAS ORIENTE</span>
        </div>
        <h1>Solicitud de Crédito</h1>
        <p class="consent">
            Al completar este formulario aceptas que CREDIMAS ORIENTE trate tus datos personales
            para evaluar tu solicitud de crédito, conforme a nuestra
            <a href="{{ route('privacidad') }}" target="_blank">Política de Privacidad</a>.
        </p>
    </header>

    <main>
        <form id="solicitud-form" novalidate>
            <div id="form-card-body" class="card">

                <div class="field">
                    <label for="dni">DNI del cliente</label>
                    <input type="text" id="dni" name="dni" inputmode="numeric" pattern="[0-9]{8}" maxlength="8" placeholder="12345678" required>
                </div>

                <div class="field">
                    <label for="email">Correo electrónico <span class="opt">(opcional)</span></label>
                    <input type="email" id="email" name="email" placeholder="correo@ejemplo.com">
                </div>

                <div class="field">
                    <label for="celular">Número de celular</label>
                    <input type="tel" id="celular" name="celular" inputmode="numeric" pattern="9[0-9]{8}" maxlength="9" placeholder="9XXXXXXXX" required>
                </div>

                <div class="field">
                    <label for="celular2">Otro celular <span class="opt">(opcional)</span></label>
                    <input type="tel" id="celular2" name="celular2" inputmode="numeric" pattern="9[0-9]{8}" maxlength="9" placeholder="9XXXXXXXX">
                </div>

                <div class="field">
                    <label>Tipo de crédito que solicita</label>
                    <div class="credito-options">
                        <label class="credito-option" data-option>
                            <input type="radio" name="tipo_credito" value="hipotecario" required>
                            <span class="emoji">🏠</span>Hipotecario
                        </label>
                        <label class="credito-option" data-option>
                            <input type="radio" name="tipo_credito" value="vehicular" required>
                            <span class="emoji">🚗</span>Vehicular
                        </label>
                        <label class="credito-option" data-option>
                            <input type="radio" name="tipo_credito" value="diario" required>
                            <span class="emoji">💰</span>Diario
                        </label>
                    </div>
                    <div class="requisitos-box" id="requisitos-box">
                        <strong>Requisitos:</strong>
                        <ul id="requisitos-list"></ul>
                    </div>
                </div>

                <div class="field">
                    <label for="monto">Monto que solicita (S/)</label>
                    <input type="number" id="monto" name="monto" min="0" step="50" placeholder="Ej. 500" required>
                </div>

                <button type="submit" class="submit-btn">Enviar solicitud</button>
            </div>

            <div class="card success-panel" id="success-panel">
                <div class="success-icon">✓</div>
                <h2>¡Solicitud enviada!</h2>
                <p>Hemos recibido tu solicitud. Un asesor de CREDIMAS se pondrá en contacto contigo pronto. 🙏</p>
            </div>
        </form>
    </main>

    <footer class="page-footer">
        <div class="footer-links">
            <a href="https://www.facebook.com/credimas.peru/" target="_blank" rel="noopener">Facebook</a>
            <span class="sep">·</span>
            <a href="{{ route('terminos') }}" target="_blank">Términos y condiciones</a>
            <span class="sep">·</span>
            <a href="{{ route('privacidad') }}" target="_blank">Privacidad</a>
        </div>
        <p>© {{ now()->year }} CREDIMAS ORIENTE. Todos los derechos reservados.</p>
    </footer>

</div>

{{-- Botón flotante de chat — diseño únicamente, funcionalidad pendiente --}}
<a href="#" class="floating-chat-btn" title="Escríbenos por WhatsApp (próximamente)" onclick="return false;" aria-disabled="true">
    <svg viewBox="0 0 24 24" fill="#fff">
        <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38a9.9 9.9 0 0 0 4.74 1.21h.01c5.46 0 9.9-4.45 9.9-9.91C21.96 6.45 17.5 2 12.04 2Zm5.8 14.02c-.24.68-1.39 1.32-1.92 1.4-.49.08-1.11.11-1.79-.11-.41-.13-.94-.3-1.62-.6-2.85-1.23-4.71-4.1-4.85-4.29-.14-.19-1.16-1.54-1.16-2.94s.73-2.09.99-2.37c.26-.28.56-.35.75-.35h.54c.17 0 .4-.03.63.48.24.55.8 1.9.87 2.04.07.14.12.3.02.49-.09.19-.14.3-.28.46-.14.16-.29.36-.42.48-.14.14-.28.28-.12.55.16.28.72 1.19 1.55 1.93 1.06.95 1.96 1.24 2.24 1.38.28.14.44.12.6-.07.16-.19.68-.79.86-1.06.18-.28.36-.23.6-.14.24.09 1.55.73 1.82.86.27.14.44.2.51.31.07.12.07.65-.17 1.33Z"/>
    </svg>
</a>

@php
    $requisitosCredito = [
        'hipotecario' => config('messages.creditos.hipotecario.requisitos'),
        'vehicular'   => config('messages.creditos.vehicular.requisitos'),
        'diario'      => config('messages.creditos.diario.requisitos'),
    ];
@endphp
<script>
    const requisitos = @json($requisitosCredito);

    document.querySelectorAll('input[name="tipo_credito"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            document.querySelectorAll('[data-option]').forEach(function (el) {
                el.classList.remove('selected');
            });
            radio.closest('[data-option]').classList.add('selected');
            mostrarRequisitos(radio.value);
        });
    });

    function mostrarRequisitos(tipo) {
        const box  = document.getElementById('requisitos-box');
        const list = document.getElementById('requisitos-list');
        const texto = requisitos[tipo] || '';

        const items = texto.split('\n')
            .map(function (l) { return l.trim(); })
            .filter(function (l) { return l.startsWith('*'); })
            .map(function (l) { return l.replace(/^\*+\s*/, ''); });

        list.innerHTML = items.map(function (i) {
            const div = document.createElement('div');
            div.textContent = i;
            return '<li>' + div.innerHTML + '</li>';
        }).join('');

        box.classList.add('visible');
    }

    document.getElementById('solicitud-form').addEventListener('submit', function (e) {
        e.preventDefault();
        if (!this.reportValidity()) return;

        document.getElementById('form-card-body').style.display = 'none';
        document.getElementById('success-panel').classList.add('visible');
    });
</script>

</body>
</html>
