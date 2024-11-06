<?php
namespace Tests\Feature;

use Tests\TestCase;
use Carbon\Carbon;
use App\Models\Job;

class JobExpirationTest extends TestCase
{
    public function testCustomerCreationWithPaidConsumerTypeCreatesCompanyAndDepartment()
    {
        $request = [
            'role' => env('CUSTOMER_ROLE_ID'),
            'name' => 'Alice Doe',
            'consumer_type' => 'paid',
            'company_id' => '',
            'department_id' => '',
        ];

        $repository = new UserRepository();
        $user = $repository->createOrUpdate(null, $request);

        $this->assertNotNull($user->company_id);
        $this->assertNotNull($user->department_id);
        $this->assertDatabaseHas('companies', ['id' => $user->company_id, 'name' => 'Alice Doe']);
        $this->assertDatabaseHas('departments', ['id' => $user->department_id, 'name' => 'Alice Doe']);
    }
    public function testBlacklistManagement()
    {
        $existingUser = User::factory()->create(['user_type' => 'customer']);
        UsersBlacklist::factory()->create(['user_id' => $existingUser->id, 'translator_id' => 1]);
        $request = [
            'role' => env('CUSTOMER_ROLE_ID'),
            'translator_ex' => [2, 3]
        ];
        $repository = new UserRepository();
        $user = $repository->createOrUpdate($existingUser->id, $request);
        $this->assertDatabaseHas('users_blacklist', ['user_id' => $user->id, 'translator_id' => 2]);
        $this->assertDatabaseHas('users_blacklist', ['user_id' => $user->id, 'translator_id' => 3]);
        $this->assertDatabaseMissing('users_blacklist', ['user_id' => $user->id, 'translator_id' => 1]);
    }
}
?>