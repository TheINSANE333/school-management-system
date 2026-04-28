<?php

namespace Tests\Browser;

use App\Http\Helpers\AppHelper;
use App\Role;
use App\User;
use App\UserRole;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LoginTest extends DuskTestCase
{
    // use DatabaseMigrations;

   protected function setUp(): void
    {
        parent::setUp();

        $this->browse(function (Browser $browser) {
            $browser->logout();
        });
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
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->type('[name="username"]', 'admin')
                    ->type('[name="password"]', 'demo123')
                    ->press('SIGN IN')
                    ->waitForLocation('/dashboard')
                    ->assertPathIs('/dashboard')
                    ->assertSee('Welcome');
        });
    }

    /**
     * Test successful Student login and redirect to dashboard with student view.
     *
     * @return void
     */
    public function testStudentLogin()
    {
        $user = User::where('username', 'studentuser')->first();

        if (!$user) {
            $user = User::create([
                'name' => 'Test Student',
                'username' => 'studentuser',
                'email' => 'student@example.com',
                'password' => bcrypt('osdjaji122'),
                'status' => AppHelper::ACTIVE,
            ]);
        }

        $studentRole = Role::where('name', 'Student')->first();

        UserRole::firstOrCreate([
            'user_id' => $user->id,
            'role_id' => $studentRole->id
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/login')
                    ->type('[name="username"]', 'studentuser')
                    ->type('[name="password"]', 'osdjaji122')
                    ->press('SIGN IN')
                    ->waitForLocation('/dashboard')
                    ->assertPathIs('/dashboard')
                    ->assertSee('Dashboard')
                    ->assertSee('Welcome to CloudSchool')
                    ->assertDontSee('Employee')
                    ->assertDontSee('Teacher');
        });
    }
}
