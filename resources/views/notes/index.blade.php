@extends('adminlte::page')

@section('title', 'Catatan & Pengingat ' . $tipeLabel)

@section('content_header')
<div class="d-flex justify-content-between align-items-center flex-wrap" style="padding: 2px 0;">
    <div>
        <h1 class="page-title">
            <i class="fas fa-clipboard-list text-warning mr-1"></i> Catatan & Pengingat {{ $tipeLabel }}
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="fas fa-house"></i> Dashboard</a></li>
                <li class="breadcrumb-item">{{ $tipeLabel }}</li>
                <li class="breadcrumb-item active">Catatan & Pengingat</li>
            </ol>
        </nav>
    </div>
    <div class="header-actions d-flex align-items-center flex-wrap" style="gap: 8px;">
        <button type="button" class="btn btn-sm btn-outline-primary bg-white shadow-sm font-weight-bold" onclick="showModal('#modalCreateNote')">
            <i class="fas fa-sticky-note text-warning mr-1"></i> Tambah Catatan
        </button>
        <button type="button" class="btn btn-sm btn-primary shadow-sm font-weight-bold" onclick="showModal('#modalCreateTask')">
            <i class="fas fa-calendar-plus mr-1"></i> Tambah Pengingat
        </button>
        <span class="badge {{ $tipe === 'bosp' ? 'badge-success' : 'badge-warning text-dark' }} px-3 py-2 font-weight-bold" style="font-size: 0.85rem;">
            <i class="fas fa-wallet mr-1"></i> Modul {{ $tipeLabel }}
        </span>
    </div>
</div>
@stop

