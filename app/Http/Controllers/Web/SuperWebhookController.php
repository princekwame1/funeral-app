<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\WebhookEvent;
use Illuminate\Http\Request;

class SuperWebhookController extends Controller
{
    public function index(Request $request)
    {
        $provider = $request->query('provider');
        $query = WebhookEvent::query()->latest('received_at');

        if ($provider && in_array($provider, ['paystack', 'texttango'], true)) {
            $query->where('provider', $provider);
        }

        $events = $query->paginate(30)->withQueryString();

        $urls = [
            'paystack' => url('/api/paystack/webhook'),
            'texttango' => url('/api/texttango/webhook'),
        ];

        $counts = [
            'paystack' => WebhookEvent::where('provider', 'paystack')->count(),
            'texttango' => WebhookEvent::where('provider', 'texttango')->count(),
            'invalid_sig' => WebhookEvent::where('signature_ok', false)->count(),
            'last_24h' => WebhookEvent::where('received_at', '>=', now()->subDay())->count(),
        ];

        return view('super.webhooks.index', compact('events', 'urls', 'counts', 'provider'));
    }
}
