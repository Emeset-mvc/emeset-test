<?php

namespace Emeset\Test;

use Emeset\Test\TestCase;

/**
 * Classe base per a tests que necessiten reiniciar la base de dades.
 *
 * Funcionalitats:
 *   - Reinicia la BD abans de cada test (carregant Db/Db.sql).
 *   - Dona un helper db() per accedir ràpidament a la connexió.
 */
abstract class TestDbCase extends TestCase
{
    /**
     * Retorna la ruta al fitxer SQL del projecte.
     * Es pot sobreescriure si un projecte té un layout diferent.
     */
    protected function getSqlPath(): string
    {
        // Ruta típica: PROJECT_ROOT/Db/Db.sql
        return getcwd() . '/Db/Db.sql';
    }

    /**
     * Reinicia la base de dades carregant novament Db.sql
     */
    protected function resetDatabase(): void
    {
        $sqlPath = $this->getSqlPath();

        if (!file_exists($sqlPath)) {
            throw new \RuntimeException("No s'ha trobat el fitxer de BD: $sqlPath");
        }

        $sql = file_get_contents($sqlPath);

        $db = $this->db();

        try {
            $db->getDb()->exec($sql);
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                "Error executant Db.sql: " . $e->getMessage()
            );
        }
    }

    /**
     * Helper per obtenir la connexió de base de dades del contenidor
     */
    protected function db()
    {
        return $this->container->get('Db');
    }

    /**
     * Preparem cada test: TestCase::setUp() + reinici de BD
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetDatabase();
    }
}

