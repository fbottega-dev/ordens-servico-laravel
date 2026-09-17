<?php

namespace Tests\Feature;

use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        $u = User::factory()->create();
        $u->role = 'staff';
        $u->save();

        return $u;
    }

    private function order(User $owner): ServiceOrder
    {
        $this->actingAs($owner)->post('/orders', ['equipment' => 'Notebook', 'description' => 'Não liga'])->assertRedirect();

        return ServiceOrder::latest('id')->firstOrFail();
    }

    public function test_guests_cannot_access_orders(): void
    {
        $this->get('/orders')->assertRedirect('/login');
    }

    public function test_customers_cannot_read_other_orders(): void
    {
        $order = $this->order(User::factory()->create());
        $other = User::factory()->create();
        $this->actingAs($other)->get('/orders/'.$order->id)->assertForbidden();
        $this->get('/orders')->assertDontSee('Notebook');
        $this->post('/orders/'.$order->id.'/transition', ['action' => 'cancel'])->assertForbidden();
    }

    public function test_customer_cannot_quote_or_skip_approval(): void
    {
        $owner = User::factory()->create();
        $order = $this->order($owner);
        $this->post('/orders/'.$order->id.'/transition', ['action' => 'quote', 'quote_cents' => 10000, 'diagnosis' => 'Fonte'])->assertSessionHasErrors('action');
        $this->actingAs($this->staff())->post('/orders/'.$order->id.'/transition', ['action' => 'start'])->assertSessionHasErrors('action');
        $this->assertDatabaseHas('service_orders', ['id' => $order->id, 'status' => 'received']);
    }

    public function test_full_workflow_records_audit_and_blocks_repeated_completion(): void
    {
        $owner = User::factory()->create();
        $staff = $this->staff();
        $order = $this->order($owner);
        $url = '/orders/'.$order->id.'/transition';
        $this->actingAs($staff)->post($url, ['action' => 'quote', 'quote_cents' => 15000, 'diagnosis' => 'Troca de fonte'])->assertSessionHasNoErrors();
        $this->post($url, ['action' => 'approve'])->assertSessionHasErrors('action');
        $this->actingAs($owner)->post($url, ['action' => 'approve'])->assertSessionHasNoErrors();
        $this->actingAs($staff)->post($url, ['action' => 'start'])->assertSessionHasNoErrors();
        $this->post($url, ['action' => 'complete'])->assertSessionHasNoErrors();
        $this->post($url, ['action' => 'complete'])->assertSessionHasErrors('action');
        $this->assertDatabaseHas('service_orders', ['id' => $order->id, 'status' => 'completed', 'quote_cents' => 15000]);
        $this->assertDatabaseCount('order_events', 5);
    }

    public function test_owner_is_taken_from_session_and_not_request(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($owner)->post('/orders', ['equipment' => 'Celular', 'description' => 'Tela quebrada', 'user_id' => $other->id, 'status' => 'completed'])->assertRedirect();
        $this->assertDatabaseHas('service_orders', ['user_id' => $owner->id, 'status' => 'received']);
    }

    public function test_validation_prevents_empty_description_and_negative_quote(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner)->post('/orders', ['equipment' => 'Tablet', 'description' => ''])->assertSessionHasErrors('description');
        $order = $this->order($owner);
        $this->actingAs($this->staff())->post('/orders/'.$order->id.'/transition', ['action' => 'quote', 'quote_cents' => -1, 'diagnosis' => 'Teste'])->assertSessionHasErrors('quote_cents');
    }

    public function test_registration_cannot_elevate_role_and_login_works(): void
    {
        $this->post('/register', ['name' => 'Ana', 'email' => 'ana@example.test', 'password' => 'Password123', 'password_confirmation' => 'Password123', 'role' => 'staff'])->assertRedirect('/orders');
        $this->assertDatabaseHas('users', ['email' => 'ana@example.test', 'role' => 'customer']);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
        $this->post('/login', ['email' => 'ana@example.test', 'password' => 'wrong'])->assertSessionHasErrors();
        $this->assertGuest();
        $this->post('/login', ['email' => 'ana@example.test', 'password' => 'Password123'])->assertRedirect('/orders');
        $this->assertAuthenticated();
    }
}
