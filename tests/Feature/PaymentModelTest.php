<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Course;
use App\Models\Payment;
use App\Models\User;

test('payment attributes are cast to their expected types', function () {
    $payment = Payment::factory()->create();

    expect($payment->amount)->toBeString();
    expect($payment->method)->toBeInstanceOf(PaymentMethod::class);
    expect($payment->status)->toBeInstanceOf(PaymentStatus::class);
    expect($payment->status)->toBe(PaymentStatus::Pending);
});

test('the pending scope only returns pending payments', function () {
    $pending = Payment::factory()->create();
    Payment::factory()->confirmed()->create();
    Payment::factory()->rejected()->create();

    $payments = Payment::pending()->get();

    expect($payments)->toHaveCount(1);
    expect($payments->first()->id)->toBe($pending->id);
});

test('the confirmed scope only returns confirmed payments', function () {
    $confirmed = Payment::factory()->confirmed()->create();
    Payment::factory()->create();
    Payment::factory()->rejected()->create();

    $payments = Payment::confirmed()->get();

    expect($payments)->toHaveCount(1);
    expect($payments->first()->id)->toBe($confirmed->id);
});

test('isConfirmed reflects the payment status', function () {
    expect(Payment::factory()->confirmed()->make()->isConfirmed())->toBeTrue();
    expect(Payment::factory()->make()->isConfirmed())->toBeFalse();
    expect(Payment::factory()->rejected()->make()->isConfirmed())->toBeFalse();
});

test('confirmer returns the admin who reviewed the payment', function () {
    $admin = User::factory()->admin()->create();
    $payment = Payment::factory()->confirmed($admin)->create();

    expect($payment->confirmer->id)->toBe($admin->id);
});

test('deleting the confirming admin nulls confirmed_by instead of deleting the payment', function () {
    $admin = User::factory()->admin()->create();
    $payment = Payment::factory()->confirmed($admin)->create();

    $admin->delete();

    expect(Payment::find($payment->id))->not->toBeNull();
    expect($payment->fresh()->confirmed_by)->toBeNull();
});

test('deleting a course cascades to its payments', function () {
    $course = Course::factory()->create();
    $payment = Payment::factory()->for($course)->create();

    $course->delete();

    expect(Payment::find($payment->id))->toBeNull();
});

test('the confirmed factory state inherits the amount from the course price', function () {
    $course = Course::factory()->create(['price' => '49.99']);
    $payment = Payment::factory()->for($course)->confirmed()->create();

    expect($payment->amount)->toBe('49.99');
});
