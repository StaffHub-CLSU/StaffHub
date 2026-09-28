<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_guest_is_redirected_to_login_from_the_home_page(): void
    {
        $this->get('/')->assertRedirect('/login');
    }
}
