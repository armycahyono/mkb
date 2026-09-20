<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolProfile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'npsn', 'address', 'foundation_name',
        'logo_school', 'logo_foundation',
        'letterhead_landscape', 'letterhead_portrait',
        'phone', 'email', 'website',
        'established_year', 'ownership', 'school_status',
        'sk_pendirian', 'sk_date', 'accreditation',
        'accreditation_year', 'curriculum',
        'bosp_bank_name', 'bosp_bank_branch', 'bosp_bank_address', 'bosp_account_number', 'bosp_account_name',
        'bpopp_bank_name', 'bpopp_bank_branch', 'bpopp_bank_address', 'bpopp_account_number', 'bpopp_account_name',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'sk_date' => 'date',
    ];
}
