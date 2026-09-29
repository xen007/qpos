{{--
    Notifications flash (skin Tailwind) : les messages de session et les erreurs
    de validation sont transmis a Sonner via un ilot React.
    Le JS (React + Sonner) n'est charge que s'il y a quelque chose a afficher.
--}}
@php
    $qposFlash = [];

    foreach (['success' => 'success', 'error' => 'error', 'warning' => 'warning'] as $key => $type) {
        if (session()->has($key)) {
            $qposFlash[] = ['type' => $type, 'message' => session($key)];
        }
    }

    if (isset($errors) && $errors->any()) {
        foreach ($errors->all() as $error) {
            $qposFlash[] = ['type' => 'error', 'message' => $error];
        }
    }
@endphp

@if ($qposFlash !== [])
    <script type="application/json" id="qpos-flash-messages">{!! json_encode($qposFlash, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    <div id="qpos-flash-root"></div>
    @vite('resources/js/backend-toast.jsx')
@endif
