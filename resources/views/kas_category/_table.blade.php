<div class="table-responsive">
    <table class="table table-sm table-hover table-bordered table-striped w-100 category-table" id="{{ $tableId }}">
        <thead>
            <tr>
                <th width="45" class="text-center">URUT</th>
                <th>KATEGORI</th>
                <th width="150">KODE SISTEM</th>
                @if(!$defaultType)
                    <th width="90" class="text-center">TIPE</th>
                @endif
                <th>KETERANGAN / PENJELASAN</th>
                <th width="95" class="text-center">DIPAKAI</th>
                <th width="85" class="text-center">SIFAT</th>
                <th width="90" class="text-center">STATUS</th>
                <th width="85" class="text-center">AKSI</th>
            </tr>
        </thead>
        <tbody>
            @forelse($cats as $cat)
                <tr>
                    <td class="text-center font-weight-bold text-muted">{{ $cat->sort_order }}</td>
                    <td>
                        <span class="mr-1" style="font-size: 1.15rem;">{{ $cat->icon ?: ($cat->type === 'masuk' ? '📥' : '🛍️') }}</span>
                        <span class="font-weight-bold text-dark">{{ $cat->name }}</span>
                    </td>
                    <td>
                        <code class="text-primary font-weight-bold" style="font-size: 0.78rem;">{{ $cat->code }}</code>
                    </td>
                    @if(!$defaultType)
                        <td class="text-center">
                            @if($cat->type === 'masuk')
                                <span class="badge badge-success px-2 py-1"><i class="fas fa-arrow-down mr-1"></i>Masuk</span>
                            @else
                                <span class="badge badge-danger px-2 py-1"><i class="fas fa-arrow-up mr-1"></i>Keluar</span>
                            @endif
                        </td>
                    @endif
                    <td>
                        <small class="text-muted">{{ $cat->description ?: '-' }}</small>
                    </td>
                    <td class="text-center">
                        @if($cat->kas_tunais_count > 0)
                            <span class="badge badge-info px-2 py-1" title="{{ $cat->kas_tunais_count }} transaksi menggunakan kategori ini">
                                <i class="fas fa-receipt mr-1"></i>{{ $cat->kas_tunais_count }} trx
                            </span>
                        @else
                            <span class="text-muted small">0 trx</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($cat->is_system)
                            <span class="badge badge-system px-2 py-1" title="Kategori inti aplikasi yang dilindungi">
                                <i class="fas fa-shield-alt mr-1"></i>Sistem
                            </span>
                        @else
                            <span class="badge badge-custom px-2 py-1">Kustom</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <button type="button" 
                                class="btn btn-xs btn-toggle-status badge {{ $cat->is_active ? 'badge-success' : 'badge-secondary' }} px-2 py-1 border-0" 
                                data-id="{{ $cat->id }}" 
                                title="Klik untuk mengaktifkan/menonaktifkan">
                            <i class="fas {{ $cat->is_active ? 'fa-check-circle' : 'fa-times-circle' }} mr-1"></i>
                            {{ $cat->is_active ? 'Aktif' : 'Nonaktif' }}
                        </button>
                    </td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <button type="button" 
                                    class="btn btn-xs btn-outline-primary btn-edit-category" 
                                    data-id="{{ $cat->id }}"
                                    data-name="{{ $cat->name }}"
                                    data-code="{{ $cat->code }}"
                                    data-type="{{ $cat->type }}"
                                    data-icon="{{ $cat->icon }}"
                                    data-desc="{{ $cat->description }}"
                                    data-sort="{{ $cat->sort_order }}"
                                    data-system="{{ $cat->is_system ? '1' : '0' }}"
                                    title="Edit Kategori">
                                <i class="fas fa-edit"></i>
                            </button>
                            @if(!$cat->is_system && $cat->kas_tunais_count == 0)
                                <button type="button" 
                                        class="btn btn-xs btn-outline-danger btn-delete-category" 
                                        data-id="{{ $cat->id }}"
                                        data-name="{{ $cat->name }}"
                                        title="Hapus Kategori">
                                    <i class="fas fa-trash"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $defaultType ? 8 : 9 }}" class="text-center py-4 text-muted">
                        <i class="fas fa-folder-open fa-2x mb-2 text-secondary d-block"></i>
                        Belum ada kategori yang ditambahkan.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>