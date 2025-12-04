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
    protected function makeRequest()
    {
        return $this->container->get('request');
    }

    /**
     * Helper còmode per obtenir Response del contenidor.
     */
    protected function makeResponse()
    {
        return $this->container->get('response');
    }
}
