<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use LogicException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $application = parent::createApplication();
        $connection_name = $application['config']->get('database.default');
        $connection_config = $application['config']->get('database.connections.'.$connection_name);

        if ($connection_name !== 'mysql' || $connection_config['database'] !== 'testing' || ! empty($connection_config['url'])) {
            throw new LogicException('テストはMySQLの専用testingデータベースでのみ実行できます。');
        }

        return $application;
    }
}
