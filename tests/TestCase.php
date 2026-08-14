<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function skipWithoutCloud(): void
    {
        if (! class_exists(\Duvento\Cloud\CloudServiceProvider::class)) {
            $this->markTestSkipped('Cloud-пакет не установлен (OSS-сборка).');
        }
    }
}
