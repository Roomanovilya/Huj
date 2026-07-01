<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Approval extends Model
{
    protected $table = 'approvals';
    protected $guarded = [];

    // Так как в ТЗ есть только created_at, отключаем updated_at
    const UPDATED_AT = null;
}
