<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateContactSubmissionRequest;
use App\Models\ContactSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactSubmissionController extends Controller
{
    public function index(Request $request): Response
    {
        $filter = $request->string('filter')->toString();

        $submissions = ContactSubmission::query()
            ->when($filter === 'unread', fn ($query) => $query->unread())
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/contact-submissions/Index', [
            'submissions' => $submissions,
            'filter' => $filter,
            'unreadCount' => ContactSubmission::query()->unread()->count(),
        ]);
    }

    public function show(ContactSubmission $contactSubmission): Response
    {
        if (! $contactSubmission->isRead()) {
            $contactSubmission->forceFill(['read_at' => now()])->save();
        }

        return Inertia::render('admin/contact-submissions/Show', [
            'submission' => $contactSubmission,
        ]);
    }

    public function update(UpdateContactSubmissionRequest $request, ContactSubmission $contactSubmission): RedirectResponse
    {
        $contactSubmission->forceFill(['read_at' => $request->boolean('read') ? now() : null])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => $request->boolean('read') ? __('Marked as read.') : __('Marked as unread.')]);

        return back();
    }

    public function destroy(ContactSubmission $contactSubmission): RedirectResponse
    {
        $contactSubmission->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Message deleted.')]);

        return to_route('admin.contact-submissions.index');
    }
}
