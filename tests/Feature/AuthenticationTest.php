<?php

namespace Tests\Feature;

use App\Role;
use App\User;
use App\UserRole;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Helpers\AppHelper;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_user_can_view_the_login_form()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertViewIs('backend.user.login');
    }

    /** @test */
    public function a_user_can_login_with_correct_credentials()
    {
        // Setup: Create a role and a user
        $role = Role::create([
            'name' => 'Admin',
            'deletable' => false
        ]);

        $user = factory(User::class)->create([
            'username' => 'testuser',
            'password' => bcrypt('password123'),
            'status' => AppHelper::ACTIVE,
        ]);

        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id
        ]);

        $response = $this->post('/login', [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertEquals($role->id, session('user_role_id'));
    }

    /** @test */
    public function a_user_cannot_login_with_incorrect_password()
    {
        $user = factory(User::class)->create([
            'username' => 'testuser',
            'password' => bcrypt('password123'),
            'status' => AppHelper::ACTIVE,
        ]);

        $response = $this->from('/login')->post('/login', [
            'username' => 'testuser',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('error');
        $this->assertGuest();
    }

    /** @test */
    public function a_user_cannot_login_if_inactive()
    {
        $user = factory(User::class)->create([
            'username' => 'testuser',
            'password' => bcrypt('password123'),
            'status' => AppHelper::INACTIVE,
        ]);

        $response = $this->from('/login')->post('/login', [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    /** @test */
    public function an_authenticated_user_can_logout()
    {
        $role = Role::create(['name' => 'Admin']);
        $user = factory(User::class)->create([
            'is_super_admin' => true
        ]);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id]);

        $this->be($user);

        $response = $this->get('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }
}
