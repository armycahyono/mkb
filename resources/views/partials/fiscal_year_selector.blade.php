@php
    $currentYear = (int) date('Y');
    $activeYear = $activeFiscalYear ?? \App\Helpers\FiscalYearHelper::getActiveYear();
    $yearsList = $availableFiscalYears ?? \App\Helpers\FiscalYearHelper::getAvailableYears();
    $isCurrent = ($activeYear === $currentYear);
@endphp

<li class="nav-item dropdown fiscal-year-selector" style="margin-right: 8px;">
    <a href="#" class="nav-link dropdown-toggle d-flex align-items-center py-1 px-2 rounded border" 
       data-bs-toggle="dropdown" 
       data-toggle="dropdown" 
       aria-expanded="false" 
       title="Ganti Tahun Anggaran Aktif"
       style="background: #f8fafc; font-size: 0.84rem; font-weight: 600; color: #334155;">
        <i class="fas fa-calendar-alt text-primary me-1 mr-1" style="font-size: 0.95rem;"></i>
        <span class="d-none d-sm-inline me-1 mr-1">Tahun Anggaran</span>
        <span class="d-inline d-sm-none me-1 mr-1">TA</span>
        <span class="text-dark font-weight-bold fw-bold">{{ $activeYear }}</span>
        @if($isCurrent)
            <span class="badge bg-success badge-success ms-1 ml-1" style="font-size: 0.65rem; padding: 2px 6px;">Tahun Berjalan</span>
        @else
            <span class="badge bg-info badge-info ms-1 ml-1 text-white" style="font-size: 0.65rem; padding: 2px 6px;">Tahun {{ $activeYear }}</span>
        @endif


    </a>

    <ul class="dropdown-menu dropdown-menu-end shadow border-0 py-2" style="min-width: 250px; font-size: 0.85rem; z-index: 1060;">
        <li class="dropdown-header text-uppercase fw-bold text-muted px-3 py-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">
            <i class="fas fa-calendar-check me-1 mr-1 text-primary"></i> Pilih Tahun Anggaran
        </li>
        <li><hr class="dropdown-divider my-1"></li>
        @foreach($yearsList as $yr)
            @php
                $isSel = ((int)$yr === (int)$activeYear);
                $isNow = ((int)$yr === $currentYear);
            @endphp
            <li>
                <a class="dropdown-item d-flex align-items-center justify-content-between px-3 py-2 btn-switch-fiscal-year {{ $isSel ? 'active fw-bold' : '' }}" 
                   href="javascript:void(0)" 
                   data-year="{{ $yr }}">
                    <div class="d-flex align-items-center">
                        <i class="fas {{ $isSel ? 'fa-check-circle text-success' : 'fa-circle text-muted opacity-25' }} me-2 mr-2" style="font-size: 0.85rem;"></i>
                        <span>Tahun Anggaran {{ $yr }}</span>
                    </div>
                    @if($isNow)
                        <span class="badge {{ $isSel ? 'bg-light text-dark' : 'bg-success text-white' }}" style="font-size: 0.68rem;">Berjalan</span>
                    @endif
                </a>
            </li>
        @endforeach
        <li><hr class="dropdown-divider my-1"></li>
        <li class="px-3 py-1 small text-muted" style="font-size: 0.74rem;">
            <i class="fas fa-info-circle me-1 mr-1 text-info"></i> Mengubah tahun anggaran akan menampilkan data pembukuan tahun tersebut. Anda tetap dapat menyelesaikan kegiatan/transaksi yang belum rampung.
        </li>
    </ul>
</li>

<form id="formSwitchFiscalYear" action="{{ route('fiscal-year.switch') }}" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="year" id="inputSwitchFiscalYear">
</form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.btn-switch-fiscal-year').forEach(function(el) {
            el.addEventListener('click', function(e) {
                e.preventDefault();
                let yr = this.getAttribute('data-year');
                if (!yr) return;
                
                let form = document.getElementById('formSwitchFiscalYear');
                let input = document.getElementById('inputSwitchFiscalYear');
                if (form && input) {
                    input.value = yr;
                    form.submit();
                }
            });
        });
    });
</script>
