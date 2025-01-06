<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrewClearance extends Model
{
    use HasFactory;

    protected $table = 'tbl_crew_clearance'; // Specify the table name

  
    protected $fillable = [
        'ref_bus', 'company_id', 'clearance_date', 'ref_emp', 'ref_position',
        'ref_checklist', 'remarks', 'datetime_created', 'expired_at', 
        'created_by', 'flag', 'is_tcr', 'override_flag'
    ];


    public function crewAssignment()
{
    return $this->belongsTo(CrewAssignment::class, 'ref_emp', 'ref_employee');
}
}
