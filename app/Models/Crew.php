<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Crew extends Model
{
    protected $table = 'tbl_crew';

   
    public $timestamps = true;

  
    protected $fillable = [
 'id','ref_company','ref_bus','ref_driver','ref_conductor','has_clearance_driver','has_clearance_conductor','has_checklist_driver','has_checklist_conductor','ref_reliever_driver','ref_reliever_conductor','has_clearance_reliever_driver','has_clearance_reliever_conductor','has_checklist_reliever_driver','has_checklist_reliever_conductor','extra_driver','extra_conductor','has_extra_clearance_driver','has_extra_clearance_conductor','reserve_driver','reserve_conductor','has_reserve_clearance_driver','has_reserve_clearance_conductor','datetime_created','created_by','on_hold','flag'
    ];
}
