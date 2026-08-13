<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
