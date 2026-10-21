<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\AddCommentTool;
use App\Mcp\Tools\AttachFileTool;
use App\Mcp\Tools\CreateTaskTool;
use App\Mcp\Tools\GetMyWorkTool;
use App\Mcp\Tools\GetProjectTool;
use App\Mcp\Tools\GetTaskTool;
use App\Mcp\Tools\ListProjectsTool;
use App\Mcp\Tools\ReadAttachmentTool;
use App\Mcp\Tools\RequestReviewTool;
use App\Mcp\Tools\ReviewTaskTool;
use App\Mcp\Tools\SearchTasksTool;
use App\Mcp\Tools\SetTaskStatusTool;
use App\Mcp\Tools\UpdateTaskTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Relay')]
#[Version('1.0.0')]
#[Instructions(<<<'TXT'
Relay is the user's task manager. You act as the signed-in user; every change is recorded in the task's activity log as made by you through this connection.

Workflow:
1. Call get_my_work first to see what is assigned to the user and what awaits their review.
2. Use get_task (by key, e.g. "WEB-12") for full context before working on something. If the task or its project (get_project) lists files, read the relevant ones with read_attachment — specs, briefs and screenshots often hold the requirements.
3. When you start, set_task_status to in_progress. Post progress or questions with add_comment. Save longer outputs (reports, plans, data exports) with attach_file instead of pasting them into comments.
4. When the work is ready for a human, call request_review with a clear summary. Do not mark tasks done yourself unless the user asks.
5. Only call review_task to approve after the user explicitly confirms.

Statuses: backlog, todo, in_progress, in_review (needs review), done. Priorities: low, medium, high, urgent.
TXT)]
class RelayServer extends Server
{
    protected array $tools = [
        GetMyWorkTool::class,
        ListProjectsTool::class,
        GetProjectTool::class,
        SearchTasksTool::class,
        GetTaskTool::class,
        CreateTaskTool::class,
        UpdateTaskTool::class,
        SetTaskStatusTool::class,
        RequestReviewTool::class,
        ReviewTaskTool::class,
        AddCommentTool::class,
        ReadAttachmentTool::class,
        AttachFileTool::class,
    ];
}
