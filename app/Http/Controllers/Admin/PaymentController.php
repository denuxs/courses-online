<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EnrollmentStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\UpdatePaymentRequest;
use App\Models\Enrollment;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    /**
     * Display the pending payment requests.
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', Payment::class);

        $payments = Payment::query()
            ->pending()
            ->with(['user:id,name,email', 'course:id,title,slug'])
            ->latest()
            ->paginate(15);

        return Inertia::render('admin/Payments', [
            'payments' => $payments,
        ]);
    }

    /**
     * Confirm or reject the given payment.
     */
    public function update(UpdatePaymentRequest $request, Payment $payment): RedirectResponse
    {
        if ($payment->status !== PaymentStatus::Pending) {
            throw ValidationException::withMessages([
                'status' => __('This payment has already been reviewed.'),
            ]);
        }

        $status = PaymentStatus::from($request->validated('status'));

        DB::transaction(function () use ($request, $payment, $status): void {
            $payment->update([
                'status' => $status,
                'confirmed_by' => $request->user()->id,
                'confirmed_at' => now(),
                'notes' => $request->validated('notes'),
            ]);

            if ($status->isConfirmed()) {
                Enrollment::updateOrCreate(
                    ['user_id' => $payment->user_id, 'course_id' => $payment->course_id],
                    ['status' => EnrollmentStatus::Active, 'enrolled_at' => now(), 'completed_at' => null]
                );
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Payment :status.', ['status' => $status->value])]);

        return to_route('admin.payments.index');
    }
}
