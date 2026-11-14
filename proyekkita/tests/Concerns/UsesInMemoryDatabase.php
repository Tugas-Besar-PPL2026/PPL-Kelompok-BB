<?php

namespace Tests\Concerns;

/**
 * Memaksa test memakai SQLite in-memory.
 *
 * phpunit.xml proyek ini tidak menetapkan koneksi database untuk test, sehingga RefreshDatabase akan memakai
 * database dari .env dan MENGHAPUS isinya. Trait ini mencegah hal itu tanpa mengubah phpunit.xml.
 * Pakai bersama RefreshDatabase pada test yang membutuhkan database.
 */
trait UsesInMemoryDatabase
{
    public function createApplication()
    {
        $app = parent::createApplication();

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');

        return $app;
    }
}
