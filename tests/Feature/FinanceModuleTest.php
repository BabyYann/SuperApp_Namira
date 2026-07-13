<?php

use App\Modules\Yayasan\Models\SystemSetting;
use App\Modules\Finance\Models\FinanceType;
use App\Modules\Finance\Models\StudentBill;
use App\Modules\Finance\Models\FinanceAccount;
use Illuminate\Support\Facades\Cache;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->unit = createUnit();
    $this->user = createUserWithRole('admin_unit', $this->unit->id);

    SystemSetting::setSetting('feature_finance', '1');
    Cache::forget('system_settings');
});

it('can display finance types index', function () {
    FinanceType::create(['unit_id' => $this->unit->id, 'name' => 'SPP', 'amount' => 500000]);

    $response = $this->actingAs($this->user)
        ->get(route('yayasan.finance.types.index'));

    $response->assertOk();
    $response->assertSee('SPP');
});

it('can create a finance type', function () {
    $response = $this->actingAs($this->user)
        ->post(route('yayasan.finance.types.store'), [
            'name' => 'Uang Gedung',
            'amount' => 2000000,
            'billing_cycle' => 'once',
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('finance_types', [
        'unit_id' => $this->unit->id,
        'name' => 'Uang Gedung',
    ]);
});

it('can display finance accounts index', function () {
    FinanceAccount::create(['bank_name' => 'Bank Jatim', 'account_number' => '1234567890', 'account_name' => 'Yayasan Namira']);

    $response = $this->actingAs($this->user)
        ->get(route('yayasan.finance.accounts.index'));

    $response->assertOk();
    $response->assertSee('Bank Jatim');
});

it('can create a finance account', function () {
    $admin = createUserWithRole('admin_yayasan');
    $response = $this->actingAs($admin)
        ->post(route('yayasan.finance.accounts.store'), [
            'bank_name' => 'Bank Mandiri',
            'account_number' => '9876543210',
            'account_name' => 'Yayasan Namira Mandiri',
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('finance_accounts', [
        'bank_name' => 'Bank Mandiri',
        'account_number' => '9876543210',
    ]);
});

it('can display student bills index', function () {
    $classroom = \App\Modules\Academic\Models\Classroom::create(['unit_id' => $this->unit->id, 'name' => 'Kelas 1A', 'level' => '1']);
    $studentUser = \App\Models\User::create(['name' => 'Ahmad', 'email' => 'ahmad_' . uniqid() . '@test.com', 'password' => bcrypt('password')]);
    $student = \App\Modules\Academic\Models\Student::create(['user_id' => $studentUser->id, 'unit_id' => $this->unit->id, 'classroom_id' => $classroom->id, 'full_name' => 'Ahmad', 'gender' => 'L']);

    StudentBill::create([
        'student_id' => $student->id,
        'bill_code' => 'INV/' . uniqid(),
        'description' => 'SPP Januari',
        'billing_date' => now(),
        'original_amount' => 500000,
        'discount_amount' => 0,
        'final_amount' => 500000,
        'paid_amount' => 0,
        'status' => 'unpaid',
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('yayasan.finance.bills.index'));

    $response->assertOk();
    $response->assertSee('SPP Januari');
});

it('can display transactions index', function () {
    $response = $this->actingAs($this->user)
        ->get(route('yayasan.finance.transactions.index'));

    $response->assertOk();
});

it('blocks access when finance feature is disabled', function () {
    SystemSetting::setSetting('feature_finance', '0');
    Cache::forget('system_settings');

    $response = $this->actingAs($this->user)
        ->get(route('yayasan.finance.types.index'));

    $response->assertStatus(403);
});
