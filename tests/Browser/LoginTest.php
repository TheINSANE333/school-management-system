<?php

namespace Tests\Browser;

use App\Http\Helpers\AppHelper;
use App\Role;
use App\User;
use App\UserRole;
use Illuminate\Support\Facades\Artisan;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LoginTest extends DuskTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate:fresh', ['--force' => true]);

        // Seed roles as they are needed for redirection logic
        Role::create(['name' => 'Admin', 'deletable' => false]);
        Role::create(['name' => 'Teacher', 'deletable' => false]);
        Role::create(['name' => 'Student', 'deletable' => false]);
    }

    /**
     * Test failed login with invalid credentials.
     *
     * @return void
     */
    public function testFailedLogin()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login');
            file_put_contents('dusk_source.html', $browser->driver->getPageSource());
            $browser->type('[name="username"]', 'wronguser')
                    ->type('[name="password"]', 'wrongpassword')
                    ->press('SIGN IN')
                    ->assertPathIs('/login')
                    ->assertSee('Your email/password combination was incorrect');
        });
    }

    /**
     * Test successful Admin login and redirect to dashboard.
     *
     * @return void
     */
    public function testAdminLogin()
    {
        $user = User::create([
            'name' => 'Test Admin',
            'username' => 'adminuser',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
            'status' => AppHelper::ACTIVE,
        ]);

        UserRole::create([
            'user_id' => $user->id,
            'role_id' => AppHelper::USER_ADMIN
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/login')
                    ->type('[name="username"]', 'adminuser')
                    ->type('[name="password"]', 'password123')
                    ->press('SIGN IN')
                    ->waitForPath('/dashboard')
                    ->assertPathIs('/dashboard')
                    ->assertSee('Dashboard')
                    ->assertSee('Student')
                    ->assertSee('Teacher')
                    ->assertSee('Employee');
        });
    }

    /**
     * Test successful Student login and redirect to dashboard with student view.
     *
     * @return void
     */
    public function testStudentLogin()
    {
        $user = User::create([
            'name' => 'Test Student',
            'username' => 'studentuser',
            'email' => 'student@example.com',
            'password' => bcrypt('password123'),
            'status' => AppHelper::ACTIVE,
        ]);

        UserRole::create([
            'user_id' => $user->id,
            'role_id' => AppHelper::USER_STUDENT
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/login')
                    ->type('[name="username"]', 'studentuser')
                    ->type('[name="password"]', 'password123')
                    ->press('SIGN IN')
                    ->waitForPath('/dashboard')
                    ->assertPathIs('/dashboard')
                    ->assertSee('Dashboard')
                    ->assertSee('Welcome to CloudSchool')
                    ->assertDontSee('Student') // Admin-only boxes
                    ->assertDontSee('Teacher');
        });
    }
}
