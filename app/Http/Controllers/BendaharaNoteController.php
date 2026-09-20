<?php

namespace App\Http\Controllers;

use App\Models\BendaharaNote;
use App\Models\BendaharaTask;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class BendaharaNoteController extends Controller
{
    /**
     * Display listing of notes for BOSP or BPOPP.
     */
    public function index(Request $request, string $tipe = 'bosp'): View
    {
        $tipe = in_array(strtolower($tipe), ['bosp', 'bpopp'], true) ? strtolower($tipe) : 'bosp';
        $tipeLabel = strtoupper($tipe);

        $query = BendaharaNote::with('user')->where('tipe', $tipe);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $filter = $request->input('filter', 'active');
        if ($filter === 'active') {
            $query->where('is_completed', false);
        } elseif ($filter === 'pinned') {
            $query->where('is_pinned', true);
        } elseif ($filter === 'completed') {
            $query->where('is_completed', true);
        }

        $notes = $query->orderByDesc('is_pinned')
            ->orderByDesc('updated_at')
            ->get();

        $pinnedNotes = $notes->where('is_pinned', true);
        $otherNotes = $notes->where('is_pinned', false);

        $counts = [
            'total' => BendaharaNote::where('tipe', $tipe)->count(),
            'pinned' => BendaharaNote::where('tipe', $tipe)->where('is_pinned', true)->count(),
            'active' => BendaharaNote::where('tipe', $tipe)->where('is_completed', false)->count(),
            'completed' => BendaharaNote::where('tipe', $tipe)->where('is_completed', true)->count(),
        ];

        $taskCounts = [
            'total' => BendaharaTask::where('tipe', $tipe)->count(),
            'active' => BendaharaTask::where('tipe', $tipe)->pending()->count(),
            'today' => BendaharaTask::where('tipe', $tipe)->pending()->dueToday()->count(),
            'overdue' => BendaharaTask::where('tipe', $tipe)->overdue()->count(),
            'upcoming' => BendaharaTask::where('tipe', $tipe)->upcoming()->count(),
            'completed' => BendaharaTask::where('tipe', $tipe)->completed()->count(),
        ];

        $taskFilter = $request->input('task_filter', 'active');
        $taskQuery = BendaharaTask::with('user')->where('tipe', $tipe);

        if ($taskFilter === 'active') {
            $taskQuery->pending();
        } elseif ($taskFilter === 'today') {
            $taskQuery->pending()->dueToday();
        } elseif ($taskFilter === 'overdue') {
            $taskQuery->overdue();
        } elseif ($taskFilter === 'upcoming') {
            $taskQuery->upcoming();
        } elseif ($taskFilter === 'completed') {
            $taskQuery->completed();
        }

        $tasks = $taskQuery
            ->orderBy('is_completed', 'asc')
            ->orderByRaw("CASE 
                WHEN priority = 'mendesak' THEN 1 
                WHEN priority = 'penting' THEN 2 
                ELSE 3 
            END ASC")
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END, due_date ASC')
            ->orderBy('due_time', 'asc')
            ->orderByDesc('id')
            ->get();

        return view('notes.index', compact(
            'notes',
            'pinnedNotes',
            'otherNotes',
            'tipe',
            'tipeLabel',
            'counts',
            'tasks',
            'taskCounts',
            'taskFilter'
        ));
    }

    /**
     * Store a newly created note.
     */
    public function store(Request $request, string $tipe = 'bosp'): RedirectResponse
    {
        $tipe = in_array(strtolower($tipe), ['bosp', 'bpopp'], true) ? strtolower($tipe) : 'bosp';

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'required|string',
            'color' => 'nullable|in:yellow,blue,green,red,purple,gray',
            'is_pinned' => 'nullable|boolean',
        ]);

        BendaharaNote::create([
            'user_id' => Auth::id(),
            'tipe' => $tipe,
            'title' => isset($validated['title']) ? trim((string) $validated['title']) : null,
            'content' => trim((string) $validated['content']),
            'color' => $validated['color'] ?? 'yellow',
            'is_pinned' => $request->boolean('is_pinned'),
            'is_completed' => false,
        ]);

        return Redirect::back()->with('success', 'Catatan berhasil ditambahkan.');
    }

    /**
     * Update the specified note.
     */
    public function update(Request $request, BendaharaNote $note): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'required|string',
            'color' => 'nullable|in:yellow,blue,green,red,purple,gray',
            'is_pinned' => 'nullable|boolean',
            'is_completed' => 'nullable|boolean',
        ]);

        $note->update([
            'title' => isset($validated['title']) ? trim((string) $validated['title']) : null,
            'content' => trim((string) $validated['content']),
            'color' => $validated['color'] ?? $note->color,
            'is_pinned' => $request->boolean('is_pinned'),
            'is_completed' => $request->has('is_completed') ? $request->boolean('is_completed') : $note->is_completed,
        ]);

        return Redirect::back()->with('success', 'Catatan berhasil diperbarui.');
    }

    /**
     * Toggle the pinned status of a note.
     */
    public function togglePin(BendaharaNote $note): RedirectResponse
    {
        $note->update([
            'is_pinned' => ! $note->is_pinned,
        ]);

        $status = $note->is_pinned ? 'disematkan di atas' : 'dilepas dari sematan';

        return Redirect::back()->with('success', "Catatan berhasil {$status}.");
    }

    /**
     * Toggle the completed status of a note.
     */
    public function toggleComplete(BendaharaNote $note): RedirectResponse
    {
        $note->update([
            'is_completed' => ! $note->is_completed,
        ]);

        $status = $note->is_completed ? 'ditandai selesai' : 'diaktifkan kembali';

        return Redirect::back()->with('success', "Catatan {$status}.");
    }

    /**
     * Remove the specified note.
     */
    public function destroy(BendaharaNote $note): RedirectResponse
    {
        $note->delete();

        return Redirect::back()->with('success', 'Catatan berhasil dihapus.');
    }
}
