<?php

namespace Emeset\Test;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Emeset\Contracts\Container as ContainerContract;
use Emeset\Container as EmesetContainer;

abstract class TestCase extends BaseTestCase
{
    protected ContainerContract $container;

    /**
     * Ruta al fitxer de configuració del projecte.
     * Per defecte: App/config.php
     */
    protected function getConfigPath(): string
    {
        // Permet sobreescriure fàcilment a tests concrets si cal
        return 'App/config.php';
    }

    /**
     * Crea el contenidor del projecte.
     *
     * Si existeix App\Container el fem servir; sinó,
     * fem servir directament Emeset\Container.
     */
    protected function createContainer(): ContainerContract
    {
        $configPath = $this->getConfigPath();

        if (class_exists(\App\Container::class)) {
            return new \App\Container($configPath);
        }

        return new EmesetContainer($configPath);
    }

    /**
     * Configuració comuna abans de cada test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Ens assegurem que estem en mode test (per .env.test)
        if (getenv('PHPUNIT_RUNNING') !== '1') {
            putenv('PHPUNIT_RUNNING=1');
        }

        $this->container = $this->createContainer();

        $_SESSION = [];
    }

    /**
     * Helper còmode per obtenir Request del contenidor.
     */
    protected function makeRequest($get = [], $post = [], $session = [])
    {
        return \Emeset\Http\Request::fake(
            get: $get,
            post: $post,
            session: $session,            
        );
    }

    /**
     * Helper còmode per obtenir Response del contenidor.
     */
    protected function makeResponse()
    {
        return $this->container->get('response');
    }

    protected function call(
        string $method,
        string $uri,
        array $query = [],
        array $post = [],
        array $session = []
    ): \Emeset\Contracts\Http\Response {
        // Preparem globals d’HTTP perquè RouterHttp faci match
        $_SERVER['REQUEST_METHOD'] = strtoupper($method);
        $_SERVER['REQUEST_URI']    = $uri;

        unset($this->container['request']);

        $fakeRequest = \Emeset\Http\Request::fake($query, $post, $session, [], $_SERVER);
        // Creem un Request fake amb GET/POST/SESSION
        $this->container['request'] = function($c) use($fakeRequest) {
            return $fakeRequest;
        };

        // Obtenim una Response del contenidor
        $response = $this->container->get('response');
        $request = $this->container->get('request');

        $app = new \Emeset\Emeset($this->container);
        // Carreguem les mateixes rutes que a public/index.php
        $routesFile = getcwd() . '/App/routes.php';
        if (!is_file($routesFile)) {
            $this->fail("No s'ha trobat el fitxer de rutes: {$routesFile}");
        }
        require $routesFile;

        // Ens assegurem que l’app fa servir el nostre Request i Response
        $app->request  = $request;
        $app->response = $response;

        // 6. Executem el pipeline (middleware + router + controlador)
        return $app->handle(); // NO fa ->response(), només retorna l’objecte
    }

    protected function get(string $uri, array $query = [], array $session = [])
    {
        return $this->call('GET', $uri, $query, [], $session);
    }

    protected function post(string $uri, array $post = [], array $session = [])
    {
        return $this->call('POST', $uri, [], $post, $session);
    }
}
