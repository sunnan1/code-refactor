<?php
namespace Tests\Feature;

use Tests\TestCase;
use Carbon\Carbon;
use App\Models\Job;

class JobExpirationTest extends TestCase
{
    public function testWillExpireAtWithin90Minutes()
    {
        $dueTime = Carbon::now()->addMinutes(60)->format('Y-m-d H:i:s');
        $createdAt = Carbon::now()->format('Y-m-d H:i:s');

        $expectedExpiration = Carbon::parse($dueTime)->format('Y-m-d H:i:s');
        $actualExpiration = Job::willExpireAt($dueTime, $createdAt);

        $this->assertEquals($expectedExpiration, $actualExpiration, "The expiration time should match the due time when within 90 minutes.");
    }
    public function testWillExpireAtWithin24Hours()
    {
        $dueTime = Carbon::now()->addHours(10)->format('Y-m-d H:i:s');
        $createdAt = Carbon::now()->format('Y-m-d H:i:s');

        $expectedExpiration = Carbon::parse($createdAt)->addMinutes(90)->format('Y-m-d H:i:s');
        $actualExpiration = Job::willExpireAt($dueTime, $createdAt);

        $this->assertEquals($expectedExpiration, $actualExpiration, "The expiration time should be created_at + 90 minutes when within 24 hours.");
    }
    public function testWillExpireAtBetween24And72Hours()
    {
        $dueTime = Carbon::now()->addHours(48)->format('Y-m-d H:i:s');
        $createdAt = Carbon::now()->format('Y-m-d H:i:s');

        $expectedExpiration = Carbon::parse($createdAt)->addHours(16)->format('Y-m-d H:i:s');
        $actualExpiration = Job::willExpireAt($dueTime, $createdAt);

        $this->assertEquals($expectedExpiration, $actualExpiration, "The expiration time should be created_at + 16 hours when between 24 and 72 hours.");
    }
    public function testWillExpireAtGreaterThan72Hours()
    {
        $dueTime = Carbon::now()->addHours(100)->format('Y-m-d H:i:s');
        $createdAt = Carbon::now()->format('Y-m-d H:i:s');

        $expectedExpiration = Carbon::parse($dueTime)->subHours(48)->format('Y-m-d H:i:s');
        $actualExpiration = Job::willExpireAt($dueTime, $createdAt);

        $this->assertEquals($expectedExpiration, $actualExpiration, "The expiration time should be due_time - 48 hours when the difference is greater than 72 hours.");
    }
}
?>