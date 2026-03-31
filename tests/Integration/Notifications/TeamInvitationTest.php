<?php namespace GeneaLabs\LaravelGovernor\Tests\Integration\Notifications;

use GeneaLabs\LaravelGovernor\Tests\UnitTestCase;
use Illuminate\Support\Facades\Notification;
use GeneaLabs\LaravelGovernor\TeamInvitation;
use GeneaLabs\LaravelGovernor\Notifications\TeamInvitation as TeamInvitationNotification;
use GeneaLabs\LaravelGovernor\Tests\Fixtures\User;
use GeneaLabs\LaravelGovernor\Team;
use Illuminate\Notifications\AnonymousNotifiable;

class TeamInvitationTest extends UnitTestCase
{
    public function setUp() : void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->team = (new Team)->create([
            "name" => "Test Team",
            "description" => "bla bla bla",
        ]);
        $this->user->teams()->attach($this->team);
    }

    public function testTeamInvitationNotification()
    {
        $invitation = (new TeamInvitation)->create([
            "team_id" => $this->team->id,
            "email" => "test1@example.com",
        ]);
        $invitations = (new TeamInvitation)->get();

        $this->assertTrue($invitations->contains($invitation));
    }

    public function testNotificationContents()
    {
        $notifyable = (new AnonymousNotifiable)
            ->route("mail", "test2@example.com");
        $invitation = (new TeamInvitation)->create([
            "team_id" => $this->team->id,
            "email" => "test2@example.com",
        ]);

        Notification::fake();

        Notification::send(
            $notifyable,
            new TeamInvitationNotification($invitation)
        );

        Notification::assertSentTo(
            $notifyable,
            TeamInvitationNotification::class,
            function ($notification, $channels, $notifyable) use ($invitation) {
                $mailData = $notification->toMail($notifyable)->toArray();

                $this->assertStringContainsString($invitation->token->toString(), $mailData["actionUrl"]);

                return true;
            }
        );
    }

    public function testNotificationHandlesNullOwnerAndTeamGracefully()
    {
        $invitation = (new TeamInvitation)->create([
            "team_id" => $this->team->id,
            "email" => "test3@example.com",
        ]);

        // Remove the team and ownership to test null-safe accessors
        $invitation->setRelation('team', null);
        $invitation->setRelation('governorOwner', null);

        $notification = new TeamInvitationNotification($invitation);
        $mailData = $notification->toMail(new AnonymousNotifiable)->toArray();

        $this->assertStringContainsString("A team admin", implode(" ", $mailData["introLines"]));
        $this->assertStringContainsString("the team", implode(" ", $mailData["introLines"]));
    }
}