@section('css')
<style>
    body, .content-wrapper, .card, .btn, .form-control {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
    }
    h1.page-title {
        font-size: 1.15rem !important;
        font-weight: 700 !important;
        letter-spacing: -0.015em;
        margin-bottom: 0;
    }
    .content-header .breadcrumb {
        font-size: 0.76rem !important;
        margin-top: 2px;
    }

    /* Note Card Styles */
    .note-card {
        border-radius: 12px;
        transition: transform 0.18s ease, box-shadow 0.18s ease;
        border: 1px solid rgba(0,0,0,0.08);
        position: relative;
        display: flex;
        flex-direction: column;
        height: 100%;
        overflow: hidden;
    }
    .note-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.12) !important;
    }

    /* Colors */
    .note-yellow { background-color: #fefce8; border-color: #fef08a; }
    .note-blue   { background-color: #f0f9ff; border-color: #bae6fd; }
    .note-green  { background-color: #f0fdf4; border-color: #bbf7d0; }
    .note-red    { background-color: #fef2f2; border-color: #fecaca; }
    .note-purple { background-color: #faf5ff; border-color: #e9d5ff; }
    .note-gray   { background-color: #f8fafc; border-color: #e2e8f0; }

    .note-card .note-header {
        padding: 0.75rem 0.95rem 0.35rem 0.95rem;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }
    .note-card .note-title {
        font-weight: 700;
        font-size: 0.95rem;
        color: #1e293b;
        margin-bottom: 0;
        line-height: 1.35;
        word-break: break-word;
        text-align: left !important;
        text-indent: 0 !important;
    }
    .note-card .note-body {
        padding: 0.35rem 0.95rem 0.75rem 0.95rem;
        flex-grow: 1;
        font-size: 0.875rem;
        color: #334155;
        white-space: pre-line;
        word-break: break-word;
        line-height: 1.5;
        text-align: left !important;
        text-indent: 0 !important;
    }
    .note-card .note-footer {
        padding: 0.5rem 0.95rem;
        background: rgba(255,255,255,0.45);
        border-top: 1px solid rgba(0,0,0,0.05);
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.75rem;
        color: #64748b;
    }

    /* Completed state for Note */
    .note-completed .note-title,
    .note-completed .note-body {
        text-decoration: line-through;
        opacity: 0.6;
    }

    /* Color radio buttons */
    .color-picker-label {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-right: 6px;
        border: 2px solid transparent;
        transition: transform 0.15s ease;
    }
    .color-picker-label:hover {
        transform: scale(1.15);
    }
    .color-picker-input:checked + .color-picker-label {
        border-color: #0f172a;
        box-shadow: 0 0 0 2px rgba(255,255,255,0.8);
    }
    .bg-pick-yellow { background-color: #fef08a; }
    .bg-pick-blue   { background-color: #bae6fd; }
    .bg-pick-green  { background-color: #bbf7d0; }
    .bg-pick-red    { background-color: #fecaca; }
    .bg-pick-purple { background-color: #e9d5ff; }
    .bg-pick-gray   { background-color: #e2e8f0; }

    .btn-pin-active {
        color: #d97706 !important;
    }

    /* Task & Reminder Styles */
    .task-card-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .task-item {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 9px 12px;
        transition: all 0.18s ease;
        position: relative;
    }
    .task-item:hover {
        border-color: #cbd5e1;
        box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    }
    .task-item.completed {
        background: #f8fafc;
        border-color: #e2e8f0;
        opacity: 0.78;
    }
    .task-item.completed .task-text {
        text-decoration: line-through;
        color: #94a3b8 !important;
    }
    .task-checkbox {
        width: 17px;
        height: 17px;
        cursor: pointer;
        accent-color: #10b981;
    }
    .task-priority-indicator {
        width: 4px;
        border-radius: 2px;
        margin-right: 10px;
        align-self: stretch;
    }
    .priority-mendesak { background-color: #ef4444; }
    .priority-penting  { background-color: #f59e0b; }
    .priority-normal   { background-color: #cbd5e1; }
</style>
@stop

@section('content')
<div class="container-fluid px-0">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <strong><i class="fas fa-exclamation-triangle mr-1"></i> Gagal menyimpan:</strong>
            <ul class="mb-0 mt-1 pl-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <!-- SPLIT VIEW LAYOUT: OPSI B (Berdampingan di Desktop) -->
    <div class="row">
        <!-- ========================================== -->
        <!-- KOLOM KIRI: CATATAN STICKY NOTES (±58-60%) -->
        <!-- ========================================== -->
        <div class="col-xl-7 col-lg-7 col-12 mb-4">
            <!-- 1. QUICK ADD NOTE CARD -->
            <div class="card shadow-sm border-0 mb-3" style="border-radius: 12px; background: #ffffff;">
                <div class="card-body p-3">
                    <form action="{{ route($tipe === 'bosp' ? 'bos.notes.store' : 'bpopp.notes.store') }}" method="POST" id="formQuickNote">
                        @csrf
                        <div class="form-group mb-2">
                            <input type="text" name="title" class="form-control font-weight-bold border-0 px-2" 
                                   placeholder="Judul memo catatan... (opsional)" 
                                   style="font-size: 0.95rem; box-shadow: none; background: transparent;" 
                                   value="{{ old('title') }}">
                        </div>
                        <div class="form-group mb-2">
                            <textarea name="content" class="form-control border-0 px-2" rows="2" 
                                      placeholder="Tulis catatan cepat {{ $tipeLabel }} di sini... (misal: rincian nota, no rekening, info kontak supplier)" 
                                      style="font-size: 0.88rem; resize: vertical; box-shadow: none; background: transparent;" required>{{ old('content') }}</textarea>
                        </div>

                        <div class="d-flex justify-content-between align-items-center flex-wrap pt-2 border-top" style="gap: 8px;">
                            <!-- Color Picker -->
                            <div class="d-flex align-items-center">
                                <span class="small text-muted font-weight-bold mr-2 d-none d-sm-inline" style="font-size: 0.78rem;">Warna:</span>
                                <div class="d-flex align-items-center">
                                    <input type="radio" name="color" value="yellow" id="q_yellow" class="color-picker-input d-none" checked>
                                    <label for="q_yellow" class="color-picker-label bg-pick-yellow" title="Kuning (Memo Standar)"></label>

                                    <input type="radio" name="color" value="blue" id="q_blue" class="color-picker-input d-none">
                                    <label for="q_blue" class="color-picker-label bg-pick-blue" title="Biru (Administrasi)"></label>

                                    <input type="radio" name="color" value="green" id="q_green" class="color-picker-input d-none">
                                    <label for="q_green" class="color-picker-label bg-pick-green" title="Hijau (Pencairan / Selesai)"></label>

                                    <input type="radio" name="color" value="red" id="q_red" class="color-picker-input d-none">
                                    <label for="q_red" class="color-picker-label bg-pick-red" title="Merah (Penting / Deadline)"></label>

                                    <input type="radio" name="color" value="purple" id="q_purple" class="color-picker-input d-none">
                                    <label for="q_purple" class="color-picker-label bg-pick-purple" title="Ungu (Khusus)"></label>

                                    <input type="radio" name="color" value="gray" id="q_gray" class="color-picker-input d-none">
                                    <label for="q_gray" class="color-picker-label bg-pick-gray" title="Abu-abu"></label>
                                </div>
                            </div>

                            <!-- Pin Checkbox & Save Button -->
                            <div class="d-flex align-items-center" style="gap: 10px;">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="q_pinned" name="is_pinned" value="1">
                                    <label class="custom-control-label small font-weight-bold text-secondary" for="q_pinned" style="cursor: pointer; font-size: 0.78rem;">
                                        <i class="fas fa-thumbtack mr-1 text-warning"></i> Sematkan
                                    </label>
                                </div>

                                <button type="submit" class="btn btn-primary btn-sm px-3 font-weight-bold shadow-sm" style="border-radius: 8px;">
                                    <i class="fas fa-paper-plane mr-1"></i> Simpan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 2. FILTER & SEARCH BAR CATATAN -->
            <div class="card shadow-sm border-0 mb-3" style="border-radius: 12px;">
                <div class="card-body py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 8px;">
                        <!-- Filter Tabs -->
                        <div class="nav nav-pills small font-weight-bold">
                            <a class="nav-link py-1 px-2 mr-1 {{ request('filter', 'active') === 'active' ? 'active' : '' }}" 
                               href="{{ route($tipe === 'bosp' ? 'bos.notes.index' : 'bpopp.notes.index', array_merge(request()->query(), ['filter' => 'active'])) }}"
                               style="font-size: 0.8rem;">
                                <i class="fas fa-sticky-note mr-1"></i> Aktif ({{ $counts['active'] }})
                            </a>
                            <a class="nav-link py-1 px-2 mr-1 {{ request('filter') === 'pinned' ? 'active' : '' }}" 
                               href="{{ route($tipe === 'bosp' ? 'bos.notes.index' : 'bpopp.notes.index', array_merge(request()->query(), ['filter' => 'pinned'])) }}"
                               style="font-size: 0.8rem;">
                                <i class="fas fa-thumbtack text-warning mr-1"></i> Semat ({{ $counts['pinned'] }})
                            </a>
                            <a class="nav-link py-1 px-2 mr-1 {{ request('filter') === 'completed' ? 'active' : '' }}" 
                               href="{{ route($tipe === 'bosp' ? 'bos.notes.index' : 'bpopp.notes.index', array_merge(request()->query(), ['filter' => 'completed'])) }}"
                               style="font-size: 0.8rem;">
                                <i class="fas fa-check-circle text-success mr-1"></i> Selesai ({{ $counts['completed'] }})
                            </a>
                            <a class="nav-link py-1 px-2 {{ request('filter') === 'all' ? 'active' : '' }}" 
                               href="{{ route($tipe === 'bosp' ? 'bos.notes.index' : 'bpopp.notes.index', array_merge(request()->query(), ['filter' => 'all'])) }}"
                               style="font-size: 0.8rem;">
                                Semua ({{ $counts['total'] }})
                            </a>
                        </div>

                        <!-- Search Form -->
                        <form method="GET" action="{{ route($tipe === 'bosp' ? 'bos.notes.index' : 'bpopp.notes.index') }}" class="form-inline">
                            @if(request('filter'))
                                <input type="hidden" name="filter" value="{{ request('filter') }}">
                            @endif
                            @if(request('task_filter'))
                                <input type="hidden" name="task_filter" value="{{ request('task_filter') }}">
                            @endif
                            <div class="input-group input-group-sm" style="min-width: 170px;">
                                <input type="text" name="search" class="form-control" placeholder="Cari catatan..." value="{{ request('search') }}" style="font-size: 0.8rem;">
                                <div class="input-group-append">
                                    <button class="btn btn-default border" type="submit"><i class="fas fa-search"></i></button>
                                    @if(request('search'))
                                        <a href="{{ route($tipe === 'bosp' ? 'bos.notes.index' : 'bpopp.notes.index', array_merge(request()->except('search'), ['filter' => request('filter', 'active')])) }}" class="btn btn-default border" title="Reset cari">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 3. STICKY NOTES GRID -->
            @if($notes->isEmpty())
                <div class="card shadow-sm border-0 py-5 text-center" style="border-radius: 12px; background: #ffffff;">
                    <div class="card-body">
                        <i class="fas fa-note-sticky text-muted mb-3" style="font-size: 3rem; opacity: 0.35;"></i>
                        <h6 class="font-weight-bold text-dark mb-1">Belum Ada Catatan {{ $tipeLabel }}</h6>
                        <p class="text-muted small mb-0">Tulis catatan memo cepat menggunakan form di atas.</p>
                    </div>
                </div>
            @else
                <!-- SECTION PINNED NOTES -->
                @if($pinnedNotes->isNotEmpty())
                    <div class="mb-2 d-flex align-items-center">
                        <span class="text-uppercase small font-weight-bold text-muted mr-2" style="letter-spacing: 0.5px; font-size: 0.72rem;">
                            <i class="fas fa-thumbtack text-warning mr-1"></i> Catatan Disematkan
                        </span>
                        <div class="flex-grow-1 border-top" style="border-color: #cbd5e1 !important;"></div>
                    </div>

                    <div class="row mb-3">
                        @foreach($pinnedNotes as $note)
                            <div class="col-sm-6 mb-3">
                                @include('notes.partials.card', ['note' => $note])
                            </div>
                        @endforeach
                    </div>
                @endif

                <!-- SECTION OTHER NOTES -->
                @if($otherNotes->isNotEmpty())
                    @if($pinnedNotes->isNotEmpty())
                        <div class="mb-2 d-flex align-items-center">
                            <span class="text-uppercase small font-weight-bold text-muted mr-2" style="letter-spacing: 0.5px; font-size: 0.72rem;">
                                Catatan Lainnya
                            </span>
                            <div class="flex-grow-1 border-top" style="border-color: #cbd5e1 !important;"></div>
                        </div>
                    @endif

                    <div class="row">
                        @foreach($otherNotes as $note)
                            <div class="col-sm-6 mb-3">
                                @include('notes.partials.card', ['note' => $note])
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>

        <!-- ==================================================== -->
        <!-- KOLOM KANAN: AGENDA & PENGINGAT KEGIATAN (±40-42%) -->
        <!-- ==================================================== -->
        <div class="col-xl-5 col-lg-5 col-12 mb-4">
            <div class="card shadow-sm border-0" style="border-radius: 12px; background: #ffffff;">
                <div class="card-header border-bottom py-3 px-3 bg-white" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
                    <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 8px;">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-calendar-check text-primary mr-2" style="font-size: 1.15rem;"></i>
                            <h6 class="font-weight-bold mb-0 text-dark" style="font-size: 0.95rem;">
                                Agenda & Pengingat
                            </h6>
                            <span class="badge badge-primary badge-pill ml-2" style="font-size: 0.72rem;">
                                {{ $taskCounts['active'] }} aktif
                            </span>
                            @if($taskCounts['overdue'] > 0)
                                <span class="badge badge-danger badge-pill ml-1" style="font-size: 0.72rem;">
                                    {{ $taskCounts['overdue'] }} lewat
                                </span>
                            @endif
                        </div>
                        <button type="button" class="btn btn-xs btn-outline-primary font-weight-bold" onclick="showModal('#modalCreateTask')" style="border-radius: 6px; padding: 3px 8px;">
                            <i class="fas fa-plus mr-1"></i> Tambah
                        </button>
                    </div>
                </div>

                <div class="card-body p-3">
                    <!-- Quick Add Task Bar -->
                    <form action="{{ route($tipe === 'bosp' ? 'bos.notes.tasks.store' : 'bpopp.notes.tasks.store') }}" method="POST" class="mb-3" id="formQuickTask">
                        @csrf
                        <div class="input-group">
                            <input type="text" name="title" class="form-control form-control-sm" placeholder="Tulis agenda / pengingat kegiatan baru..." required style="font-size: 0.85rem;">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-sm btn-primary px-3 font-weight-bold">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="d-flex align-items-center justify-content-between flex-wrap mt-2 pt-1" style="gap: 6px;">
                            <div class="d-flex align-items-center" style="gap: 4px;">
                                <span class="text-muted" style="font-size: 0.72rem;"><i class="far fa-calendar-alt mr-1 text-secondary"></i>Tenggat:</span>
                                <input type="date" name="due_date" class="form-control form-control-sm py-0 px-1" style="width: 125px; height: 26px; font-size: 0.75rem;">
                            </div>
                            <div class="d-flex align-items-center" style="gap: 4px;">
                                <span class="text-muted" style="font-size: 0.72rem;">Prioritas:</span>
                                <select name="priority" class="form-control form-control-sm py-0 px-1" style="width: 95px; height: 26px; font-size: 0.75rem;">
                                    <option value="normal">Normal</option>
                                    <option value="penting">Penting</option>
                                    <option value="mendesak">Mendesak</option>
                                </select>
                            </div>
                        </div>
                    </form>

                    <!-- Filter Tabs Task -->
                    <div class="nav nav-pills small font-weight-bold mb-3 pb-2 border-bottom flex-nowrap" style="overflow-x: auto; gap: 4px;">
                        <a class="nav-link py-1 px-2 {{ ($taskFilter ?? 'active') === 'active' ? 'active' : '' }}" 
                           href="{{ route($tipe === 'bosp' ? 'bos.notes.index' : 'bpopp.notes.index', array_merge(request()->query(), ['task_filter' => 'active'])) }}"
                           style="font-size: 0.76rem; white-space: nowrap;">
                            Aktif ({{ $taskCounts['active'] }})
                        </a>
                        <a class="nav-link py-1 px-2 {{ ($taskFilter ?? '') === 'today' ? 'active' : '' }}" 
                           href="{{ route($tipe === 'bosp' ? 'bos.notes.index' : 'bpopp.notes.index', array_merge(request()->query(), ['task_filter' => 'today'])) }}"
                           style="font-size: 0.76rem; white-space: nowrap;">
                            Hari Ini ({{ $taskCounts['today'] }})
                        </a>
                        <a class="nav-link py-1 px-2 {{ ($taskFilter ?? '') === 'overdue' ? 'active' : '' }}" 
                           href="{{ route($tipe === 'bosp' ? 'bos.notes.index' : 'bpopp.notes.index', array_merge(request()->query(), ['task_filter' => 'overdue'])) }}"
                           style="font-size: 0.76rem; white-space: nowrap;">
                            Terlambat ({{ $taskCounts['overdue'] }})
                        </a>
                        <a class="nav-link py-1 px-2 {{ ($taskFilter ?? '') === 'upcoming' ? 'active' : '' }}" 
                           href="{{ route($tipe === 'bosp' ? 'bos.notes.index' : 'bpopp.notes.index', array_merge(request()->query(), ['task_filter' => 'upcoming'])) }}"
                           style="font-size: 0.76rem; white-space: nowrap;">
                            Mendatang ({{ $taskCounts['upcoming'] }})
                        </a>
                        <a class="nav-link py-1 px-2 {{ ($taskFilter ?? '') === 'completed' ? 'active' : '' }}" 
                           href="{{ route($tipe === 'bosp' ? 'bos.notes.index' : 'bpopp.notes.index', array_merge(request()->query(), ['task_filter' => 'completed'])) }}"
                           style="font-size: 0.76rem; white-space: nowrap;">
                            Selesai ({{ $taskCounts['completed'] }})
                        </a>
                        <a class="nav-link py-1 px-2 {{ ($taskFilter ?? '') === 'all' ? 'active' : '' }}" 
                           href="{{ route($tipe === 'bosp' ? 'bos.notes.index' : 'bpopp.notes.index', array_merge(request()->query(), ['task_filter' => 'all'])) }}"
                           style="font-size: 0.76rem; white-space: nowrap;">
                            Semua ({{ $taskCounts['total'] }})
                        </a>
                    </div>

                    <!-- Task List Items -->
                    @if($tasks->isEmpty())
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-clipboard-check text-muted mb-2" style="font-size: 2.2rem; opacity: 0.35;"></i>
                            <h6 class="small font-weight-bold text-secondary mb-1">Tidak Ada Agenda Kegiatan</h6>
                            <p class="small text-muted mb-0" style="font-size: 0.76rem;">Semua agenda pada filter ini telah terselesaikan atau belum dibuat.</p>
                        </div>
                    @else
                        <div class="task-card-list">
                            @foreach($tasks as $task)
                                @include('notes.partials.task_item', ['task' => $task])
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- 4. MODAL TAMBAH CATATAN                    -->
<!-- ========================================== -->
<div class="modal fade" id="modalCreateNote" tabindex="-1" role="dialog" aria-labelledby="modalCreateNoteLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow" style="border-radius: 12px;">
            <form method="POST" action="{{ route($tipe === 'bosp' ? 'bos.notes.store' : 'bpopp.notes.store') }}">
                @csrf
                <div class="modal-header border-bottom py-2 px-3">
                    <h5 class="modal-title font-weight-bold" id="modalCreateNoteLabel" style="font-size: 0.95rem;">
                        <i class="fas fa-plus-circle text-primary mr-1"></i> Tambah Catatan {{ $tipeLabel }}
                    </h5>
                    <button type="button" class="close btn-close btn-dismiss-modal" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" style="background: none; border: none; font-size: 1.25rem;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-3">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold text-muted mb-1">Judul Catatan</label>
                        <input type="text" name="title" class="form-control form-control-sm font-weight-bold" placeholder="Judul (opsional)">
                    </div>

                    <div class="form-group mb-2">
                        <label class="small font-weight-bold text-muted mb-1">Isi Catatan <span class="text-danger">*</span></label>
                        <textarea name="content" class="form-control" rows="5" placeholder="Tulis isi catatan memo..." required></textarea>
                    </div>

                    <div class="form-group mb-2">
                        <label class="small font-weight-bold text-muted mb-1 d-block">Warna Catatan</label>
                        <div class="d-flex align-items-center">
                            <input type="radio" name="color" value="yellow" id="create_color_yellow" class="color-picker-input d-none" checked>
                            <label for="create_color_yellow" class="color-picker-label bg-pick-yellow" title="Kuning (Memo Standar)"></label>

                            <input type="radio" name="color" value="blue" id="create_color_blue" class="color-picker-input d-none">
                            <label for="create_color_blue" class="color-picker-label bg-pick-blue" title="Biru (Administrasi)"></label>

                            <input type="radio" name="color" value="green" id="create_color_green" class="color-picker-input d-none">
                            <label for="create_color_green" class="color-picker-label bg-pick-green" title="Hijau (Pencairan / Selesai)"></label>

                            <input type="radio" name="color" value="red" id="create_color_red" class="color-picker-input d-none">
                            <label for="create_color_red" class="color-picker-label bg-pick-red" title="Merah (Penting / Deadline)"></label>

                            <input type="radio" name="color" value="purple" id="create_color_purple" class="color-picker-input d-none">
                            <label for="create_color_purple" class="color-picker-label bg-pick-purple" title="Ungu (Khusus)"></label>

                            <input type="radio" name="color" value="gray" id="create_color_gray" class="color-picker-input d-none">
                            <label for="create_color_gray" class="color-picker-label bg-pick-gray" title="Abu-abu"></label>
                        </div>
                    </div>

                    <div class="d-flex align-items-center mt-3">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="create_pinned" name="is_pinned" value="1">
                            <label class="custom-control-label small font-weight-bold text-secondary" for="create_pinned" style="cursor: pointer;">
                                <i class="fas fa-thumbtack text-warning mr-1"></i> Sematkan di atas
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-2 px-3 justify-content-between">
                    <button type="button" class="btn btn-secondary btn-sm px-3 btn-dismiss-modal" data-dismiss="modal" data-bs-dismiss="modal" onclick="hideModal('#modalCreateNote')">
                        <i class="fas fa-times mr-1"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-primary btn-sm px-3 font-weight-bold">
                        <i class="fas fa-paper-plane mr-1"></i> Simpan Catatan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- 5. MODAL EDIT NOTE                         -->
<!-- ========================================== -->
<div class="modal fade" id="modalEditNote" tabindex="-1" role="dialog" aria-labelledby="modalEditNoteLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow" style="border-radius: 12px;">
            <form id="formEditNote" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-header border-bottom py-2 px-3">
                    <h5 class="modal-title font-weight-bold" id="modalEditNoteLabel" style="font-size: 0.95rem;">
                        <i class="fas fa-edit text-primary mr-1"></i> Ubah Catatan
                    </h5>
                    <button type="button" class="close btn-close btn-dismiss-modal" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" style="background: none; border: none; font-size: 1.25rem;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-3">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold text-muted mb-1">Judul Catatan</label>
                        <input type="text" name="title" id="edit_title" class="form-control form-control-sm font-weight-bold" placeholder="Judul (opsional)">
                    </div>

                    <div class="form-group mb-2">
                        <label class="small font-weight-bold text-muted mb-1">Isi Catatan <span class="text-danger">*</span></label>
                        <textarea name="content" id="edit_content" class="form-control" rows="5" placeholder="Tulis isi catatan..." required></textarea>
                    </div>

                    <div class="form-group mb-2">
                        <label class="small font-weight-bold text-muted mb-1 d-block">Warna Catatan</label>
                        <div class="d-flex align-items-center">
                            <input type="radio" name="color" value="yellow" id="edit_color_yellow" class="color-picker-input d-none">
                            <label for="edit_color_yellow" class="color-picker-label bg-pick-yellow"></label>

                            <input type="radio" name="color" value="blue" id="edit_color_blue" class="color-picker-input d-none">
                            <label for="edit_color_blue" class="color-picker-label bg-pick-blue"></label>

                            <input type="radio" name="color" value="green" id="edit_color_green" class="color-picker-input d-none">
                            <label for="edit_color_green" class="color-picker-label bg-pick-green"></label>

                            <input type="radio" name="color" value="red" id="edit_color_red" class="color-picker-input d-none">
                            <label for="edit_color_red" class="color-picker-label bg-pick-red"></label>

                            <input type="radio" name="color" value="purple" id="edit_color_purple" class="color-picker-input d-none">
                            <label for="edit_color_purple" class="color-picker-label bg-pick-purple"></label>

                            <input type="radio" name="color" value="gray" id="edit_color_gray" class="color-picker-input d-none">
                            <label for="edit_color_gray" class="color-picker-label bg-pick-gray"></label>
                        </div>
                    </div>

                    <div class="d-flex align-items-center mt-3" style="gap: 15px;">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="edit_pinned" name="is_pinned" value="1">
                            <label class="custom-control-label small font-weight-bold text-secondary" for="edit_pinned" style="cursor: pointer;">
                                <i class="fas fa-thumbtack text-warning mr-1"></i> Sematkan di atas
                            </label>
                        </div>

                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="edit_completed" name="is_completed" value="1">
                            <label class="custom-control-label small font-weight-bold text-secondary" for="edit_completed" style="cursor: pointer;">
                                <i class="fas fa-check text-success mr-1"></i> Tandai selesai
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-2 px-3 justify-content-between">
                    <button type="button" class="btn btn-secondary btn-sm px-3 btn-dismiss-modal" data-dismiss="modal" data-bs-dismiss="modal" onclick="hideModal('#modalEditNote')">
                        <i class="fas fa-times mr-1"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-primary btn-sm px-3 font-weight-bold">
                        <i class="fas fa-save mr-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- 6. MODAL TAMBAH TUGAS / PENGINGAT (NEW)    -->
<!-- ========================================== -->
<div class="modal fade" id="modalCreateTask" tabindex="-1" role="dialog" aria-labelledby="modalCreateTaskLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow" style="border-radius: 12px;">
            <form method="POST" action="{{ route($tipe === 'bosp' ? 'bos.notes.tasks.store' : 'bpopp.notes.tasks.store') }}">
                @csrf
                <div class="modal-header border-bottom py-2 px-3">
                    <h5 class="modal-title font-weight-bold" id="modalCreateTaskLabel" style="font-size: 0.95rem;">
                        <i class="fas fa-calendar-plus text-primary mr-1"></i> Tambah Agenda & Pengingat {{ $tipeLabel }}
                    </h5>
                    <button type="button" class="close btn-close btn-dismiss-modal" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" style="background: none; border: none; font-size: 1.25rem;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-3">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold text-muted mb-1">Judul Agenda / Tugas <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control form-control-sm font-weight-bold" placeholder="Misal: Bayar Listrik Sekolah, Selesaikan BKU, SPJ Perjadin" required>
                    </div>

                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <label class="small font-weight-bold text-muted mb-1">Tanggal Batas Waktu / Pelaksanaan</label>
                            <input type="date" name="due_date" class="form-control form-control-sm">
                        </div>
                        <div class="col-sm-6">
                            <label class="small font-weight-bold text-muted mb-1">Jam (Opsional)</label>
                            <input type="time" name="due_time" class="form-control form-control-sm">
                        </div>
                    </div>

                    <div class="form-group mb-2">
                        <label class="small font-weight-bold text-muted mb-1">Tingkat Prioritas</label>
                        <select name="priority" class="form-control form-control-sm">
                            <option value="normal">Normal (Biasa)</option>
                            <option value="penting">Penting</option>
                            <option value="mendesak">Mendesak (Segera)</option>
                        </select>
                    </div>

                    <div class="form-group mb-2">
                        <label class="small font-weight-bold text-muted mb-1">Referensi Kegiatan Kertas Kerja / Belanja (Opsional)</label>
                        <input type="text" name="kegiatan_ref" class="form-control form-control-sm" placeholder="Misal: Tagihan Internet Iconnet, Belanja ATK Triwulan 3">
                    </div>

                    <div class="form-group mb-2">
                        <label class="small font-weight-bold text-muted mb-1">Rincian / Keterangan Tambahan</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Tambahkan catatan langkah atau rincian jika ada..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top py-2 px-3 justify-content-between">
                    <button type="button" class="btn btn-secondary btn-sm px-3 btn-dismiss-modal" data-dismiss="modal" data-bs-dismiss="modal" onclick="hideModal('#modalCreateTask')">
                        <i class="fas fa-times mr-1"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-primary btn-sm px-3 font-weight-bold">
                        <i class="fas fa-save mr-1"></i> Simpan Agenda
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- 7. MODAL EDIT TUGAS / PENGINGAT (NEW)      -->
<!-- ========================================== -->
<div class="modal fade" id="modalEditTask" tabindex="-1" role="dialog" aria-labelledby="modalEditTaskLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow" style="border-radius: 12px;">
            <form id="formEditTask" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-header border-bottom py-2 px-3">
                    <h5 class="modal-title font-weight-bold" id="modalEditTaskLabel" style="font-size: 0.95rem;">
                        <i class="fas fa-edit text-primary mr-1"></i> Ubah Agenda & Pengingat
                    </h5>
                    <button type="button" class="close btn-close btn-dismiss-modal" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" style="background: none; border: none; font-size: 1.25rem;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-3">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold text-muted mb-1">Judul Agenda / Tugas <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="edit_task_title" class="form-control form-control-sm font-weight-bold" required>
                    </div>

                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <label class="small font-weight-bold text-muted mb-1">Tanggal Batas Waktu / Pelaksanaan</label>
                            <input type="date" name="due_date" id="edit_task_due_date" class="form-control form-control-sm">
                        </div>
                        <div class="col-sm-6">
                            <label class="small font-weight-bold text-muted mb-1">Jam (Opsional)</label>
                            <input type="time" name="due_time" id="edit_task_due_time" class="form-control form-control-sm">
                        </div>
                    </div>

                    <div class="form-group mb-2">
                        <label class="small font-weight-bold text-muted mb-1">Tingkat Prioritas</label>
                        <select name="priority" id="edit_task_priority" class="form-control form-control-sm">
                            <option value="normal">Normal (Biasa)</option>
                            <option value="penting">Penting</option>
                            <option value="mendesak">Mendesak (Segera)</option>
                        </select>
                    </div>

                    <div class="form-group mb-2">
                        <label class="small font-weight-bold text-muted mb-1">Referensi Kegiatan Kertas Kerja / Belanja (Opsional)</label>
                        <input type="text" name="kegiatan_ref" id="edit_task_kegiatan_ref" class="form-control form-control-sm">
                    </div>

                    <div class="form-group mb-2">
                        <label class="small font-weight-bold text-muted mb-1">Rincian / Keterangan Tambahan</label>
                        <textarea name="description" id="edit_task_description" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="custom-control custom-checkbox mt-3">
                        <input type="checkbox" class="custom-control-input" id="edit_task_completed" name="is_completed" value="1">
                        <label class="custom-control-label small font-weight-bold text-secondary" for="edit_task_completed" style="cursor: pointer;">
                            <i class="fas fa-check text-success mr-1"></i> Tandai sudah selesai
                        </label>
                    </div>
                </div>
                <div class="modal-footer border-top py-2 px-3 justify-content-between">
                    <button type="button" class="btn btn-secondary btn-sm px-3 btn-dismiss-modal" data-dismiss="modal" data-bs-dismiss="modal" onclick="hideModal('#modalEditTask')">
                        <i class="fas fa-times mr-1"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-primary btn-sm px-3 font-weight-bold">
                        <i class="fas fa-save mr-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
    window.showModal = function(selector) {
        const el = typeof selector === 'string' ? document.querySelector(selector) : selector;
        if (!el) return;

        if (window.bootstrap && typeof window.bootstrap.Modal !== 'undefined') {
            try {
                const modal = bootstrap.Modal.getOrCreateInstance ? bootstrap.Modal.getOrCreateInstance(el) : (bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el));
                modal.show();
                return;
            } catch (e) {}
        }

        if (window.jQuery && typeof $(el).modal === 'function') {
            try {
                $(el).modal('show');
                return;
            } catch (e) {}
        }

        $(el).addClass('show').css('display', 'block').removeAttr('aria-hidden').attr('aria-modal', 'true');
        if (!$('.modal-backdrop').length) {
            $('body').addClass('modal-open').append('<div class="modal-backdrop fade show custom-modal-backdrop"></div>');
        }
    };

    window.hideModal = function(selector) {
        const el = typeof selector === 'string' ? document.querySelector(selector) : selector;
        if (!el) return;

        if (window.bootstrap && typeof window.bootstrap.Modal !== 'undefined') {
            try {
                const modal = bootstrap.Modal.getInstance(el);
                if (modal) {
                    modal.hide();
                }
            } catch (e) {}
        }

        if (window.jQuery && typeof $(el).modal === 'function') {
            try {
                $(el).modal('hide');
            } catch (e) {}
        }

        $(el).removeClass('show').css('display', 'none').attr('aria-hidden', 'true').removeAttr('aria-modal');
        $('body').removeClass('modal-open');
        $('.modal-backdrop, .custom-modal-backdrop').remove();
    };

    // Note Modal Edit Handler
    function openEditModal(note) {
        const form = document.getElementById('formEditNote');
        form.action = `/notes/${note.id}`;

        document.getElementById('edit_title').value = note.title || '';
        document.getElementById('edit_content').value = note.content || '';

        const colorInput = document.getElementById(`edit_color_${note.color || 'yellow'}`);
        if (colorInput) {
            colorInput.checked = true;
        }

        document.getElementById('edit_pinned').checked = Boolean(note.is_pinned);
        document.getElementById('edit_completed').checked = Boolean(note.is_completed);

        window.showModal('#modalEditNote');
    }

    // Task Modal Edit Handler
    function openEditTaskModal(task) {
        const form = document.getElementById('formEditTask');
        form.action = `/notes/tasks/${task.id}`;

        document.getElementById('edit_task_title').value = task.title || '';
        document.getElementById('edit_task_due_date').value = task.due_date ? task.due_date.substring(0, 10) : '';
        document.getElementById('edit_task_due_time').value = task.due_time || '';
        document.getElementById('edit_task_priority').value = task.priority || 'normal';
        document.getElementById('edit_task_kegiatan_ref').value = task.kegiatan_ref || '';
        document.getElementById('edit_task_description').value = task.description || '';
        document.getElementById('edit_task_completed').checked = Boolean(task.is_completed);

        window.showModal('#modalEditTask');
    }

    // Task AJAX Toggle Complete
    function toggleTaskComplete(taskId, checkbox) {
        const item = document.getElementById(`task-item-${taskId}`);
        const isChecked = checkbox.checked;

        // Optimistic UI update
        if (item) {
            if (isChecked) {
                item.classList.add('completed');
            } else {
                item.classList.remove('completed');
            }
        }

        fetch(`/notes/tasks/${taskId}/toggle-complete`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ is_completed: isChecked })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Gagal memperbarui status');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const badge = document.getElementById(`task-due-badge-${taskId}`);
                if (badge && data.due_label) {
                    badge.className = `badge ${data.due_badge_class} px-2 py-1`;
                    badge.innerHTML = `<i class="far fa-clock mr-1"></i>${data.due_label}`;
                }
            } else {
                checkbox.checked = !isChecked;
                if (item) item.classList.toggle('completed');
            }
        })
        .catch(err => {
            console.error('Error toggling task:', err);
            checkbox.checked = !isChecked;
            if (item) item.classList.toggle('completed');
            alert('Gagal memperbarui status tugas. Silakan coba lagi.');
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Universal dismiss button click handler
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('[data-dismiss="modal"], [data-bs-dismiss="modal"], .btn-dismiss-modal');
            if (btn) {
                const modal = btn.closest('.modal');
                if (modal) {
                    window.hideModal(modal);
                }
            }
        });
    });
</script>
@stop
