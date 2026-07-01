<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminación de datos — CREDIMAS</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #1f2937; background: #f9fafb; }
        .container { max-width: 680px; margin: 0 auto; padding: 48px 24px; }
        header { text-align: center; margin-bottom: 48px; }
        header .logo { display: inline-flex; align-items: center; gap: 12px; margin-bottom: 24px; }
        header .logo-icon { width: 44px; height: 44px; background: #1e3a5f; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #facc15; font-weight: 700; font-size: 20px; }
        header .logo-name { font-size: 22px; font-weight: 700; color: #1e3a5f; }
        h1 { font-size: 26px; font-weight: 700; color: #1e3a5f; margin-bottom: 8px; }
        .subtitle { color: #6b7280; font-size: 14px; }
        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 32px; margin-bottom: 20px; }
        h2 { font-size: 16px; font-weight: 600; color: #1e3a5f; margin-bottom: 12px; }
        p { font-size: 14px; line-height: 1.75; color: #4b5563; margin-bottom: 10px; }
        p:last-child { margin-bottom: 0; }
        ul { padding-left: 20px; margin-bottom: 10px; }
        li { font-size: 14px; line-height: 1.75; color: #4b5563; margin-bottom: 4px; }
        .steps { counter-reset: step; }
        .step { display: flex; gap: 16px; margin-bottom: 16px; }
        .step-num { width: 32px; height: 32px; background: #1e3a5f; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; flex-shrink: 0; margin-top: 2px; }
        .step-content p { margin: 0; }
        .step-content strong { display: block; font-size: 14px; color: #1f2937; margin-bottom: 2px; }
        .info-box { background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 14px 16px; font-size: 13px; color: #92400e; margin-top: 12px; }
        .confirm-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 14px 16px; font-size: 13px; color: #14532d; }
        footer { text-align: center; margin-top: 40px; color: #9ca3af; font-size: 13px; }
    </style>
</head>
<body>
<div class="container">

    <header>
        <div class="logo">
            <div class="logo-icon">C</div>
            <span class="logo-name">CREDIMAS</span>
        </div>
        <h1>Solicitud de eliminación de datos</h1>
        <p class="subtitle">Ejerce tu derecho a la supresión de datos personales</p>
    </header>

    <div class="card">
        <h2>¿Qué datos podemos eliminar?</h2>
        <p>Si en algún momento interactuaste con el servicio de WhatsApp de CREDIMAS, podemos eliminar:</p>
        <ul>
            <li>Tu número de teléfono de nuestros registros</li>
            <li>El historial de conversaciones con el chatbot y asesores</li>
            <li>Los datos de solicitudes de atención generadas</li>
        </ul>
        <div class="info-box">
            ⚠️ La eliminación es permanente e irreversible. Una vez procesada tu solicitud, no podremos recuperar el historial de atenciones anteriores.
        </div>
    </div>

    <div class="card">
        <h2>Cómo solicitar la eliminación</h2>
        <div class="steps">
            <div class="step">
                <div class="step-num">1</div>
                <div class="step-content">
                    <strong>Envía un mensaje por WhatsApp</strong>
                    <p>Escríbenos directamente al número de WhatsApp de CREDIMAS con el asunto <em>"Solicitud de eliminación de datos"</em>.</p>
                </div>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <div class="step-content">
                    <strong>Incluye tu número de teléfono</strong>
                    <p>Indica el número con el que interactuaste con nuestro servicio para que podamos localizarlo en nuestro sistema.</p>
                </div>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <div class="step-content">
                    <strong>Procesamos tu solicitud</strong>
                    <p>Recibirás una confirmación en un plazo máximo de <strong>15 días hábiles</strong>, conforme a la Ley N° 29733.</p>
                </div>
            </div>
        </div>
        <div class="confirm-box">
            ✅ Una vez eliminados los datos, te notificaremos mediante un mensaje de confirmación al número que indicaste.
        </div>
    </div>

    <div class="card">
        <h2>Base legal</h2>
        <p>
            Este derecho está reconocido en el artículo 20° de la <strong>Ley N° 29733 — Ley de Protección de Datos Personales del Perú</strong>
            y su reglamento aprobado por D.S. N° 003-2013-JUS.
        </p>
        <p>
            También aplica a usuarios que hayan interactuado con nuestra app a través de la plataforma de Meta (WhatsApp/Facebook),
            conforme a los requisitos de la <a href="https://www.facebook.com/policy.php" target="_blank" style="color:#1e3a5f">Política de datos de Meta</a>.
        </p>
    </div>

    <footer>
        <p>© {{ now()->year }} CREDIMAS. Todos los derechos reservados.</p>
        <p style="margin-top:6px;">
            Para más información consulta nuestra
            <a href="{{ route('privacidad') }}" style="color:#1e3a5f">Política de Privacidad</a>
            y los
            <a href="{{ route('terminos') }}" style="color:#1e3a5f">Términos del Servicio</a>.
        </p>
    </footer>

</div>
</body>
</html>
