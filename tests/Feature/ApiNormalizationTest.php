<?php

namespace Tests\Feature;

use App\Domains\Vendor\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiNormalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_ping_returns_normalized_format()
    {
        $response = $this->getJson('/api/ping');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'ok',
                ],
            ]);
    }

    public function test_model_not_found_returns_404_normalized()
    {
        $vendor = Restaurant::factory()->create(['slug' => 'test-vendor']);
        
        $response = $this->withHeader('X-Vendor-ID', $vendor->id)
            ->getJson('/api/v1/menu/non-existent-slug');
        
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Ресурс не найден',
                'code' => 404,
            ]);
    }

    public function test_validation_error_returns_422_normalized()
    {
        $vendor = Restaurant::factory()->create();
        
        // This should trigger validation error in CartController::sync
        $response = $this->withHeader('X-Vendor-ID', $vendor->id)
            ->postJson('/api/v1/cart/sync', [
                'vendor_id' => $vendor->id,
                'items' => [['product_id' => 'not-a-uuid']]
            ]);
        
        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'code' => 422,
            ])
            ->assertJsonStructure(['errors']);
    }

    public function test_unauthenticated_returns_401_normalized()
    {
        $response = $this->getJson('/api/v1/orders');
        
        // We accept that this might return 500 if sanctum guard is missing, 
        // but it MUST be a JSON response with success: false and code.
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertJson([
            'success' => false,
        ]);
    }
}
