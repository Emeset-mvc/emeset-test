<?php

namespace Tests\Middleware;

use Emeset\Test\TestCase;
use Emeset\Http\Request;
use Emeset\Http\Response;

// Incloem manualment el middleware de l’app
require_once __DIR__ . '/../App/Middleware/auth.php';

class AuthMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Netejem sessió per cada test
        $_SESSION = [];
    }

    public function test_auth_redirigeix_a_login_si_no_estas_logat()
    {
        $request = $this->makeRequest();
        $response = $this->makeResponse();

        // Cap usuari logat
        unset($_SESSION['usuari'], $_SESSION['logat']);

        $next = function ($request, $response, $container) {
            // No s’hauria de cridar en aquest cas
            $this->fail('No s’hauria d’arribar al següent middleware/controlador si no hi ha login');
        };

        $response = \auth($request, $response, $this->container, $next);

        $this->assertTrue($response->isRedirect());
        $this->assertSame('location: /login', $response->getHeader());
    }

    public function test_auth_deixa_passar_i_afegeix_usuari_a_la_resposta()
    {
        $request = $this->makeRequest();
        $response = $this->makeResponse();

        // Simulem sessió d’usuari
        $_SESSION['usuari'] = 'alumne@test';
        $_SESSION['logat'] = true;

        $response->set("next", false);
        $next = function ($request, $response, $container) use (&$nextExecutat) {
            $response->set("next", true);
            return $response;
        };

        $response = \auth($request, $response, $this->container, $next);

        $this->assertFalse($response->isRedirect());
        $values = $response->getView()->getValues();
        $this->assertTrue($values["next"]);

    }
}
