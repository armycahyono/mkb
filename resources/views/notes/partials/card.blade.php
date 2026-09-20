<div class="note-card note-{{ $note->color ?? 'yellow' }} {{ $note->is_completed ? 'note-completed' : '' }} shadow-sm">
    <div class="note-header">
        <h6 class="note-title">
            {{ $note->title ?: Str::limit($note->content, 35) }}
        </h6>
        <div class="d-flex align-items-center">
            <!-- Toggle Pin Form -->
            <form action="{{ route('notes.toggle-pin', $note) }}" method="POST" class="d-inline">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-xs btn-link p-0 {{ $note->is_pinned ? 'btn-pin-active' : 'text-muted' }}" 
                        title="{{ $note->is_pinned ? 'Lepas Sematan' : 'Sematkan di atas' }}" 
                        style="font-size: 0.95rem;">
                    <i class="fas fa-thumbtack"></i>
                </button>
            </form>
        </div>
    </div>

    <div class="note-body text-left">{{ trim($note->content) }}</div>

    <div class="note-footer">
        <div>
            <span class="d-block" style="font-size: 0.72rem; line-height: 1.2;">
                {{ $note->updated_at->format('d M Y, H:i') }}
            </span>
            @if($note->user)
                <span class="text-muted" style="font-size: 0.68rem;">
                    <i class="fas fa-user-circle mr-1"></i>{{ Str::limit($note->user->name, 15) }}
                </span>
            @endif
        </div>

        <div class="d-flex align-items-center" style="gap: 6px;">
            <!-- Toggle Complete Form -->
            <form action="{{ route('notes.toggle-complete', $note) }}" method="POST" class="d-inline">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-xs {{ $note->is_completed ? 'btn-success' : 'btn-outline-secondary' }}" 
                        title="{{ $note->is_completed ? 'Tandai Belum Selesai' : 'Tandai Selesai' }}" 
                        style="border-radius: 6px; padding: 2px 6px;">
                    <i class="fas fa-check"></i>
                </button>
            </form>

            <!-- Edit Button (Modal) -->
            <button type="button" class="btn btn-xs btn-outline-primary" 
                    title="Ubah Catatan" 
                    onclick='openEditModal(@json($note))' 
                    style="border-radius: 6px; padding: 2px 6px;">
                <i class="fas fa-pencil-alt"></i>
            </button>

            <!-- Delete Form -->
            <form action="{{ route('notes.destroy', $note) }}" method="POST" class="d-inline" 
                  onsubmit="return confirm('Apakah Anda yakin ingin menghapus catatan ini?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-xs btn-outline-danger" 
                        title="Hapus Catatan" 
                        style="border-radius: 6px; padding: 2px 6px;">
                    <i class="fas fa-trash"></i>
                </button>
            </form>
        </div>
    </div>
</div>
