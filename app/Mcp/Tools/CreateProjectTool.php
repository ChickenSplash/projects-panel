<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;

// Left to itself the class name would become `create-project-tool`, which reads as though
// the tool creates a tool. The name is what the model sees, so it is spelled out here.
#[Name('create-project')]
#[Title('Post a project')]
#[Description('Post a project -- a title and a link -- to the signed-in account.')]
class CreateProjectTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        // Same rules as POST /api/projects, so a project posted by a model and one posted
        // by curl are held to the same standard. The messages are read by the model rather
        // than a person, so they say what to send next instead of what went wrong.
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'link' => ['required', 'url:http,https', 'max:2048'],
        ], [
            'title.required' => 'Send a title: a short name for the project.',
            'title.max' => 'Titles stop at 255 characters. Send a shorter one.',
            'link.url' => 'Send a full URL including the scheme, like https://example.com.',
            'link.max' => 'Links stop at 2048 characters. Send a shorter one.',
        ]);

        // The column is `url`; the argument is `link`, matching the form label and the API.
        $project = $request->user()->projects()->create([
            'title' => $validated['title'],
            'url' => $validated['link'],
        ]);

        return Response::text("Posted \"{$project->title}\" ({$project->url}). It is on the account's shelf now.");
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()
                ->description('A short name for the project.')
                ->required(),
            'link' => $schema->string()
                ->description('A full URL to the project, including https://')
                ->required(),
        ];
    }
}
