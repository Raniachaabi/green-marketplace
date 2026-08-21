<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Same reasoning as CredentialDocumentController — private disk, admin-only, streamed. */
class PaymentProofController extends Controller
{
    public function show(Request $request, Payment $payment): StreamedResponse
    {
        abort_unless($request->user()?->is_admin, 403);
        abort_unless($payment->proof_path, 404);
        abort_unless(Storage::disk('payment_proofs')->exists($payment->proof_path), 404);

        return Storage::disk('payment_proofs')->response($payment->proof_path);
    }
}
