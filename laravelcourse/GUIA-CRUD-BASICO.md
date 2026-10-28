# Guía básica: CRUD en Laravel (estilo del taller, respuesta JSON)

Basada en las prácticas de este taller (`Product`, migraciones, `$fillable`, getters/setters, validación inline, rutas en `web.php`). **Sin vistas Blade**: las respuestas se ven en JSON (navegador, Postman o `curl`).

Requisitos: **PHP ^8.2**, **Laravel 12**, MySQL/SQLite configurado en `.env`.

---

## 0. Proyecto desde cero

```bash
composer create-project laravel/laravel mi-crud
cd mi-crud
cp .env.example .env
php artisan key:generate
```

Configura la BD en `.env` (`DB_CONNECTION`, `DB_DATABASE`, etc.) y arranca:

```bash
php artisan serve
```

Ejemplo con entidad **Product** (`name`, `price`), como en el taller.

---

## 1. Migración

```bash
php artisan make:migration create_products_table
```

En `database/migrations/..._create_products_table.php`:

```php
public function up(): void
{
    Schema::create('products', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->integer('price');
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('products');
}
```

```bash
php artisan migrate
```

---

## 2. Modelo (estilo del taller)

```bash
php artisan make:model Product
```

En `app/Models/Product.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    /**
     * PRODUCT ATTRIBUTES
     * $this->attributes['id'] - int - contains the product primary key (id)
     * $this->attributes['name'] - string - contains the product name
     * $this->attributes['price'] - int - contains the product price
     */

    protected $fillable = ['name', 'price'];

    public function getId(): int
    {
        return $this->attributes['id'];
    }

    public function setId($id): void
    {
        $this->attributes['id'] = $id;
    }

    public function getName(): string
    {
        return $this->attributes['name'];
    }

    public function setName($name): void
    {
        $this->attributes['name'] = $name;
    }

    public function getPrice(): int
    {
        return $this->attributes['price'];
    }

    public function setPrice($price): void
    {
        $this->attributes['price'] = $price;
    }
}
```

Puntos del taller: docblock de atributos, `$fillable` para `create`/`update`, getters/setters sobre `$this->attributes`.

---

## 3. Factory y seeder (datos de prueba)

```bash
php artisan make:factory ProductFactory --model=Product
```

`database/factories/ProductFactory.php`:

```php
public function definition(): array
{
    return [
        'name' => fake()->name(),
        'price' => fake()->numberBetween(10, 100),
    ];
}
```

En `database/seeders/DatabaseSeeder.php`:

```php
use App\Models\Product;

public function run(): void
{
    Product::factory(8)->create();
}
```

```bash
php artisan db:seed
```

---

## 4. Controlador CRUD (JSON, sin front)

```bash
php artisan make:controller ProductController
```

En `app/Http/Controllers/ProductController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // LISTAR
    public function index()
    {
        return response()->json(Product::all());
    }

    // VER UNO
    public function show(string $id)
    {
        $product = Product::findOrFail($id);
        return response()->json($product);
    }

    // CREAR (en el taller se llama save, no store)
    public function save(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'price' => 'required',
        ]);

        $product = Product::create($request->only(['name', 'price']));

        return response()->json($product, 201);
    }

    // ACTUALIZAR
    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required',
            'price' => 'required',
        ]);

        $product = Product::findOrFail($id);
        $product->update($request->only(['name', 'price']));

        return response()->json($product);
    }

    // ELIMINAR
    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json(['message' => 'Product deleted']);
    }
}
```

Diferencia con el taller: en vez de `view(...)->with("viewData", ...)` o `back()`, se usa `response()->json(...)`.

---

## 5. Rutas (`routes/web.php`)

Mismo estilo del taller (`Controller@metodo` + nombres `product.*`). Rutas estáticas **antes** de `{id}`:

```php
use Illuminate\Support\Facades\Route;

Route::get('/products', 'App\Http\Controllers\ProductController@index')->name('product.index');
Route::post('/products/save', 'App\Http\Controllers\ProductController@save')->name('product.save');
Route::get('/products/{id}', 'App\Http\Controllers\ProductController@show')->name('product.show');
Route::put('/products/{id}', 'App\Http\Controllers\ProductController@update')->name('product.update');
Route::delete('/products/{id}', 'App\Http\Controllers\ProductController@destroy')->name('product.destroy');
```

> En Laravel, para `PUT`/`DELETE` desde formularios HTML haría falta `_method`. Con Postman/`curl` no hace falta.

### Nota sobre CSRF

Las rutas en `web.php` llevan middleware CSRF. Para probar POST/PUT/DELETE sin front:

- **Opción A:** Postman con sesión + header `X-XSRF-TOKEN`
- **Opción B (laboratorio):** excluir esas rutas del CSRF en desarrollo

Para laboratorio rápido, en `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: [
        'products/*',
        'products',
    ]);
})
```

(Úsalo solo en desarrollo.)

Con el header `Accept: application/json`, Laravel responde errores de validación en JSON.

---

## 6. Probar el CRUD

Con el servidor en `http://127.0.0.1:8000`:

| Acción     | Método | URL               | Body (JSON)                         |
|------------|--------|-------------------|-------------------------------------|
| Listar     | GET    | `/products`       | —                                   |
| Ver        | GET    | `/products/1`     | —                                   |
| Crear      | POST   | `/products/save`  | `{"name":"TV","price":1000}`        |
| Actualizar | PUT    | `/products/1`     | `{"name":"TV 4K","price":1200}`     |
| Eliminar   | DELETE | `/products/1`     | —                                   |

`curl` (ejemplo crear en Windows PowerShell):

```bash
curl -X POST http://127.0.0.1:8000/products/save `
  -H "Content-Type: application/json" `
  -H "Accept: application/json" `
  -d "{\"name\":\"TV\",\"price\":1000}"
```

Respuesta esperada al listar: array JSON de productos. Al crear: el producto con `id`, `name`, `price`, `created_at`, `updated_at`.

---

## 7. Orden mental del taller

1. Migración → tabla
2. Modelo → `$fillable` + getters/setters
3. Factory + seeder → datos
4. Controlador → Eloquent (`all`, `findOrFail`, `create`, `update`, `delete`) + `validate`
5. Rutas en `web.php` con nombres `product.*`
6. Respuesta JSON (en el taller real irían Blade + `$viewData`)

---

## Resumen de convenciones del taller

| Pieza       | Convención                                              |
|-------------|---------------------------------------------------------|
| Controlador | `ProductController`                                     |
| Crear       | método `save` + ruta `POST /products/save`              |
| Rutas       | string `'App\Http\Controllers\ProductController@...'`   |
| Nombres     | `product.index`, `product.show`, `product.save`         |
| Validación  | `$request->validate([...])` en el controlador           |
| Modelo      | `$fillable` + getters/setters + docblock ATTRIBUTES     |
| Sin         | Form Requests, services, repositories, `Route::resource`|
