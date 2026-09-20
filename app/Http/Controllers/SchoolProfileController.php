<?php

namespace App\Http\Controllers;

use App\Models\SchoolProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SchoolProfileController extends Controller
{
    public function index()
    {
        $profile = SchoolProfile::first();
        if (! $profile) {
            $profile = new SchoolProfile;
        }

        return view('master.school-profile.index', compact('profile'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'npsn' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'foundation_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|string|max:255',
            'established_year' => 'nullable|digits:4',
            'ownership' => 'nullable|string|max:50',
            'school_status' => 'nullable|string|max:50',
            'sk_pendirian' => 'nullable|string|max:255',
            'sk_date' => 'nullable|date',
            'accreditation' => 'nullable|string|max:10',
            'accreditation_year' => 'nullable|digits:4',
            'curriculum' => 'nullable|string|max:255',
            'logo_school' => 'nullable|file|mimes:jpeg,png,jpg,webp,svg,gif|max:10240',
            'logo_foundation' => 'nullable|file|mimes:jpeg,png,jpg,webp,svg,gif|max:10240',
            'letterhead_landscape' => 'nullable|file|mimes:jpeg,png,jpg,webp,svg,gif|max:10240',
            'letterhead_portrait' => 'nullable|file|mimes:jpeg,png,jpg,webp,svg,gif|max:10240',
            'bosp_bank_name' => 'nullable|string|max:100',
            'bosp_bank_branch' => 'nullable|string|max:100',
            'bosp_bank_address' => 'nullable|string|max:255',
            'bosp_account_number' => 'nullable|string|max:50',
            'bosp_account_name' => 'nullable|string|max:150',
            'bpopp_bank_name' => 'nullable|string|max:100',
            'bpopp_bank_branch' => 'nullable|string|max:100',
            'bpopp_bank_address' => 'nullable|string|max:255',
            'bpopp_account_number' => 'nullable|string|max:50',
            'bpopp_account_name' => 'nullable|string|max:150',
        ], [
            'logo_school.file' => 'Berkas Logo Sekolah tidak valid.',
            'logo_school.mimes' => 'Format Logo Sekolah harus berupa gambar (PNG, JPG, JPEG, WEBP, SVG).',
            'logo_school.max' => 'Ukuran Logo Sekolah maksimal 10 MB.',
            'logo_foundation.file' => 'Berkas Logo Yayasan tidak valid.',
            'logo_foundation.mimes' => 'Format Logo Yayasan harus berupa gambar (PNG, JPG, JPEG, WEBP, SVG).',
            'logo_foundation.max' => 'Ukuran Logo Yayasan maksimal 10 MB.',
            'letterhead_portrait.file' => 'Berkas Kop Surat Portrait tidak valid.',
            'letterhead_portrait.mimes' => 'Format Kop Surat Portrait harus berupa gambar (PNG, JPG, JPEG, WEBP, SVG).',
            'letterhead_portrait.max' => 'Ukuran Kop Surat Portrait maksimal 10 MB.',
            'letterhead_landscape.file' => 'Berkas Kop Surat Landscape tidak valid.',
            'letterhead_landscape.mimes' => 'Format Kop Surat Landscape harus berupa gambar (PNG, JPG, JPEG, WEBP, SVG).',
            'letterhead_landscape.max' => 'Ukuran Kop Surat Landscape maksimal 10 MB.',
        ]);

        $profile = SchoolProfile::first();
        if (! $profile) {
            $profile = new SchoolProfile;
        }

        // Handle file uploads & removals
        foreach (['logo_school', 'logo_foundation', 'letterhead_landscape', 'letterhead_portrait'] as $field) {
            if ($request->hasFile($field)) {
                // Delete old file if exists
                if ($profile->$field && Storage::disk('public')->exists($profile->$field)) {
                    Storage::disk('public')->delete($profile->$field);
                }
                $profile->$field = $request->file($field)->store('school', 'public');
            } elseif ($request->boolean('remove_'.$field)) {
                if ($profile->$field && Storage::disk('public')->exists($profile->$field)) {
                    Storage::disk('public')->delete($profile->$field);
                }
                $profile->$field = null;
            }
            // Ensure fill() never overwrites existing media fields when no new file is provided
            unset($validated[$field]);
        }

        $profile->fill($validated);
        $profile->save();

        if (function_exists('activity')) {
            activity()
                ->performedOn($profile)
                ->withProperties(['name' => $profile->name])
                ->log('Memperbarui profil sekolah');
        }

        $activeTab = $request->input('active_tab');
        $redirectUrl = route('school.index');
        if ($activeTab && str_starts_with($activeTab, '#')) {
            $redirectUrl .= $activeTab;
        }

        return redirect($redirectUrl)->with('success', 'Profil sekolah berhasil diperbarui.');
    }
}
