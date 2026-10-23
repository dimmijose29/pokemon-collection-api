<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Blocca i test prima di RefreshDatabase se il database non è quello di test:
     * altrimenti un errore di configurazione svuoterebbe il database di sviluppo.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $config = $app['config'];
        $database = $config->get('database.connections.' . $config->get('database.default') . '.database');

        if ($database !== 'testing') {
            throw new RuntimeException(
                "I test devono usare il database 'testing', configurato: '{$database}'. Controlla phpunit.xml."
            );
        }

        return $app;
    }
}
