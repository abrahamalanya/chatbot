<!DOCTYPE html>
<html>
<head>
    <title>Chat Asesor</title>
</head>
<body>
    <h2>Clientes</h2>

    <ul>
        @foreach($clientes as $cliente)
            <li>
                <a href="/chat?asesor={{ $asesorTelefono }}&cliente={{ $cliente->cliente_telefono }}">
                    {{ $cliente->cliente_telefono }}
                </a>
            </li>
        @endforeach
    </ul>

    <hr>

    @if($clienteSeleccionado)

        <h3>Chat con {{ $clienteSeleccionado }}</h3>

        <div id="chat-box" style="border:1px solid #ccc; padding:10px; height:300px; overflow-y:scroll;">
        </div>

        <form method="POST" action="/chat/send">
            @csrf
            <input type="hidden" name="cliente_telefono" value="{{ $clienteSeleccionado }}">

            <input type="text" name="mensaje" placeholder="Escribe mensaje">
            <button type="submit">Enviar</button>
        </form>

    @endif

    <script>
        let cliente = "{{ $clienteSeleccionado ?? '' }}";

        function cargarMensajes() {
            if (!cliente) return;

            fetch(`/chat/messages?cliente_telefono=${cliente}`)
                .then(res => res.json())
                .then(data => {
                    let chatBox = document.getElementById('chat-box');
                    chatBox.innerHTML = '';

                    data.forEach(msg => {
                        chatBox.innerHTML += `
                            <p>
                                <strong>${msg.sender}:</strong>
                                ${msg.mensaje}
                            </p>
                        `;
                    });

                    // auto scroll abajo
                    chatBox.scrollTop = chatBox.scrollHeight;
                });
        }

        // refresca cada 2 segundos
        setInterval(cargarMensajes, 2000);

        // carga inicial
        cargarMensajes();
    </script>
</body>
</html>