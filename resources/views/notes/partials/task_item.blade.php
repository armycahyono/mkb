<div class="task-item d-flex align-items-start {{ $task->is_completed ? 'completed' : '' }}" id="task-item-{{ $task->id }}">
    <div class="task-priority-indicator priority-{{ $task->priority }}"></div>
    <div class="pt-1 mr-2">
        <input type="checkbox" class="task-checkbox" 
               id="task_chk_{{ $task->id }}" 
               {{ $task->is_completed ? 'checked' : '' }} 
               onchange="toggleTaskComplete({{ $task->id }}, this)">
    </div>
    <div class="flex-grow-1" style="min-width: 0;">
        <div class="d-flex align-items-start justify-content-between">
            <label for="task_chk_{{ $task->id }}" class="task-text font-weight-bold mb-1 mr-2" style="cursor: pointer; word-break: break-word; line-height: 1.35; font-size: 0.9rem;">
                {{ $task->title }}
            </label>
            <div class="task-actions d-flex align-items-center ml-2" style="gap: 4px;">
                <button type="button" class="btn btn-xs btn-outline-primary" 
                        title="Ubah Agenda" 
                        onclick='openEditTaskModal(@json($task))' 
                        style="border-radius: 6px; padding: 2px 6px;">
                    <i class="fas fa-pencil-alt"></i>
                </button>
                <form action="{{ route('notes.tasks.destroy', $task) }}" method="POST" class="d-inline form-delete-task" 
                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus agenda tugas ini?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-xs btn-outline-danger" 
                            title="Hapus Agenda" 
                            style="border-radius: 6px; padding: 2px 6px;">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            </div>
        </div>

        <div class="d-flex align-items-center flex-wrap mt-1" style="gap: 5px;">
            @if($task->due_date)
                <span class="badge {{ $task->due_badge_class }} px-2 py-1" id="task-due-badge-{{ $task->id }}" style="font-size: 0.72rem;">
                    <i class="far fa-clock mr-1"></i>{{ $task->due_label }}
                </span>
            @endif

            <span class="badge {{ $task->priority_badge_class }} px-2 py-1" style="font-size: 0.72rem;">
                {{ $task->priority_label }}
            </span>

            @if($task->kegiatan_ref)
                <span class="badge badge-light border text-secondary px-2 py-1 text-truncate" style="max-width: 180px; font-size: 0.72rem;" title="{{ $task->kegiatan_ref }}">
                    <i class="fas fa-link text-primary mr-1"></i>{{ $task->kegiatan_ref }}
                </span>
            @endif
        </div>

        @if($task->description)
            <div class="task-desc small text-muted mt-2 pt-1 border-top" style="font-size: 0.78rem; line-height: 1.4; white-space: pre-line;">
                {{ $task->description }}
            </div>
        @endif
    </div>
</div>
