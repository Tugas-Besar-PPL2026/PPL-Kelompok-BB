<?php

namespace App\Services;

use App\Enums\ModerationDecision;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProjectModerationService
{
    public function decide(Project $project, User $admin, ModerationDecision $decision, ?string $note): void
    {
        DB::transaction(function () use ($project, $admin, $decision, $note) {
            $project->moderations()->create([
                'admin_id' => $admin->id,
                'decision' => $decision,
                'note' => $note,
            ]);

            $status = match ($decision) {
                ModerationDecision::Approved => ProjectStatus::Published,
                ModerationDecision::RevisionRequested => ProjectStatus::RevisionRequested,
                ModerationDecision::Rejected => ProjectStatus::Rejected,
            };

            $project->update([
                'status' => $status,
                'published_at' => $decision === ModerationDecision::Approved ? now() : $project->published_at,
            ]);
        });
    }
}