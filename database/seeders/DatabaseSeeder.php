<?php

namespace Database\Seeders;

use App\Application\Projects\AddProjectMemberUseCase;
use App\Application\Projects\CreateProjectUseCase;
use App\Application\Projects\Dto\CreateProjectInput;
use App\Application\Tasks\AddCommentUseCase;
use App\Application\Tasks\ChangeTaskStatusUseCase;
use App\Application\Tasks\CreateTaskUseCase;
use App\Application\Tasks\Dto\CreateTaskInput;
use App\Application\Tasks\RequestReviewUseCase;
use App\Domain\Projects\ProjectRole;
use App\Domain\Shared\Actor;
use App\Domain\Tasks\TaskStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Demo workspace. Sign in as demo@relay.test / password1234. Runs through the real use cases. */
class DatabaseSeeder extends Seeder
{
    public function run(
        CreateProjectUseCase $createProject,
        AddProjectMemberUseCase $addMember,
        CreateTaskUseCase $createTask,
        ChangeTaskStatusUseCase $changeStatus,
        RequestReviewUseCase $requestReview,
        AddCommentUseCase $comment,
    ): void {
        $ada = User::factory()->create(['name' => 'Ada Lovelace', 'email' => 'demo@relay.test', 'password' => 'password1234']);
        $sam = User::factory()->create(['name' => 'Sam Rivera', 'email' => 'sam@relay.test', 'password' => 'password1234']);

        $me = Actor::web($ada->id);
        $claude = Actor::agent($ada->id, 'Claude');
        $samActor = Actor::web($sam->id);

        $web = $createProject->execute($me, new CreateProjectInput('Website Relaunch', 'WEB', 'New marketing site and pricing page, launching end of month.'));
        $ops = $createProject->execute($me, new CreateProjectInput('Platform Ops', 'OPS', 'Reliability, on-call and infrastructure work.'));
        $addMember->execute($me, $web->id, $sam->email, ProjectRole::Admin);
        $addMember->execute($me, $ops->id, $sam->email, ProjectRole::Member);

        $task = fn (string $project, string $title, array $extra = []) => $createTask->execute($me, CreateTaskInput::fromArray([
            'project' => $project, 'title' => $title, ...$extra,
        ]));
        $due = fn (int $days) => now()->addDays($days)->toDateString();

        $pricing = $task('WEB', 'Ship the new pricing page', [
            'priority' => 'high', 'assignee_id' => $ada->id, 'reviewer_id' => $sam->id, 'labels' => ['frontend', 'launch'], 'due_date' => $due(1),
            'description' => "Three tiers, annual toggle, FAQ.\n\n**Acceptance**\n- [x] Matches Figma at 375 / 768 / 1440\n- [ ] Lighthouse ≥ 95\n- [ ] Copy approved by marketing",
        ]);
        $hero = $task('WEB', 'Hero animation respects reduced motion', ['priority' => 'medium', 'assignee_id' => $ada->id, 'reviewer_id' => $sam->id, 'labels' => ['a11y']]);
        $task('WEB', 'Write launch announcement', ['priority' => 'medium', 'assignee_id' => $sam->id, 'due_date' => $due(4), 'labels' => ['content']]);
        $task('WEB', 'Set up redirects from old URLs', ['priority' => 'urgent', 'assignee_id' => $ada->id, 'due_date' => $due(-1), 'labels' => ['seo']]);
        $task('WEB', 'Compress hero video under 2 MB', ['priority' => 'low', 'status' => 'backlog']);
        $task('WEB', 'Cookie banner', ['priority' => 'low', 'status' => 'backlog', 'labels' => ['legal']]);
        $done = $task('WEB', 'Choose typography for the new brand', ['priority' => 'medium', 'assignee_id' => $sam->id]);

        $rate = $createTask->execute($samActor, CreateTaskInput::fromArray([
            'project' => 'OPS', 'title' => 'Add rate limiting to the public API', 'priority' => 'high', 'assignee_id' => $sam->id, 'reviewer_id' => $ada->id, 'labels' => ['backend', 'security'],
            'description' => 'Token bucket per API key. 120 req/min default, configurable per plan.',
        ]));
        $task('OPS', 'Rotate database credentials', ['priority' => 'urgent', 'assignee_id' => $ada->id, 'due_date' => $due(2), 'labels' => ['security']]);
        $task('OPS', 'Alert when queue depth > 1k for 5 min', ['priority' => 'medium', 'assignee_id' => $sam->id]);
        $task('OPS', 'Document the on-call runbook', ['priority' => 'low', 'status' => 'backlog', 'labels' => ['docs']]);

        // What a connected assistant leaves behind.
        $changeStatus->execute($claude, $pricing->key, TaskStatus::InProgress);
        $comment->execute($claude, $pricing->key, "Plan:\n1. Build the tier cards with the annual toggle\n2. Wire FAQ accordion\n3. Run Lighthouse and fix regressions\n\nStarting with (1).");
        $changeStatus->execute($claude, $hero->key, TaskStatus::InProgress);
        $requestReview->execute($claude, $hero->key, "Swapped the GSAP timeline for a CSS fallback when `prefers-reduced-motion` is set.\n\nVerified in Chrome + Safari with the OS setting on. Please check the poster frame on mobile.");

        $changeStatus->execute($samActor, $done->key, TaskStatus::Done);
        $changeStatus->execute($samActor, $rate->key, TaskStatus::InProgress);
        $requestReview->execute($samActor, $rate->key, 'Middleware + tests are in. Limits live in config/limits.php — sanity-check the defaults.');
        $comment->execute($samActor, $pricing->key, 'Marketing signed off on the copy 🎉');
    }
}
