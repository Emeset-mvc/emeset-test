# FirstStepsUnitTesting – Primeres passes amb testos unitaris a Emeset

Objectiu: escriure un test d’un **controlador** que treballa amb la **base de dades**.

L’exemple està pensat per a un projecte Emeset tipus:

- model `App\Models\Links`
- controlador `App\Controllers\Links`
- base de dades amb taules `users` i `links` (projecte *links*).

---

## 0. Punt de partida

Abans de començar, assegura’t que:

1. Al projecte has instal·lat el paquet:

   ```bash
   composer require --dev emeset/test
   ```

2. Tens:

   - un fitxer `phpunit.xml` a l’arrel,
   - un fitxer `tests/bootstrap.php`,
   - un primer test simple (`SmokeTest`) que passa.

Si això no ho tens, mira el `README.md` del paquet abans de seguir.

---

## 1. Preparar l’entorn de base de dades per a tests

No volem fer servir la **mateixa** base de dades que l’aplicació real (producció / desenvolupament).  
Per això crearem una base de dades de **test** i un fitxer `.env.test`.

### 1.1. Crear la base de dades de test

1. Crea una BD nova, per exemple `links_test`.
2. Importa-hi l’SQL del projecte (per exemple, `Db/Db.sql`):
   - Taules: `users`, `links`…
   - Algunes dades d’exemple.

Ara tens una còpia de la BD només per als tests.

### 1.2. Fitxer `.env.test`

A l’arrel del projecte (on hi ha `composer.json`), crea un fitxer `.env.test`:

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=links_test
DB_USER=root
DB_PASS=secret
```

Quan executis PHPUnit, el contenidor d’Emeset detectarà que estàs en mode test (`PHPUNIT_RUNNING=1`) i, si existeix `.env.test`, el farà servir en lloc de `.env`.

> **Important:** així els tests NO toquen les dades reals del teu projecte.

---

## 2. Entendre què fa el controlador `Links`

El controlador `App\Controllers\Links` té un mètode `index` que fa a:

- Llegeix l’usuari logat de la sessió (`$_SESSION['user_id']`).  
- Demana al model `Links` els enllaços d’aquest usuari.  
- Els passa a una vista `links/index.php` per mostrar-los.

Això ens permet provar tres coses alhora:

1. Sessió (`$_SESSION`).  
2. Accés a base de dades (model).  
3. Vista (plantilla carregada i dades passades).

---

## 3. Crear el fitxer de test

Crea la carpeta `tests/Controllers` (si no existeix) i dins el fitxer:

`tests/Controllers/LinksControllerTest.php`

```php
<?php

namespace Tests\Controllers;

use Emeset\Test\TestCase;
use App\Controllers\Links;
use App\Models\Links as LinksModel;

class LinksControllerTest extends TestCase
{
    protected LinksModel $linksModel;

    protected function setUp(): void
    {
        parent::setUp();

        // Obtenim el model Links del contenidor
        $this->linksModel = $this->container->get('Links');

        // (Opcional) podríem aquí netejar i reomplir la BD de test
        // per assegurar-nos que sempre té el mateix estat.
    }

    public function test_index_mostra_links_de_l_usuari_logat()
    {
        // 1. ARRANGE (Preparar)

        // Sabem que la BD de test, l'usuari amb id=1 té alguns links
        $_SESSION['user'] = [
            "name" => "user",
            "user" => "user@cendrassos.net",
            "id" => 1
        ];

        $request  = $this->makeRequest();
        $response = $this->makeResponse();

        $controller = new Links();

        // 2. ACT (Actuar)
        $response = $controller->index($request, $response, $this->container);

        // 3. ASSERT (Comprovar)

        // Comprovem que s'ha carregat la plantilla correcta
        $view = $response->getView();
        $this->assertEquals('list.php', $view->getTemplate());

        // Si la vista té un mètode getValues(), podem comprovar les dades:

        // $values = $view->getValues();
        // $links  = $values['links'];
        //
        // $this->assertIsArray($links);
        // $this->assertNotEmpty($links);
        //
        // foreach ($links as $link) {
        //     $this->assertEquals(1, $link['user_id']);
        // }
    }
}
```

Observa l’estructura **AAA** (Arrange – Act – Assert):

1. **Arrange (Preparar)**: configures la sessió, la BD, la request…  
2. **Act (Actuar)**: crides el mètode del controlador.  
3. **Assert (Comprovar)**: mires què ha passat (vista, dades, redireccions…).

---

## 4. Gestionar l’estat de la base de dades

Per garantir que els testos sempre donen el mateix resultat ens cal reiniciar la base dades, per fer-ho crearem un classe que extendrà el testCase, testBdCase a la carpeta tests.

```php
<?php

namespace Emeset\Tests;

use Emeset\Test\TestCase;

/**
 * Classe base per a tests que necessiten treballar amb base de dades.
 *
 * Aquesta classe:
 *   - Reinicia la base de dades abans de cada test,
 *   - Torna a carregar el fitxer Db/Db.sql,
 *   - Proporciona un helper db() per accedir a la connexió.
 *
 * Recomanada per a tests de models i controladors que interactuen amb BD.
 */
abstract class TestDbCase extends TestCase
{
    /**
     * Reinicia la base de dades carregant novament Db/Db.sql
     */
    protected function resetDatabase(): void
    {
        // Obtenim la connexió del contenidor
        $db = $this->container->get('db');

        // Ruta al fitxer Db.sql del projecte
        $sqlPath = __DIR__ . '/../Db/Db.sql';

        if (!file_exists($sqlPath)) {
            throw new \RuntimeException("No s'ha trobat el fitxer de base de dades: $sqlPath");
        }

        // Llegim el SQL i l’executem
        $sql = file_get_contents($sqlPath);

        try {
            $db->exec($sql);
        } catch (\Throwable $e) {
            throw new \RuntimeException("Error reexecutant el fitxer Db.sql: " . $e->getMessage());
        }
    }

    /**
     * Helper còmode per obtenir l'objecte DB
     */
    protected function db()
    {
        return $this->container->get('db');
    }

    /**
     * Preparem cada test: crida a TestCase::setUp() i reiniciem BD
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Reiniciar la BD abans de cada test
        $this->resetDatabase();
    }
}

```

Això garanteix que **cada test** comença amb la BD en el mateix estat.

---

## 5. Executar i interpretar el resultat del test

Executa:

```bash
composer test
```

Si tot va bé, hauries de veure alguna cosa així:

```text
OK (1 test, 1 assertion)
```

Si falla, per exemple per una plantilla mal escrita (`links/index.php` en lloc de `links/index.php`), veuràs un missatge semblant a:

```text
Failed asserting that 'links/index.php' matches expected 'links/index.php'.
```

---


## 6. Resum

En aquest document has vist:

- Com separar la base de dades de tests (`links_test`) de la base de dades real.  
- Com configurar `.env.test` perquè Emeset utilitzi la BD de test quan s’executen els tests.  
- Com escriure un test d’un controlador que accedeix a BD, mitjançant la `Emeset\Test\TestCase`.  
- Com organitzar els tests amb l’esquema **Arrange – Act – Assert**.  
- Algunes idees d’exercicis per practicar.

A partir d’aquí, pots començar a provar:

- altres controladors,
- models directament,
- middlewares que treballen amb sessió i base de dades.

L’important és **fer petits passos** i llegir bé el que diu PHPUnit quan alguna cosa falla.
