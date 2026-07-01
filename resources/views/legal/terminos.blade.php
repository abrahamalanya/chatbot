<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Condiciones del Servicio — CREDIMAS</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #1f2937; background: #f9fafb; }
        .container { max-width: 760px; margin: 0 auto; padding: 48px 24px; }
        header { text-align: center; margin-bottom: 48px; }
        header .logo { display: inline-flex; align-items: center; gap: 12px; margin-bottom: 24px; }
        header .logo-icon { width: 44px; height: 44px; background: #1e3a5f; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #facc15; font-weight: 700; font-size: 20px; }
        header .logo-name { font-size: 22px; font-weight: 700; color: #1e3a5f; }
        h1 { font-size: 28px; font-weight: 700; color: #1e3a5f; margin-bottom: 8px; }
        .subtitle { color: #6b7280; font-size: 14px; }
        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 32px; margin-bottom: 20px; }
        h2 { font-size: 16px; font-weight: 600; color: #1e3a5f; margin-bottom: 12px; display: flex; align-items: center; }
        h2::before { content: ''; display: inline-block; width: 4px; height: 16px; background: #1e3a5f; border-radius: 2px; margin-right: 10px; }
        p { font-size: 14px; line-height: 1.75; color: #4b5563; margin-bottom: 10px; }
        p:last-child { margin-bottom: 0; }
        ul { padding-left: 20px; margin-bottom: 10px; }
        li { font-size: 14px; line-height: 1.75; color: #4b5563; margin-bottom: 4px; }
        footer { text-align: center; margin-top: 40px; color: #9ca3af; font-size: 13px; }
        a { color: #1e3a5f; }
    </style>
</head>
<body>
<div class="container">

    <header>
        <div class="logo">
            <div class="logo-icon">C</div>
            <span class="logo-name">CREDIMAS</span>
        </div>
        <h1>Condiciones del Servicio</h1>
        <p class="subtitle">Última actualización: {{ now()->format('d \d\e F \d\e Y') }}</p>
    </header>

    <div class="card">
        <h2>1. Aceptación de los términos</h2>
        <p>
            Al iniciar una conversación con el servicio de WhatsApp de CREDIMAS, aceptas las presentes
            Condiciones del Servicio. Si no estás de acuerdo, te pedimos que no utilices nuestro servicio.
        </p>
    </div>

    <div class="card">
        <h2>2. Descripción del servicio</h2>
        <p>
            CREDIMAS ofrece un servicio de atención al cliente a través de WhatsApp, que permite a los
            usuarios comunicarse con asesores financieros para resolver consultas sobre productos y
            servicios de crédito. El servicio opera mediante un sistema automatizado (chatbot) que
            deriva las conversaciones a asesores humanos.
        </p>
    </div>

    <div class="card">
        <h2>3. Uso aceptable</h2>
        <p>Al usar nuestro servicio te comprometes a:</p>
        <ul>
            <li>Proporcionar información veraz y actualizada</li>
            <li>No usar el servicio para fines fraudulentos o ilegales</li>
            <li>No enviar mensajes masivos, spam o contenido ofensivo</li>
            <li>No intentar acceder a sistemas o información que no te corresponde</li>
        </ul>
    </div>

    <div class="card">
        <h2>4. Disponibilidad del servicio</h2>
        <p>
            CREDIMAS se esfuerza por mantener el servicio disponible de forma continua, pero no garantiza
            disponibilidad ininterrumpida. El servicio puede suspenderse temporalmente por mantenimiento,
            actualizaciones o causas fuera de nuestro control.
        </p>
        <p>
            La atención por parte de asesores humanos está disponible en horario de oficina. Fuera de ese
            horario, el chatbot puede recibir tu solicitud y asignarte un asesor cuando esté disponible.
        </p>
    </div>

    <div class="card">
        <h2>5. Limitación de responsabilidad</h2>
        <p>
            CREDIMAS no se responsabiliza por decisiones financieras tomadas en base a la información
            proporcionada en las conversaciones. La información compartida por nuestros asesores es
            orientativa y no constituye asesoría financiera formal ni vinculante.
        </p>
    </div>

    <div class="card">
        <h2>6. Privacidad</h2>
        <p>
            El tratamiento de tus datos personales se rige por nuestra
            <a href="{{ route('privacidad') }}">Política de Privacidad</a>,
            la cual forma parte integral de estas condiciones.
        </p>
    </div>

    <div class="card">
        <h2>7. Modificaciones</h2>
        <p>
            CREDIMAS se reserva el derecho de modificar estas condiciones en cualquier momento.
            Las modificaciones entrarán en vigor al momento de su publicación en esta página.
            El uso continuado del servicio después de dichos cambios implica la aceptación de
            las nuevas condiciones.
        </p>
    </div>

    <div class="card">
        <h2>8. Ley aplicable</h2>
        <p>
            Estas condiciones se rigen por la legislación de la República del Perú.
            Cualquier controversia será sometida a los tribunales competentes de Lima, Perú.
        </p>
    </div>

    <footer>
        <p>© {{ now()->year }} CREDIMAS. Todos los derechos reservados.</p>
    </footer>

</div>
</body>
</html>
