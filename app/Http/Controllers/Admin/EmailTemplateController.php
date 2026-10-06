<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    public function index()
    {
        $templates = EmailTemplate::orderBy('label')->get();
        return view('admin.email-templates.index', compact('templates'));
    }

    public function edit(EmailTemplate $emailTemplate)
    {
        return view('admin.email-templates.edit', ['template' => $emailTemplate]);
    }

    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        $validated = $request->validate([
            'subject'   => ['required', 'string', 'max:255'],
            'body'      => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $emailTemplate->update([
            'subject'   => $validated['subject'],
            'body'      => $validated['body'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return redirect()->route('admin.email-templates.index')
            ->with('success', 'Template "' . $emailTemplate->label . '" updated.');
    }

    public function reset(EmailTemplate $emailTemplate)
    {
        // Re-seed defaults from the seeder defaults array
        (new \Database\Seeders\EmailTemplateSeeder())->run();
        return back()->with('success', 'All templates reset to system defaults.');
    }
}
