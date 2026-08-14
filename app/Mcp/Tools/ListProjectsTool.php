<?php

namespace App\Mcp\Tools;

use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('list-projects')]
#[Title('List your projects')]
#[Description("Read back the projects on the signed-in account's shelf, newest first. Each one comes with the id that delete-project needs.")]
#[IsReadOnly]
class ListProjectsTool extends Tool
{
    /**
     * Enough to answer "what have I posted" without spending the whole context on a shelf
     * nobody meant to read out loud. The count comes back either way, so the model can tell
     * it is looking at part of a longer list and ask for more.
     */
    private const DEFAULT_LIMIT = 25;

    private const MAX_LIMIT = 100;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:'.self::MAX_LIMIT],
        ], [
            'limit.integer' => 'Send limit as a whole number, or leave it out for '.self::DEFAULT_LIMIT.'.',
            'limit.min' => 'Send a limit of at least 1, or leave it out for '.self::DEFAULT_LIMIT.'.',
            'limit.max' => 'Send a limit of at most '.self::MAX_LIMIT.', or leave it out for '.self::DEFAULT_LIMIT.'.',
        ]);

        $projects = $request->user()->projects();

        $found = $projects->clone()
            ->latest()
            ->limit($validated['limit'] ?? self::DEFAULT_LIMIT)
            ->get();

        return Response::structured([
            'total' => $projects->count(),
            'showing' => $found->count(),
            'projects' => $found->map(fn (Project $project): array => [
                'id' => $project->id,
                'title' => $project->title,
                'link' => $project->url,
                'posted_at' => $project->created_at->toIso8601String(),
            ])->all(),
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'limit' => $schema->integer()
                ->description('How many to return, newest first. 1 to '.self::MAX_LIMIT.', and '.self::DEFAULT_LIMIT.' if left out.')
                ->min(1)
                ->max(self::MAX_LIMIT),
        ];
    }

    /**
     * Get the tool's output schema.
     *
     * @return array<string, Type>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'total' => $schema->integer()->description('How many projects are on the shelf altogether.'),
            'showing' => $schema->integer()->description('How many of them came back this time.'),
            'projects' => $schema->array()->description('Newest first.')->items(
                $schema->object([
                    'id' => $schema->integer()->description('What delete-project wants.'),
                    'title' => $schema->string(),
                    'link' => $schema->string(),
                    'posted_at' => $schema->string()->description('When it was posted, as an ISO 8601 timestamp.'),
                ])
            ),
        ];
    }
}
