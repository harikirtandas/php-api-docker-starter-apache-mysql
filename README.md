# php-api-docker-starter-apache-mysql

Plantilla de GitHub para arrancar una **API REST en PHP plano** (sin framework,
sin librerias externas) dockerizada, con **Apache + mod_php + MySQL 8**, en
cualquier maquina, con un solo comando.

Es el hermano API de
[`php-mvc-docker-starter-apache-mysql`](../php-mvc-docker-starter-apache-mysql):
mismo andamiaje Docker sin tocar, mismo router propio con parametros dinamicos,
pero orientado a devolver **JSON** en vez de renderizar vistas HTML. Suma sobre
el hermano MVC: verbos **PUT / PATCH / DELETE**, **method override**, helpers de
respuesta JSON, **CORS** y **autenticacion por token**.

## Requisitos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (o Docker Engine + Compose plugin) corriendo.
- [GitHub CLI](https://cli.github.com/) (`gh`) para crear proyectos nuevos desde la terminal. Alternativa: boton **"Use this template"** en GitHub.

## Crear un proyecto nuevo desde este template

```bash
gh repo create mi-api --template TU_USUARIO/php-api-docker-starter-apache-mysql --private --clone
cd mi-api
make install
```

Al terminar:

- API + cliente demo -> **http://localhost:8080**
- Adminer (cliente web de MySQL) -> **http://localhost:8081**

`make install` es **obligatorio** antes del primer arranque: sin `vendor/` no
existe el autoloader de Composer y el proyecto no corre con solo
`docker compose up`.

## Arquitectura

Un solo contenedor de aplicacion (`php:8.4-apache`, mod_php) mas MySQL y Adminer:

| Servicio | Imagen | Rol |
|---|---|---|
| `app` | build propio, `php:8.4-apache` | Apache y PHP en el mismo proceso. Publica `APP_PORT` (default 8080). |
| `mysql` | `mysql:8` | Base de datos, volumen persistente `mysql-data` + healthcheck. |
| `adminer` | `adminer` | Cliente web de MySQL, publica `ADMINER_PORT` (default 8081). |

`./src` se monta como bind mount en `app`: `src/public` es el docroot (unico
directorio que Apache expone por URL), el resto queda fuera.

## Estructura de `src/`

```
src/
├── app/
│   ├── Core/
│   │   ├── Router.php         # router propio: verbos, parametros {id:\d+}, method override, 404/405 JSON
│   │   ├── Controller.php     # base: json(), creado(), sinContenido()
│   │   ├── Request.php        # body JSON, query params, Bearer token
│   │   ├── ApiException.php   # excepcion HTTP con status + errores de validacion
│   │   ├── Auth.php           # login -> token opaco en tabla `tokens`; usuarioActual()
│   │   └── Database.php       # PDO singleton (getenv, ERRMODE_EXCEPTION, FETCH_ASSOC)
│   ├── Controllers/           # PingController, AuthController, NotaController  (slice demo)
│   └── Models/                # Usuario, Token, Nota  (slice demo)
├── config/app.php             # debug, cors_origin, token_ttl_horas
└── public/
    ├── .htaccess              # front controller + fix del header Authorization
    ├── index.php              # UNICO lugar donde se arma la tabla de rutas
    ├── index.html             # cliente demo en JS vanilla
    ├── css/app.css
    └── js/{api.js, app.js}
```

## El slice demo (descartable)

Un vertical slice completo para probar de punta a punta que Apache, PHP, el
router, el autoloader, MySQL y la auth funcionan juntos. **En un proyecto real
se borra entero** (`Ping`, `Auth`, `Nota` en Controllers/Models, la tabla de
`notas`/`usuarios`/`tokens` del schema, y el `index.html`/`js` del cliente) y se
reemplaza por el dominio propio. Lo que se conserva es todo `app/Core/`.

| Metodo | Ruta | Auth | Que hace |
|---|---|---|---|
| `GET` | `/api/ping` | no | `{ "pong": true, "hora": ... }` |
| `GET` | `/api/health/db` | no | `SELECT 1` contra MySQL |
| `POST` | `/api/auth/login` | no | `{ email, password }` -> `{ token, expira, usuario }` |
| `GET` | `/api/auth/me` | si | usuario dueno del token |
| `POST` | `/api/auth/logout` | si | invalida el token |
| `GET` | `/api/notas` | si | lista las notas del usuario |
| `POST` | `/api/notas` | si | `{ titulo, cuerpo }` -> 201 + `Location` |
| `GET` | `/api/notas/{id}` | si | una nota |
| `PUT` | `/api/notas/{id}` | si | reemplaza -> 200 |
| `DELETE` | `/api/notas/{id}` | si | -> 204 |

Usuario demo: **`demo@demo.test` / `secret`**.

### Probar con curl

```bash
# login
TOKEN=$(curl -s -X POST localhost:8080/api/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"demo@demo.test","password":"secret"}' | php -r 'echo json_decode(file_get_contents("php://stdin"))->token;')

# listar notas
curl -s localhost:8080/api/notas -H "Authorization: Bearer $TOKEN"

# crear
curl -s -X POST localhost:8080/api/notas \
  -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{"titulo":"Hola","cuerpo":"desde curl"}' -i

# borrar
curl -s -X DELETE localhost:8080/api/notas/3 -H "Authorization: Bearer $TOKEN" -i
```

## Rutas

El router (`App\Core\Router`) se registra en `src/public/index.php` con
`$router->get(...)`, `->post(...)`, `->put(...)`, `->patch(...)`, `->delete(...)`
y se despacha con `$router->dispatch()`.

### Sintaxis de parametros

| Path registrado | Que matchea | Que recibe el metodo |
|---|---|---|
| `/api/notas` | Solo ese literal | Nada |
| `/api/notas/{id}` | Cualquier segmento no vacio sin `/` | `string $id` |
| `/api/notas/{id:\d+}` | Solo digitos | `int $id` (casteado) |
| `/api/cat/{slug}/notas/{id:\d+}` | Varios parametros | `string $slug, int $id`, en orden |

**El orden de registro importa**: la primera ruta cuyo path y metodo coinciden
gana. Las rutas literales van **antes** que las parametricas con el mismo
prefijo (`/api/notas/export` antes que `/api/notas/{id}`).

### Verbos y method override

Ademas de GET/POST hay `PUT`, `PATCH` y `DELETE`. Un cliente o proxy que solo
sepa mandar POST puede simular los otros con:

- header `X-HTTP-Method-Override: PUT`, o
- query string `?_method=PUT`

sobre un POST. Solo se acepta el override sobre POST.

### 404 vs 405

- Ninguna ruta matchea el path -> **404** JSON.
- El path matchea pero con otro metodo -> **405** JSON + header `Allow`.

## Respuestas y errores

Los controladores extienden `App\Core\Controller`:

```php
$this->json($datos);                       // 200
$this->json($datos, 200);
$this->creado($datos, "/api/notas/12");    // 201 + Location
$this->sinContenido();                     // 204
```

Para cortar con un error, tiran `App\Core\ApiException` (la captura el front
controller y la vuelve JSON):

```php
throw new ApiException(404, 'Nota no encontrada.');
throw ApiException::validacion(['email' => 'Formato invalido']);  // 422
```

Forma de la respuesta de error:

```json
{ "error": { "status": 422, "mensaje": "Datos invalidos.", "errores": { "email": "Formato invalido" } } }
```

Con `APP_DEBUG=1`, un 500 no controlado incluye `error.debug` con la excepcion,
el mensaje y el archivo:linea. En `0`, solo un mensaje generico.

## Autenticacion

`App\Core\Auth`:

- `Auth::login($email, $password)` valida contra `usuarios.password_hash`
  (`password_verify`), inserta un token aleatorio de 64 hex en `tokens` con
  vencimiento (`token_ttl_horas`, default 7 dias) y lo devuelve.
- En cada request protegida: `Auth::usuarioActual($this->request)` resuelve el
  header `Authorization: Bearer <token>` o corta con 401.
- `Auth::logout($request)` borra la fila del token.

Es token opaco en tabla, no JWT: mas facil de explicar y de revocar, y sirve
igual para un cliente JS y para una app Android que consuma la misma API.

> El header `Authorization` lo suele descartar Apache antes de que PHP lo vea.
> El `.htaccess` de este starter lo reinyecta como `HTTP_AUTHORIZATION` via
> `mod_rewrite` — si moves el proyecto a un hosting sin `.htaccess`, hay que
> resolver eso aparte.

## CORS

`src/public/index.php` manda los headers CORS en toda respuesta y contesta el
preflight `OPTIONS` con 204. El origen permitido sale de `CORS_ORIGIN` (default
`*` para desarrollo; en produccion, el dominio del frontend).

## Configuracion (`.env` en la raiz, no en `src/`)

```bash
APP_PORT=8080
ADMINER_PORT=8081
DB_DATABASE=app
DB_USERNAME=app
DB_PASSWORD=secret
APP_DEBUG=1
CORS_ORIGIN=*
TOKEN_TTL_HORAS=168
```

`App\Core\Database::connection()` lee `DB_*` con `getenv()` dentro del
contenedor (docker-compose las inyecta como `environment:`), no hace falta
editar codigo.

## Comandos (Makefile)

| Comando | Que hace |
|---|---|
| `make install` | Instala Composer si hace falta y levanta los tres contenedores. Una sola vez por proyecto. |
| `make up` / `make down` | Levanta / apaga. `down` **no borra datos**. |
| `make restart` | Reinicia sin rebuildear. |
| `make shell` | `bash` dentro del contenedor `app`. |
| `make db-shell` | Cliente `mysql` conectado a la base del proyecto. |
| `make logs` | Sigue los logs. |
| `make db-import FILE=x.sql` | Aplica un `.sql` a la base ya corriendo, sin recrear el volumen. |
| `make fresh` | Borra `mysql-data` y reaplica `docker/mysql/init/*.sql`. Pide confirmacion. |
| `make composer CMD="require x/y"` | Composer dentro del contenedor. |

## Agregar tablas sin perder datos

`docker/mysql/init/*.sql` solo corre en el primer arranque del volumen. Para
sumar una tabla a un proyecto con datos ya cargados:

1. Guardar el archivo numerado: `docker/mysql/init/02-nombre.sql` (con
   `CREATE TABLE IF NOT EXISTS` / `ALTER TABLE`).
2. `make db-import FILE=docker/mysql/init/02-nombre.sql`

## Arrancar un proyecto real

1. `gh repo create mi-api --template TU_USUARIO/php-api-docker-starter-apache-mysql --private --clone && cd mi-api`
2. Reemplazar `docker/mysql/init/01-schema.sql` por el schema real.
3. Borrar el slice demo: `Ping`/`Auth`/`Nota` en `Controllers/` y `Models/` (Auth
   solo si el proyecto no usa login; si lo usa, adaptar `Usuario`/`Token` a las
   tablas reales), las rutas del slice en `src/public/index.php`, y
   `public/index.html` + `public/js/*` + `public/css/*`.
4. Conservar todo `app/Core/`.
5. `make install`.
