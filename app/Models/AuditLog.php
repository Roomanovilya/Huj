<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    // Явно указываем имя таблицы из ТЗ
    protected $table = 'audit_log';

    protected $guarded = [];

    const UPDATED_AT = null;
}
