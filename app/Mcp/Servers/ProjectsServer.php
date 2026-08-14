<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\CreateProjectTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Projects Panel')]
#[Version('1.0.0')]
#[Instructions(<<<'MARKDOWN'
    Projects Panel is a shelf of projects, each one a title and a link. This server posts to
    the shelf of whoever authorised the connection -- there is no way to reach anyone else's,
    and no way to list, edit or delete anything. Posting is the only thing on offer.
    MARKDOWN)]
class ProjectsServer extends Server
{
    protected array $tools = [
        CreateProjectTool::class,
    ];
}
