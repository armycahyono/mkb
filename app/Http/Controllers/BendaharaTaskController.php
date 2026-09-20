<?php

namespace App\Http\Controllers;

use App\Models\BendaharaTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class BendaharaTaskController extends Controller
{
    /**
     * Store a newly created task.
     */
    public function store(Request $request, string $tipe = 'bosp'): JsonResponse|RedirectResponse
    {
        $tipe = in_array(strtolower($tipe), ['bosp', 'bpopp'], true) ? strtolower($tipe) : 'bosp';

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'due_time' => 'nullable|string|max:10',
            'priority' => 'nullable|in:normal,penting,mendesak',
            'kegiatan_ref' => 'nullable|string|max:255',
        ]);

        $task = BendaharaTask::create([
            'user_id' => Auth::id(),
            'tipe' => $tipe,
            'title' => trim((string) $validated['title']),
            'description' => isset($validated['description']) ? trim((string) $validated['description']) : null,
            'due_date' => ! empty($validated['due_date']) ? $validated['due_date'] : null,
            'due_time' => ! empty($validated['due_time']) ? $validated['due_time'] : null,
            'priority' => $validated['priority'] ?? BendaharaTask::PRIORITY_NORMAL,
            'kegiatan_ref' => isset($validated['kegiatan_ref']) ? trim((string) $validated['kegiatan_ref']) : null,
            'is_completed' => false,
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Agenda tugas berhasil ditambahkan.',
                'task' => $task,
            ]);
        }

        return Redirect::back()->with('success', 'Agenda tugas berhasil ditambahkan.');
    }

    /**
     * Update the specified task.
     */
    public function update(Request $request, BendaharaTask $task): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'due_time' => 'nullable|string|max:10',
            'priority' => 'nullable|in:normal,penting,mendesak',
            'kegiatan_ref' => 'nullable|string|max:255',
            'is_completed' => 'nullable|boolean',
        ]);

        $isCompleted = $request->has('is_completed') ? $request->boolean('is_completed') : $task->is_completed;
        $completedAt = $task->completed_at;
        if ($isCompleted && ! $task->is_completed) {
            $completedAt = now();
        } elseif (! $isCompleted && $task->is_completed) {
            $completedAt = null;
        }

        $task->update([
            'title' => trim((string) $validated['title']),
            'description' => isset($validated['description']) ? trim((string) $validated['description']) : null,
            'due_date' => ! empty($validated['due_date']) ? $validated['due_date'] : null,
            'due_time' => ! empty($validated['due_time']) ? $validated['due_time'] : null,
            'priority' => $validated['priority'] ?? $task->priority,
            'kegiatan_ref' => isset($validated['kegiatan_ref']) ? trim((string) $validated['kegiatan_ref']) : null,
            'is_completed' => $isCompleted,
            'completed_at' => $completedAt,
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Agenda tugas berhasil diperbarui.',
                'task' => $task,
            ]);
        }

        return Redirect::back()->with('success', 'Agenda tugas berhasil diperbarui.');
    }

    /**
     * Toggle completion status of task.
     */
    public function toggleComplete(Request $request, BendaharaTask $task): JsonResponse|RedirectResponse
    {
        $nowCompleted = ! $task->is_completed;
        $task->update([
            'is_completed' => $nowCompleted,
            'completed_at' => $nowCompleted ? now() : null,
        ]);

        $statusMsg = $task->is_completed ? 'Tugas ditandai selesai.' : 'Tugas diaktifkan kembali.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_completed' => $task->is_completed,
                'due_status' => $task->due_status,
                'due_label' => $task->due_label,
                'due_badge_class' => $task->due_badge_class,
                'message' => $statusMsg,
            ]);
        }

        return Redirect::back()->with('success', $statusMsg);
    }

    /**
     * Remove the specified task.
     */
    public function destroy(Request $request, BendaharaTask $task): JsonResponse|RedirectResponse
    {
        $task->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Agenda tugas berhasil dihapus.',
            ]);
        }

        return Redirect::back()->with('success', 'Agenda tugas berhasil dihapus.');
    }
}
