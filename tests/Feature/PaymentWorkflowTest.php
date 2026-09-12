<?php

use App\Enums\EnrollmentStatus;
use App\Enums\PaymentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;

test('a student can request access to a published course', function () {
    $student = User::factory()->create();
    $course = Course::factory()->published()->create(['price' => '29.99']);

    $response = $this->actingAs($student)->post(route('payments.store', $course));

    $response->assertRedirect();
    $this->assertDatabaseHas('payments', [
        'user_id' => $student->id,
        'course_id' => $course->id,
        'amount' => '29.99',
        'status' => PaymentStatus::Pending->value,
    ]);
    $this->assertDatabaseMissing('enrollments', ['user_id' => $student->id, 'course_id' => $course->id]);
});

test('a student cannot request access twice while a payment is pending', function () {
    $student = User::factory()->create();
    $course = Course::factory()->published()->create();

    $this->actingAs($student)->post(route('payments.store', $course));
    $this->actingAs($student)->post(route('payments.store', $course));

    expect(Payment::where('user_id', $student->id)->where('course_id', $course->id)->count())->toBe(1);
});

test('a student cannot request access to a draft course', function () {
    $student = User::factory()->create();
    $course = Course::factory()->create();

    $response = $this->actingAs($student)->post(route('payments.store', $course));

    $response->assertSessionHasErrors('course');
    $this->assertDatabaseMissing('payments', ['user_id' => $student->id, 'course_id' => $course->id]);
});

test('a student already enrolled cannot request access again', function () {
    $student = User::factory()->create();
    $course = Course::factory()->published()->create();
    $student->enrollments()->create([
        'course_id' => $course->id,
        'status' => EnrollmentStatus::Active,
        'enrolled_at' => now(),
    ]);

    $response = $this->actingAs($student)->post(route('payments.store', $course));

    $response->assertSessionHasErrors('course');
    $this->assertDatabaseMissing('payments', ['user_id' => $student->id, 'course_id' => $course->id]);
});

test('guests cannot request access to a course', function () {
    $course = Course::factory()->published()->create();

    $response = $this->post(route('payments.store', $course));

    $response->assertRedirect(route('login'));
});

test('an admin can confirm a pending payment, which enrolls the student', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();
    $course = Course::factory()->published()->create();
    $payment = Payment::factory()->for($student)->for($course)->create();

    $response = $this->actingAs($admin)->patch(route('admin.payments.update', $payment), [
        'status' => 'confirmed',
    ]);

    $response->assertRedirect();
    expect($payment->fresh()->status)->toBe(PaymentStatus::Confirmed);
    expect($payment->fresh()->confirmed_by)->toBe($admin->id);
    $this->assertDatabaseHas('enrollments', [
        'user_id' => $student->id,
        'course_id' => $course->id,
        'status' => EnrollmentStatus::Active->value,
    ]);
});

test('an admin can reject a pending payment without enrolling the student', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();
    $course = Course::factory()->published()->create();
    $payment = Payment::factory()->for($student)->for($course)->create();

    $response = $this->actingAs($admin)->patch(route('admin.payments.update', $payment), [
        'status' => 'rejected',
        'notes' => 'No proof of payment provided.',
    ]);

    $response->assertRedirect();
    expect($payment->fresh()->status)->toBe(PaymentStatus::Rejected);
    $this->assertDatabaseMissing('enrollments', ['user_id' => $student->id, 'course_id' => $course->id]);
});

test('confirming a payment reactivates a cancelled enrollment instead of duplicating it', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();
    $course = Course::factory()->published()->create();
    $student->enrollments()->create([
        'course_id' => $course->id,
        'status' => EnrollmentStatus::Cancelled,
        'enrolled_at' => now()->subMonth(),
    ]);
    $payment = Payment::factory()->for($student)->for($course)->create();

    $this->actingAs($admin)->patch(route('admin.payments.update', $payment), [
        'status' => 'confirmed',
    ]);

    expect(Enrollment::where('user_id', $student->id)->where('course_id', $course->id)->count())->toBe(1);
    $this->assertDatabaseHas('enrollments', [
        'user_id' => $student->id,
        'course_id' => $course->id,
        'status' => EnrollmentStatus::Active->value,
    ]);
});

test('an instructor cannot confirm payments', function () {
    $instructor = User::factory()->instructor()->create();
    $payment = Payment::factory()->create();

    $response = $this->actingAs($instructor)->patch(route('admin.payments.update', $payment), [
        'status' => 'confirmed',
    ]);

    $response->assertForbidden();
});

test('a payment that was already reviewed cannot be reviewed again', function () {
    $admin = User::factory()->admin()->create();
    $payment = Payment::factory()->confirmed($admin)->create();

    $response = $this->actingAs($admin)->patch(route('admin.payments.update', $payment), [
        'status' => 'rejected',
    ]);

    $response->assertSessionHasErrors('status');
    expect($payment->fresh()->status)->toBe(PaymentStatus::Confirmed);
});

test('the admin payments index only lists pending payments', function () {
    $admin = User::factory()->admin()->create();
    $pending = Payment::factory()->create();
    Payment::factory()->confirmed()->create();
    Payment::factory()->rejected()->create();

    $response = $this->actingAs($admin)->get(route('admin.payments.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/Payments')
        ->where('payments.data.0.id', $pending->id)
        ->where('payments.total', 1)
    );
});

test('a non-admin cannot view the admin payments index', function () {
    $student = User::factory()->create();

    $response = $this->actingAs($student)->get(route('admin.payments.index'));

    $response->assertForbidden();
});
