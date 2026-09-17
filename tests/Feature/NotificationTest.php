<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\User;
use App\Notifications\AlertRaised;
use App\Services\AlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function eligibleRecipients(): int
    {
        return User::where('status', 'active')
            ->whereHas('roles', fn ($q) => $q->whereIn('slug', AlertService::RECIPIENT_ROLES))
            ->count();
    }

    private function makeAlert(string $title = 'Test alert'): Alert
    {
        return Alert::create([
            'severity' => 'critical',
            'title' => $title,
            'message' => 'Something needs attention.',
        ]);
    }

    public function test_raising_an_alert_fans_out_to_every_eligible_user(): void
    {
        $expected = $this->eligibleRecipients();
        $this->assertGreaterThan(0, $expected);

        AlertService::raise('critical', 'Unique alert title', 'Body text');

        $this->assertSame($expected, \DB::table('notifications')->count());
        $this->assertDatabaseHas('notifications', ['type' => AlertRaised::class]);
    }

    public function test_duplicate_alerts_are_not_notified_twice(): void
    {
        $expected = $this->eligibleRecipients();

        AlertService::raise('warning', 'Repeated alert', 'First');
        AlertService::raise('warning', 'Repeated alert', 'Second');

        $this->assertSame(1, Alert::where('title', 'Repeated alert')->count());
        $this->assertSame($expected, \DB::table('notifications')->count());
    }

    public function test_alerts_are_scoped_per_station(): void
    {
        AlertService::raise('info', 'Scoped alert', 'Station one', 1);
        AlertService::raise('info', 'Scoped alert', 'Station two', 2);

        $this->assertSame(2, Alert::where('title', 'Scoped alert')->count());
    }

    public function test_feed_returns_unread_count_and_items(): void
    {
        $super = $this->user('super@fuelcore.test');
        $super->notify(new AlertRaised($this->makeAlert('Feed alert')));

        $this->actingAs($super)
            ->getJson(route('notifications.feed'))
            ->assertOk()
            ->assertJsonStructure(['unread', 'items' => [['id', 'title', 'message', 'severity', 'time', 'url']]])
            ->assertJsonFragment(['title' => 'Feed alert'])
            ->assertJsonPath('unread', 1);
    }

    public function test_marking_a_notification_read_decrements_unread(): void
    {
        $super = $this->user('super@fuelcore.test');
        $super->notify(new AlertRaised($this->makeAlert()));

        $notification = $super->unreadNotifications()->firstOrFail();

        $this->actingAs($super)
            ->patchJson(route('notifications.read', $notification))
            ->assertOk()
            ->assertJson(['unread' => 0]);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_a_user_cannot_mark_another_users_notification_read(): void
    {
        $owner = $this->user('super@fuelcore.test');
        $other = $this->user('manager@fuelcore.test');

        $owner->notify(new AlertRaised($this->makeAlert()));
        $notification = $owner->unreadNotifications()->firstOrFail();

        $this->actingAs($other)
            ->patchJson(route('notifications.read', $notification))
            ->assertForbidden();
    }

    public function test_mark_all_read_clears_the_badge(): void
    {
        $super = $this->user('super@fuelcore.test');
        $super->notify(new AlertRaised($this->makeAlert('One')));
        $super->notify(new AlertRaised($this->makeAlert('Two')));

        $this->assertSame(2, $super->unreadNotifications()->count());

        $this->actingAs($super)
            ->postJson(route('notifications.readAll'))
            ->assertOk()
            ->assertJson(['unread' => 0]);

        $this->assertSame(0, $super->fresh()->unreadNotifications()->count());
    }
}
