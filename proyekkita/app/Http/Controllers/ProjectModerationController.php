<?php

namespace App\Http\Controllers;

use App\Enums\ModerationDecision;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Services\ProjectModerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectModerationController extends Controller
{
    public function index(Request $request): View
    {
        $statusFilter = $request->query('status', ProjectStatus::PendingReview->value);

        $counts = [
            'pending_review' => Project::where('status', ProjectStatus::PendingReview)->count(),
            'published' => Project::whereIn('status', [ProjectStatus::Published, ProjectStatus::Closed])->count(),
            'revision_requested' => Project::where('status', ProjectStatus::RevisionRequested)->count(),
            'rejected' => Project::where('status', ProjectStatus::Rejected)->count(),
        ];

        $projects = Project::with(['umkmProfile', 'category', 'skills', 'paymentTerms'])
            ->withCount('moderations')
            ->when($statusFilter === 'published', fn ($q) => $q->whereIn('status', [ProjectStatus::Published, ProjectStatus::Closed]))
            ->when($statusFilter !== 'published', fn ($q) => $q->where('status', $statusFilter))
            ->orderByDesc('id')
            ->get();

        $selectedProject = $request->filled('project')
            ? $projects->firstWhere('id', (int) $request->query('project'))
            : $projects->first();

        if ($selectedProject) {
            $selectedProject->load(['moderations.admin', 'umkmProfile', 'category', 'skills', 'paymentTerms']);
        }

        return view('admin.moderasi.index', compact('projects', 'selectedProject', 'counts', 'statusFilter'));
    }

    public function decide(Request $request, Project $project, ProjectModerationService $service): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', Rule::enum(ModerationDecision::class)],
            'note' => [
                Rule::requiredIf(fn () => in_array($request->input('decision'), [
                    ModerationDecision::RevisionRequested->value,
                    ModerationDecision::Rejected->value,
                ])),
                'nullable',
                'string',
            ],
        ], [
            'note.required' => 'Catatan wajib diisi untuk permintaan perbaikan dan penolakan.',
        ]);

        $service->decide(
            $project,
            $request->user(),
            ModerationDecision::from($validated['decision']),
            $validated['note'] ?? null
        );

        return redirect()->route('admin.moderasi.index')->with('success', 'Keputusan moderasi proyek berhasil disimpan.');
    }
}