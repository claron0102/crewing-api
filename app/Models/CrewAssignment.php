<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrewAssignment extends Model
{
    protected $table = 'tbl_crew_assignments';

   
    public $timestamps = true;

  
    protected $fillable = [
        'ref_bus',
        'ref_employee',
        'ref_position',
        'ref_classification',
        'ref_crew',
        'effective_at',
    ];

    // // Define any relationships (if applicable)
    // public function employee()
    // {
    //     return $this->belongsTo(Employee::class, 'ref_employee', 'id'); // Assuming 'Employee' is another model
    // }

    // public function position()
    // {
    //     return $this->belongsTo(Position::class, 'ref_position', 'id'); // Assuming 'Position' is another model
    // }

    // public function classification()
    // {
    //     return $this->belongsTo(Classification::class, 'ref_classification', 'id'); // Assuming 'Classification' is another model
    // }

    // // If you have a Crew model
    // public function crew()
    // {
    //     return $this->belongsTo(CrewClearance::class, 'ref_crew', 'id'); // Assuming 'Crew' is another model
    // } //

    public function crewClearance()
{
    return $this->hasMany(CrewClearance::class, 'ref_employee', 'ref_emp');
}
}
