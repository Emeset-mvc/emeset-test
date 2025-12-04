# emeset/test

Eines de testing per a projectes basats en el microframework docent **Emeset**.

L’objectiu del paquet és que, en un projecte Emeset, puguis escriure tests així de senzills:

```php
use Emeset\Test\TestCase;

class ExempleTest extends TestCase
{
    public function test_exemple()
    {
        $request  = $this->makeRequest();
        $response = $this->makeResponse();

        // ...
    }
}
```

i tenir automàticament disponible:

- el contenidor d’Emeset (`App\Container` o `Emeset\Container`),
- el fitxer de configuració del projecte (`App/config.php`),
- l’entorn de test (`.env.test`),
- una sessió PHP inicialitzada per treballar amb `$_SESSION` i middleware.

---

## Instal·lació

Al teu projecte Emeset (per exemple, el projecte **links** o el projecte de sessió/login), executa:

```bash
composer require --dev emeset/test
```

Això instal·larà:

- el paquet `emeset/test`,
- el framework `emeset/framework` (si no el tens ja),
- i `phpunit/phpunit`.

---

## Configuració bàsica del projecte

El paquet **NO** crea fitxers al teu projecte ni genera res automàticament.  
Tu decideixes on van les coses. Et proposo la següent estructura:

```text
.
├─ App/
├─ public/
├─ vendor/
├─ tests/
│  ├─ bootstrap.php
│  └─ ... 
└─ phpunit.xml

```

### 1. Fitxer `phpunit.xml` a l’arrel del projecte

Crea un fitxer `phpunit.xml` amb aquest contingut mínim:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         cacheDirectory=".phpunit.cache"
         executionOrder="depends,defects"
         beStrictAboutCoverageMetadata="true"
         beStrictAboutOutputDuringTests="true"
         displayDetailsOnPhpunitDeprecations="true"
         failOnPhpunitDeprecation="true">
    <testsuites>
        <testsuite name="default">
            <directory>tests</directory>
        </testsuite>
    </testsuites>

    <source ignoreIndirectDeprecations="true" restrictNotices="true" restrictWarnings="true">
        <include>
            <directory>App/</directory>
        </include>
    </source>
</phpunit>
```

### 2. Fitxer `tests/bootstrap.php`

Crea el fitxer `tests/bootstrap.php`:

```php
<?php

// Carrega l'autoload de Composer (Emeset, el teu projecte, etc.)
require __DIR__ . '/../vendor/autoload.php';

// Marquem explícitament que estem en mode test
putenv('PHPUNIT_RUNNING=1');

// Zona horària per evitar avisos
date_default_timezone_set('Europe/Madrid');
```

### 3. Afegir un script de test a `composer.json` (opcional però recomanat)

Al `composer.json` del teu projecte pots afegir:

```json
"scripts": {
    "test": "phpunit --testdox --colors=always"
}
```

A partir d’ara, podràs executar els tests amb:

```bash
composer test
```

---

## Escriure el primer test (SmokeTest)

Crea la carpeta `tests/` i dins un fitxer `tests/SmokeTest.php`:

```php
<?php

use PHPUnit\Framework\TestCase;

class SmokeTest extends TestCase
{
    public function test_phpunit_funciona()
    {
        $this->assertTrue(true);
    }
}
```

Executa:

```bash
composer test
```

Si tot està bé, veuràs que tens **1 test OK**.  
A partir d’aquí ja pots fer servir la `Emeset\Test\TestCase` per provar el teu codi Emeset.

---

## Classe base `Emeset\Test\TestCase`

Quan vulguis fer tests del teu codi Emeset (controladors, middlewares, models…), fes servir la classe base del paquet:

```php
<?php

namespace Tests\Controllers;

use Emeset\Test\TestCase;
use App\Controllers\Portada;

class PortadaControllerTest extends TestCase
{
    public function test_index()
    {
        $request  = $this->makeRequest();
        $response = $this->makeResponse();

        $controller = new Portada();
        $response = $controller->index($request, $response, $this->container);

        // Asserts aquí...
    }
}
```

### Què fa aquesta TestCase per tu?

A cada test:

- Crea el contenidor del projecte (`App\Container` si existeix, sinó `Emeset\Container`).
- Carrega el fitxer de configuració del projecte (`App/config.php`).
- Marca l’entorn com a **test** (`PHPUNIT_RUNNING=1`) perquè Emeset pugui carregar `.env.test`.
- Assegura que la sessió està engegada (`session_start()` si cal) i neteja `$_SESSION` abans de cada test.
- Et proporciona aquests *helpers*:

```php
$this->container   // Contenidor del projecte
$this->makeRequest()  // Obté una Request del contenidor
$this->makeResponse() // Obté una Response del contenidor
```

---

## Recursos d’exemple al paquet

Dins el paquet `emeset/test` trobaràs una carpeta `resources/` amb exemples:

```text
resources/
├─ phpunit.xml.dist
└─ tests/
   ├─ ExampleSmokeTest.php
   ├─ ExamplePortadaControllerTest.php
   └─ ExampleAuthMiddlewareTest.php
```

### `ExamplePortadaControllerTest.php` (resum)

Exemple de test d’un controlador que carrega una vista:

```php
<?php

use Emeset\Test\TestCase;
use App\Controllers\Portada;
use Emeset\Views\ViewsPHP;

class ExamplePortadaControllerTest extends TestCase
{
    public function test_index_carrega_la_plantilla_portada()
    {
        $request  = $this->makeRequest();
        $response = $this->makeResponse();

        $controller = new Portada();
        $response   = $controller->index($request, $response, $this->container);

        $view = $response->getView();

        $this->assertInstanceOf(ViewsPHP::class, $view);
        $this->assertSame('portada.php', $view->getTemplate());
    }
}
```

---

## Flux de treball recomanat

1. Instal·lar el paquet al projecte Emeset:

   ```bash
   composer require --dev emeset/test
   ```

2. Crear `phpunit.xml` i `tests/bootstrap.php` a partir de les plantilles.

3. Afegir un primer `SmokeTest` senzill.

4. Crear un test de controlador que **no** toqui base de dades (per exemple, Portada).

5. Quan això funcioni, passar al tutorial de `FirstStepsUnitTesting.md` per provar controladors que sí que treballen amb BD.

---

## On seguir

- `FirstStepsUnitTesting.md` → guia pas a pas per crear un test d’un controlador que interactua amb la base de dades.
