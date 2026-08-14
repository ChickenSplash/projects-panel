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
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Name('delete-project')]
#[Title('Delete a project')]
#[Description("Take one project off the signed-in account's shelf for good. Ids come from list-projects.")]
// The hint clients read to decide whether to ask the person first. There is no undo behind
// this and no soft delete on the table, so it is as destructive as the flag can mean.
#[IsDestructive]
class DeleteProjectTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => ['required', 'integer'],
        ], [
            'id.required' => 'Send the id of the project to delete. list-projects has them.',
            'id.integer' => 'Send the id as a whole number, the way list-projects gives it.',
        ]);

        // Scoped to this account's projects, exactly as the delete button on the page is, so
        // there is nothing else to authorise. Somebody else's id simply does not match, and
        // the answer to it is the same as the answer to an id that never existed -- knowing
        // which of the two it was is not something a connector should be able to find out.
        $project = $request->user()->projects()->whereKey($validated['id'])->first();

        if ($project === null) {
            return Response::error(
                "No project with id {$validated['id']} on this account's shelf. Call list-projects to see what is there."
            );
        }

        $project->delete();

        return Response::text("Deleted \"{$project->title}\". That one is gone for good.");
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('The id of the project to delete, as list-projects gives it.')
                ->required(),
        ];
    }
}
