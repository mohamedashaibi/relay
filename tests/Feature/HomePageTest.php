<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_get_the_english_homepage_at_root(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('lang="en" dir="ltr"', false)
            ->assertSee('Your assistant already knows what’s next.')
            ->assertSee('hreflang="ar"', false)
            ->assertSee('mohamed@ashaibi.com');

        $this->get('/en')->assertOk()->assertSee('Your assistant already knows what’s next.');
    }

    public function test_arabic_homepage_is_right_to_left(): void
    {
        $this->get('/ar')
            ->assertOk()
            ->assertSee('lang="ar" dir="rtl"', false)
            ->assertSee('مساعدك الذكي يعرف الخطوة التالية.')
            ->assertSee('noto-kufi-arabic', false)
            ->assertSee('mohamed@ashaibi.com');
    }

    public function test_signed_in_users_get_the_app_at_root(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertOk()
            ->assertSee('id="app"', false)
            ->assertDontSee('Your assistant already knows');
    }

    public function test_both_languages_define_the_same_strings(): void
    {
        $shape = function (array $a) use (&$shape) {
            return array_map(fn ($v) => is_array($v) ? $shape($v) : 'string', $a);
        };

        $this->assertSame($shape(require lang_path('en/home.php')), $shape(require lang_path('ar/home.php')));
    }
}
