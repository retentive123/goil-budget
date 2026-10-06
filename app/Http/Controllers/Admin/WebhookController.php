<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Webhook;
use App\Services\WebhookService;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    const EVENTS = [
        'budget_submitted'   => 'Budget Submitted',
        'budget_stage_reached' => 'Budget Stage Reached',
        'budget_approved'    => 'Budget Approved',
        'budget_rejected'    => 'Budget Rejected',
        'virement_submitted' => 'Virement Submitted',
        'virement_approved'  => 'Virement Approved',
        'virement_rejected'  => 'Virement Rejected',
        'actuals_confirmed'  => 'Actuals Confirmed',
    ];

    public function index()
    {
        $webhooks = Webhook::orderByDesc('created_at')->get();
        $events   = self::EVENTS;
        return view('admin.webhooks.index', compact('webhooks', 'events'));
    }

    public function create()
    {
        return view('admin.webhooks.create', ['events' => self::EVENTS]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:100'],
            'url'       => ['required', 'url', 'max:500'],
            'events'    => ['required', 'array', 'min:1'],
            'events.*'  => ['string', 'in:' . implode(',', array_keys(self::EVENTS))],
            'secret'    => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Webhook::create([
            'name'      => $validated['name'],
            'url'       => $validated['url'],
            'events'    => $validated['events'],
            'secret'    => $validated['secret'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return redirect()->route('admin.webhooks.index')
            ->with('success', 'Webhook "' . $validated['name'] . '" created.');
    }

    public function edit(Webhook $webhook)
    {
        return view('admin.webhooks.edit', ['webhook' => $webhook, 'events' => self::EVENTS]);
    }

    public function update(Request $request, Webhook $webhook)
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:100'],
            'url'       => ['required', 'url', 'max:500'],
            'events'    => ['required', 'array', 'min:1'],
            'events.*'  => ['string', 'in:' . implode(',', array_keys(self::EVENTS))],
            'secret'    => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $webhook->update([
            'name'      => $validated['name'],
            'url'       => $validated['url'],
            'events'    => $validated['events'],
            'secret'    => $validated['secret'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return redirect()->route('admin.webhooks.index')
            ->with('success', 'Webhook "' . $webhook->name . '" updated.');
    }

    public function destroy(Webhook $webhook)
    {
        $name = $webhook->name;
        $webhook->delete();
        return redirect()->route('admin.webhooks.index')
            ->with('success', 'Webhook "' . $name . '" deleted.');
    }

    public function test(Webhook $webhook)
    {
        (new WebhookService())->fire('budget_submitted', [
            'test'    => true,
            'message' => 'This is a test payload from the GOIL Budget System.',
        ]);

        return back()->with('success', 'Test payload dispatched to "' . $webhook->name . '".');
    }
}
