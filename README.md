# Laravel Private Chat Study

Proyecto de estudio para practicar chat privado en tiempo real con Laravel, Inertia, Vue y Reverb.

No es una aplicacion de produccion. El objetivo principal es experimentar con:

- conversaciones privadas 1:1
- base de datos escalable para conversaciones futuras de grupo
- broadcast en tiempo real con Reverb
- cola de jobs con worker
- estado de lectura por participante
- UI de chat con sidebar, panel principal y composer

## Stack

- Laravel 13
- PHP 8.5
- Inertia.js v3
- Vue 3
- Tailwind CSS v4
- Laravel Reverb
- Laravel Sail
- Wayfinder
- Pest

## Que hace este proyecto

- autentica usuarios con Fortify
- redirige al usuario autenticado a `Chat`
- muestra un sidebar con conversaciones
- permite crear conversaciones privadas entre usuarios
- permite enviar mensajes de texto
- actualiza estado de lectura en tiempo real
- ordena conversaciones por ultima actividad
- reproduce un sonido cuando llega un mensaje nuevo

## Estructura general

- `app/Http/Controllers/ChatController.php`
  - carga inbox
  - muestra conversaciones
  - crea conversaciones
  - guarda mensajes
  - marca conversaciones como leidas
- `app/Events/Chat`
  - eventos de dominio para mensajes y lectura
- `app/Models`
  - `Conversation`
  - `ConversationParticipant`
  - `Message`
- `resources/js/pages/chat`
  - pantallas de chat
- `resources/js/components/chat`
  - listener Reverb/Echo y piezas de UI
- `compose.yaml`
  - servicios de Sail
  - `laravel.test`
  - `queue`
  - `reverb`
  - `mailpit`
  - `memcached`

## Requisitos

- Docker
- Laravel Sail
- Node.js y npm si vas a correr frontend fuera de Sail

## Arranque con Sail

### 1. Instalar dependencias

Si el proyecto esta recien clonado:

```bash
./vendor/bin/sail composer install
./vendor/bin/sail npm install
```

### 2. Levantar contenedores

```bash
./vendor/bin/sail up -d --build
```

Con la receta actual de `compose.yaml`, no necesitas levantar `reverb` ni `queue` a mano en terminales separadas.

Por que:

- `reverb` corre como servicio propio y expone `8080`
- `queue` corre como servicio propio con `php artisan queue:work`
- ambos arrancan automaticamente cuando sube el stack
- esto evita depender de procesos manuales en varias terminales

### 3. Migrar base de datos

```bash
./vendor/bin/sail artisan migrate
```

### 4. Compilar assets

Para produccion local o para dejar el build listo:

```bash
./vendor/bin/sail npm run build
```

Para desarrollo con recarga en vivo:

```bash
./vendor/bin/sail npm run dev
```

## Comandos utiles

```bash
./vendor/bin/sail artisan test
./vendor/bin/sail artisan queue:failed
./vendor/bin/sail artisan reverb:restart
./vendor/bin/sail logs -f reverb
./vendor/bin/sail logs -f queue
./vendor/bin/sail pail
```

## Notas sobre Reverb

El cliente frontend se conecta a `ws://localhost:8080` durante desarrollo.
El backend de Laravel, dentro de los contenedores, usa el servicio `reverb` por red Docker.

Eso significa:

- navegador -> `localhost:8080`
- contenedores Laravel -> `reverb:8080`

Esa separacion evita el bug de intentar hablar con `localhost` dentro del contenedor.

## Notas sobre colas

Los eventos de chat se procesan por cola en base de datos.

Eso permite:

- persistir jobs de forma simple
- procesar broadcast con worker
- mantener el flujo desacoplado

## Flujo del chat

1. el usuario abre `Chat`
2. el sidebar lista conversaciones
3. al entrar a una conversacion se carga el hilo completo
4. al enviar un mensaje se guarda en DB
5. el evento se encola
6. el worker procesa el broadcast
7. Reverb entrega el payload a los clientes suscritos
8. la UI actualiza el hilo, el sidebar y el estado de lectura

## Atajos de desarrollo

- `Enter` envia mensaje
- `Shift + Enter` inserta nueva linea
- hover en una conversacion puede prefetch la vista, pero no marca leido hasta la entrada real

## Estado actual

Este repo esta orientado a estudio y prueba de ideas. Si luego quieres llevarlo a produccion, habria que revisar:

- seguridad de canales y autorizaciones
- escalado de Reverb
- presencia y estado online
- adjuntos
- moderacion
- notificaciones push
