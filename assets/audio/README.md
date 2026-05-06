# Archivos de Audio para el Softphone

**Carpeta:** `assets/audio/` (el softphone usa esta ruta automáticamente).

Coloca aquí los archivos de audio para que el softphone tenga sonido.

## Archivos principales (recomendados)

| Archivo        | Uso |
|----------------|-----|
| **ringtone.mp3** | Tono de llamada entrante (se repite en loop). |
| **ringback.mp3** | Tono de espera mientras suena la llamada saliente (en loop). |

## Archivos opcionales

| Archivo        | Uso |
|----------------|-----|
| **edd call.mp3** | Sonido al colgar (si no existe, no se reproduce). |
| **DTMF_0.mp3** … **DTMF_9.mp3**, **DTMF_star.mp3**, **DTMF_pound.mp3** | Tonos del teclado (0-9, *, #). |

## Formatos

- **MP3** (recomendado).
- OGG o WAV también suelen funcionar.

## Recomendaciones

- **Duración**: 2–4 s para ringtone y ringback (se usan en loop).
- **Calidad**: 128 kbps, 44.1 kHz es suficiente.

## Dónde conseguir tonos

- [freesound.org](https://freesound.org)
- [zapsplat.com](https://www.zapsplat.com)
- [mixkit.co](https://mixkit.co/free-sound-effects/phone/)

## Cómo se usa en el código

El script `assets/js/softphone-web.js` resuelve la ruta de esta carpeta desde la URL del propio script, así que funciona aunque la página esté en subcarpetas o con parámetros. Si hace falta, puedes pasar `audioBaseUrl` en la config del softphone para usar otra ruta.




