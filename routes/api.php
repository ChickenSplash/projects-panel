<?php

use App\Mail\ContactMessageReceived;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

// Bearer tokens only -- there is no SPA here, so Sanctum's cookie/stateful side is unused.
Route::middleware('auth:sanctum')->post('/projects', function (Request $request) {
    $validated = $request->validate([
        'title' => ['required', 'string', 'max:255'],
        'link' => ['required', 'url:http,https', 'max:2048'],
    ]);

    // Same call the posting page makes, so a project arrives owned and identical either way.
    $project = $request->user()->projects()->create([
        'title' => $validated['title'],
        'url' => $validated['link'],
    ]);

    return response()->json([
        'id' => $project->id,
        'title' => $project->title,
        'link' => $project->url,
        'created_at' => $project->created_at,
    ], 201);
});

// The portfolio's contact form. Its nginx forwards /api/contact here over the edge network,
// so the form posts to its own origin and there is no CORS to set up.
Route::middleware('throttle:5,1')->post('/contact', function (Request $request) {
    // Bots fill every field they find; people never see this one. Pretend it worked.
    if (filled($request->input('website'))) {
        return response()->json(['ok' => true], 201);
    }

    $validated = $request->validate([
        'email' => ['required', 'email', 'max:255'],
        'subject' => ['required', 'string', 'max:150'],
        'message' => ['required', 'string', 'max:5000'],
    ]);

    $message = Message::create([
        'email' => $validated['email'],
        'subject' => $validated['subject'],
        'body' => $validated['message'],
        'ip' => $request->ip(),
        'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
    ]);

    // The message is already saved, so a mail hiccup is logged rather than shown to the sender.
    if ($admin = config('app.admin_email')) {
        try {
            Mail::to($admin)->send(new ContactMessageReceived($message));
        } catch (Throwable $e) {
            report($e);
        }
    }

    return response()->json(['ok' => true], 201);
});
