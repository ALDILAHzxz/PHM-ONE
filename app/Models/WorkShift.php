<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkShift extends Model
{
    protected $fillable = ['code', 'name', 'start_time', 'end_time', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** e.g. "Shift 1 (07:00–15:00)" */
    public function describe(): string
    {
        return sprintf(
            '%s (%s–%s)',
            $this->name,
            substr((string) $this->start_time, 0, 5),
            substr((string) $this->end_time, 0, 5),
        );
    }
}
