<?php

namespace Tests\Feature;

use App\AppMeta;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasicTest()
    {
        // Seed the setting required by Frontend middleware
        AppMeta::create([
            'meta_key' => 'frontend_website',
            'meta_value' => '1'
        ]);
        Cache::forget('app_settings');

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
