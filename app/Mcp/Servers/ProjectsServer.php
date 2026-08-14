<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\CreateProjectTool;
use App\Mcp\Tools\DeleteProjectTool;
use App\Mcp\Tools\ListProjectsTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Projects Panel')]
#[Version('1.1.0')]
#[Instructions(<<<'MARKDOWN'
    Projects Panel is a shelf of projects, each one a title and a link. Everything here happens
    on the shelf of whoever authorised the connection: there is no way to reach anyone else's,
    and no way to change a project once it is posted -- deleting and posting again is the only
    way to correct one.

    Posting takes a title and a link. Deleting takes an id, and ids come from listing, so read
    the shelf before removing anything rather than guessing at a number.
    MARKDOWN)]
class ProjectsServer extends Server
{
    protected array $tools = [
        CreateProjectTool::class,
        ListProjectsTool::class,
        DeleteProjectTool::class,
    ];
}
