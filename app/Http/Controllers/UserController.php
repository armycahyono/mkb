<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    /**
     * Display a listing of the resource or return DataTables JSON.
     */
    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $query = User::with(['guru.activeJabatans.jabatan'])->orderBy('name', 'asc');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('name_display', function (User $row) {
                    $isCurrent = $row->id === Auth::id();
                    $badge = $isCurrent ? ' <span class="badge badge-primary px-2 py-1 ml-1" style="font-size:0.7rem;"><i class="fas fa-user-check mr-1"></i>Akun Anda</span>' : '';
                    $html = '<div class="font-weight-bold text-dark mb-0" style="font-size:0.88rem;">'.e($row->name).$badge.'</div>';

                    if ($row->guru) {
                        $activeJabatans = $row->guru->activeJabatans->pluck('jabatan.name')->filter()->join(', ');
                        $jabatanBadge = $activeJabatans ? ' <span class="badge badge-light border text-secondary font-weight-normal" style="font-size:0.7rem;">'.$activeJabatans.'</span>' : '';
                        $html .= '<div class="text-muted small mt-1" style="font-size:0.75rem;"><i class="fas fa-chalkboard-teacher mr-1 text-info"></i>Guru: <a href="'.route('guru.show', $row->guru_id).'" class="text-secondary font-weight-500 text-decoration-none">'.e($row->guru->full_name_with_title).'</a>'.$jabatanBadge.'</div>';
                    }

                    return $html;
                })
                ->addColumn('role_display', function (User $row) {
                    return $row->role_badge;
                })
                ->addColumn('telegram_display', function (User $row) {
                    return $row->telegram_badge;
                })
                ->addColumn('created_display', function (User $row) {
                    return $row->created_at ? '<span class="font-tabular text-muted small">'.$row->created_at->format('d/m/Y H:i').'</span>' : '-';
                })
                ->addColumn('action', function (User $row) {
                    $isCurrent = $row->id === Auth::id();

                    $btn = '<button type="button" class="btn btn-xs btn-warning btn-edit-user mr-1" '
                        .'data-id="'.$row->id.'" '
                        .'data-guru-id="'.($row->guru_id ?? '').'" '
                        .'data-name="'.e($row->name).'" '
                        .'data-username="'.e($row->username).'" '
                        .'data-email="'.e($row->email).'" '
                        .'data-role="'.e($row->role ?? User::ROLE_BENDAHARA_BOS).'" '
                        .'data-has-telegram="'.($row->telegram_chat_id ? '1' : '0').'" '
                        .'data-telegram-user="'.e($row->telegram_username ?? '').'" '
                        .'data-update-url="'.route('user.update', $row->id).'" '
                        .'title="Edit User"><i class="fas fa-edit"></i></button>';

                    if (! $isCurrent) {
                        $btn .= '<button type="button" class="btn btn-xs btn-danger btn-delete-user" '
                            .'data-url="'.route('user.destroy', $row->id).'" '
                            .'data-name="'.e($row->name).'" '
                            .'title="Hapus User"><i class="fas fa-trash"></i></button>';
                    } else {
                        $btn .= '<button type="button" class="btn btn-xs btn-secondary" disabled title="Tidak dapat menghapus akun sendiri"><i class="fas fa-trash"></i></button>';
                    }

                    return '<div class="text-nowrap text-center">'.$btn.'</div>';
                })
                ->rawColumns(['name_display', 'role_display', 'telegram_display', 'created_display', 'action'])
                ->make(true);
        }

        $gurus = Guru::where('is_active', true)
            ->with(['activeJabatans.jabatan'])
            ->orderBy('name', 'asc')
            ->get();

        return view('user.index', compact('gurus'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'guru_id' => 'nullable|exists:gurus,id',
            'name' => 'required|string|max:255',
            'username' => 'required|string|alpha_dash|max:50|unique:users,username',
            'email' => 'required|string|email|max:255|unique:users,email',
            'role' => 'nullable|in:super_admin,admin,kepala_sekolah,bendahara_bos,bendahara,bendahara_bpopp,waka_sarpras,team_bosp,team_bos,team_bpopp',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $guruId = $request->filled('guru_id') ? (int) $request->input('guru_id') : null;

        $user = User::create([
            'guru_id' => $guruId,
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'role' => $validated['role'] ?? User::ROLE_BENDAHARA_BOS,
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'message' => "User '{$user->name}' berhasil ditambahkan.",
            'user' => $user,
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'guru_id' => 'nullable|exists:gurus,id',
            'name' => 'required|string|max:255',
            'username' => 'required|string|alpha_dash|max:50|unique:users,username,'.$user->id,
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'role' => 'nullable|in:super_admin,admin,kepala_sekolah,bendahara_bos,bendahara,bendahara_bpopp,waka_sarpras,team_bosp,team_bos,team_bpopp',
            'password' => 'nullable|string|min:6|confirmed',
            'unlink_telegram' => 'nullable',
        ]);

        $userData = [
            'guru_id' => $request->filled('guru_id') ? (int) $request->input('guru_id') : null,
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
        ];

        if (! empty($validated['role'])) {
            $userData['role'] = $validated['role'];
        }

        if (! empty($validated['password'])) {
            $userData['password'] = Hash::make($validated['password']);
        }

        if ($request->boolean('unlink_telegram')) {
            $userData['telegram_chat_id'] = null;
            $userData['telegram_username'] = null;
        }

        $user->update($userData);

        return response()->json([
            'message' => "Data user '{$user->name}' berhasil diperbarui.",
            'user' => $user,
        ]);
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user): JsonResponse
    {
        if ($user->id === Auth::id()) {
            return response()->json([
                'message' => 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif digunakan.',
            ], 422);
        }

        if (User::count() <= 1) {
            return response()->json([
                'message' => 'Tidak dapat menghapus pengguna terakhir pada sistem.',
            ], 422);
        }

        $name = $user->name;
        $user->delete();

        return response()->json([
            'message' => "User '{$name}' berhasil dihapus.",
        ]);
    }
}
