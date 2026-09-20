<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use Illuminate\Http\Request;

class GuruController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $query = Guru::query();

        if ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('nip', 'like', "%{$search}%");
        }

        $gurus = $query->orderBy('name', 'asc')->get(['id', 'name', 'nip', 'employment_status']);

        return response()->json($gurus);
    }
}
