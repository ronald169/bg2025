<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\Request;
use Stripe\Webhook;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (SignatureVerificationException $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;
                $this->handleCheckoutSessionCompleted($session);
                break;

            case 'invoice.payment_succeeded':
                $invoice = $event->data->object;
                $this->handleInvoicePaymentSucceeded($invoice);
                break;

            case 'customer.subscription.deleted':
                $subscription = $event->data->object;
                $this->handleSubscriptionDeleted($subscription);
                break;
        }

        return response()->json(['status' => 'success']);
    }

    private function handleCheckoutSessionCompleted($session)
    {
        $userId = $session->metadata->user_id ?? null;
        $courseId = $session->metadata->course_id ?? null;

        if ($userId && $courseId) {
            $user = User::find($userId);
            $course = Course::find($courseId);

            if ($user && $course && !$user->coursesEnrolled()->where('course_id', $courseId)->exists()) {
                $user->coursesEnrolled()->attach($courseId, [
                    'enrolled_at' => now(),
                    'status' => 'active',
                    'paid_amount' => $session->amount_total / 100,
                    'payment_intent_id' => $session->payment_intent,
                    'paid_at' => now(),
                ]);
            }
        }
    }

    private function handleInvoicePaymentSucceeded($invoice)
    {
        // Gérer les paiements d'abonnement récurrents
        $customerId = $invoice->customer;
        $user = User::where('stripe_id', $customerId)->first();

        if ($user) {
            // Logique pour les abonnements
        }
    }

    private function handleSubscriptionDeleted($subscription)
    {
        $customerId = $subscription->customer;
        $user = User::where('stripe_id', $customerId)->first();

        if ($user) {
            $user->subscription('default')->cancel();
        }
    }
}
