<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class SubscriptionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    public function generateSubscriptionLink(Request $request)
    {
        $user = Auth::user();
        $uri = 'https://api.flutterwave.com/v3/payments';
        $details = [
            'tx_ref' => '',
            'amount' => $request->amount,
            'currency' => 'NGN',
            'redirect_url' => '',
            'customer' => [
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'name' => $user->name
            ],
            'customizations' => [
                'subscription_type' => $request->subscription_type,
            ]
        ];

        $response = Http::withToken(
            env('FLW_SECRET_KEY')
        )->post($uri, $details);

        $this->create($details['customizations']['subscription_type'], $details['tx_ref'], $user->id);

        return response()->json($response, 200);
    }

    private function create($subscription_type, $tx_ref, $id)
    {
        Subscription::create([
            'user_id' => $id,
            'subscription_type' => $subscription_type,
            'transaction_id' => null,
            'tx_ref' => $tx_ref,
            'status' => 'not_completed',

        ]);
    }

    public function verifySubscription(Request $request)
    {
        $uri = 'https://api.flutterwave.com/v3/transactions/verify_by_reference?tx_ref=' . $request->tx_ref;
        $response = Http::withToken(env('FLW_SECRET_KEY'))->get($uri);
        $subscription = Subscription::where('tx_ref', $request->tx_ref)->get();
        $subscription->transaction_id = $request->transaction_id;

        if (
            $response['data']['status'] === 'successful'
            && $response['data']['amount'] === $request->amount
            && $response['data']['currency'] === 'NGN'
        ) {
            $subscription->status = 'success';
            $subscription->save();

            return response()->json([
                'message' => 'Your Subscription was Successful'
            ], 200);
        } else {
            $subscription->status = 'failed';
            $subscription->save();

            return response()->json([
                'error' => 'Your Subscription Failed'
            ], 400);
        }
    }

    public static function showSubscriptions()
    {
        $user = Auth::user();
        $subscriptions = User::find($user->id)->subscriptions;

        if ($subscriptions) {
            return response()->json([
                'subscriptions' => $subscriptions
            ], 200);
        } else {
            return response()->json([
                'subscriptions' => []
            ], 204);
        }
    }

    public static function showSubscription(Request $request)
    {
        $subscription = Subscription::where('tx_ref', $request->tx_ref)->get();

        if (!$subscription) {
            return response()->json([
                'subscription' => []
            ], 204);
        } else {
            return response()->json([
                'subscription' => $subscription
            ], 200);
        }
    }

    public static function cancelSubscription()
    {
    }
}