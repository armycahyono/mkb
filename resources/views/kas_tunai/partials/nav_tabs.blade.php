<div class="card mb-3 border-0 shadow-sm">
    <div class="card-body p-2 d-flex justify-content-between align-items-center flex-wrap" style="background-color: #f8fafc; border-radius: 8px;">
        <ul class="nav nav-pills" style="gap: 6px;">
            <li class="nav-item">
                <a class="nav-link {{ ($activeTab ?? '') === 'buku-kas' ? 'active bg-primary font-weight-bold shadow-xs' : 'text-secondary font-weight-500' }} py-2 px-3" 
                   href="{{ route($routePrefix . '.kas-tunai.index') }}" 
                   style="border-radius: 6px; font-size: 0.875rem;">
                    <i class="fas fa-wallet mr-1"></i> Buku Kas Tunai
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ ($activeTab ?? '') === 'kasbon' ? 'active bg-warning text-dark font-weight-bold shadow-xs' : 'text-secondary font-weight-500' }} py-2 px-3" 
                   href="{{ route($routePrefix . '.kas-tunai.kasbon') }}" 
                   style="border-radius: 6px; font-size: 0.875rem;">
                    <i class="fas fa-user-clock mr-1"></i> Tanggungan Kasbon & Talangan
                    @if(($countPanjarPending ?? 0) > 0)
                        <span class="badge {{ ($activeTab ?? '') === 'kasbon' ? 'bg-danger text-white' : 'badge-danger' }} ml-1" style="font-size: 0.75rem; vertical-align: middle;">
                            {{ $countPanjarPending }}
                        </span>
                    @endif
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ ($activeTab ?? '') === 'belanja-barang' ? 'active bg-info text-white font-weight-bold shadow-xs' : 'text-secondary font-weight-500' }} py-2 px-3" 
                   href="{{ route($routePrefix . '.kas-tunai.belanja-barang') }}" 
                   style="border-radius: 6px; font-size: 0.875rem;">
                    <i class="fas fa-boxes mr-1"></i> Rekap Belanja Barang Tunai
                </a>
            </li>
        </ul>

        <div class="d-none d-md-flex align-items-center pr-2">
            <span class="badge bg-white text-muted border px-2 py-1" style="font-size: 0.78rem;">
                <i class="fas fa-shield-alt text-success mr-1"></i> Kas Operasional Mandiri {{ $fundingSource ?? 'BOSP' }}
            </span>
        </div>
    </div>
</div>
