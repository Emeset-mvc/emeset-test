<?php

namespace Tests\Controllers;

use Emeset\Test\TestCase;
use App\Controllers\Portada;

class PortadaControllerTest extends TestCase
{
    public function test_index_carrega_links_de_l_usuari_i_template_list()
    {
        

        // 1. Obtenim Request i Response del contenidor
        $request  = $this->makeRequest();
        $response = $this->makeResponse();

        // 2. Contenidor i controlador
        $container  = $this->container;
        $controller = new Portada();

        // 3. Executem el controlador
        $response = $controller->index($request, $response, $container);

        // 4. Assert bàsics:
        //    - no ha de ser una redirecció
        //    - ha de tenir el template "portada.php"
        
        $this->assertFalse($response->isRedirect(), 'No hauria de redirigir');

        $view = $response->getView();
        $this->assertEquals('portada.php', $view->getTemplate());
    }
}
