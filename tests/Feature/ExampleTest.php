<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_kok_adres_panele_yonlendirir(): void
    {
        $this->get('/')->assertRedirect('/admin');
    }
}
