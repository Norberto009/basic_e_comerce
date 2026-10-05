# Basic E-Commerce API

API REST para una tienda en línea construida con **Laravel 12** y **PHP 8.2+**. Incluye catálogo de productos, autenticación de clientes con tokens (Sanctum), órdenes de compra y registro manual de pagos.



## Stack

- Laravel 12 / PHP 8.2+
- Base de datos: MySQL 
- Autenticación: Laravel Sanctum (tokens Bearer)

## Instalación

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate 
php artisan serve
```

El seeder crea un usuario de prueba (`test@example.com` / `password`) y 25 productos de ejemplo. La API queda en `http://127.0.0.1:8000/api`.

## Autenticación

`POST /api/auth/register` y `POST /api/auth/login` devuelven un `token`, que se envía en las rutas protegidas:

```
Authorization: Bearer {token}
```

Un usuario con `is_active = false` no puede iniciar sesión 

## Endpoints

| Método | Ruta | Descripción | Auth |
|---|---|---|:---:|
| POST | `/api/auth/register` | Registrar cliente | No |
| POST | `/api/auth/login` | Iniciar sesión | No |
| POST | `/api/auth/logout` | Revocar el token actual | Sí |
| GET | `/api/auth/me` | Usuario autenticado | Sí |
| GET | `/api/products` | Listado público (`search`, `per_page`) | No |
| GET | `/api/products/{product}` | Detalle de producto | No |
| POST | `/api/products` | Crear producto | Sí |
| PUT/PATCH | `/api/products/{product}` | Actualizar producto | Sí |
| DELETE | `/api/products/{product}` | Eliminar (soft delete) | Sí |
| PUT | `/api/products/{product}/restore` | Restaurar producto eliminado | Sí |
| GET | `/api/orders` | Todas las órdenes | Sí |
| POST | `/api/orders` | Crear orden | Sí |
| GET | `/api/orders/user/{user}` | Historial de un usuario | Sí |
| GET | `/api/orders/{order}` | Detalle (solo el dueño) | Sí |
| PUT | `/api/orders/{order}/mark-as-paid` | Marcar orden como pagada manualmente | Sí (no el dueño) |

Al crear una orden se valida el stock, se congela el precio unitario y se descuenta el inventario dentro de una transacción con `lockForUpdate`.

## Estructura

- `app/Http/Controllers/Api`: `AuthController`, `ProductController`, `OrderController`, `PaymentController` (solo pago manual)
- `app/Http/Requests`: Form Requests por operación de escritura
- `app/Http/Resources`: transformación JSON de productos y órdenes
- `app/Models`: `User`, `Product`, `Order`, `OrderItem`, `Payment`
- `bootstrap/app.php`: manejo centralizado de errores en JSON

## Notas

- Sin sistema de roles: cualquier usuario autenticado puede gestionar productos y marcar órdenes ajenas como pagadas.
- Reiniciar la base: `php artisan migrate:fresh --seed`.
