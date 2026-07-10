@if($msg->tipo === 'imagen')
    @if($msg->media_url)
    <a href="{{ $msg->media_url }}" target="_blank" rel="noopener">
        <img src="{{ $msg->media_url }}" alt="Imagen" class="rounded-lg max-w-full max-h-60 object-cover mb-1">
    </a>
    @endif
    @if($msg->mensaje && $msg->mensaje !== '📷 Imagen')
        <p>{{ $msg->mensaje }}</p>
    @endif

@elseif($msg->tipo === 'documento')
    @if($msg->media_url)
    <a href="{{ $msg->media_url }}" target="_blank" rel="noopener"
       class="flex items-center gap-2 {{ $light ? 'text-white' : 'text-gray-800' }} underline decoration-dotted">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <span class="truncate">{{ $msg->media_filename ?? 'Documento' }}</span>
    </a>
    @else
        <p>{{ $msg->mensaje }}</p>
    @endif

@elseif($msg->tipo === 'video')
    @if($msg->media_url)
    <video src="{{ $msg->media_url }}" controls class="rounded-lg max-w-full max-h-60 mb-1"></video>
    @endif
    @if($msg->mensaje && $msg->mensaje !== '🎥 Video')
        <p>{{ $msg->mensaje }}</p>
    @endif

@elseif($msg->tipo === 'audio')
    @if($msg->media_url)
    <audio src="{{ $msg->media_url }}" controls class="max-w-full mb-1" style="max-width: 240px;"></audio>
    @else
        <p>{{ $msg->mensaje }}</p>
    @endif

@elseif($msg->tipo === 'ubicacion')
    <a href="https://www.google.com/maps?q={{ $msg->latitude }},{{ $msg->longitude }}" target="_blank" rel="noopener"
       class="flex items-center gap-2 {{ $light ? 'text-white' : 'text-gray-800' }} underline decoration-dotted">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        <span>{{ $msg->mensaje }}</span>
    </a>

@else
    {{ $msg->mensaje }}
@endif
