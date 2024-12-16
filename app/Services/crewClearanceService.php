<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class CrewClearanceService
{
   
    public function getCrewClearanceDetails(int $id, bool $at= null, string $date_effective = null,$type,$position,$conductor_data,$cleared_data)
    {


        
        
        return DB::table('tbl_crew_clearance as crew')
            ->join('tbl_crew_assignments as ca', function ($join) {
                $join->on('crew.ref_emp', '=', 'ca.ref_employee')
                     ->on('crew.ref_bus', '=', 'ca.ref_bus')
                     ->whereRaw('DATE(ca.effective_at) = crew.clearance_date');
            })
                        ->join('hrpmsys_02.employee_list as emp', 'emp.index_id', '=', 'ca.ref_employee')
                        ->join('erp_wpf.tbl_fleet_management as bus', 'ca.ref_bus', '=', 'bus.id')
                        ->select(
                            'ca.effective_at',
                            'crew.datetime_created',
                            'ca.ref_bus',
                            DB::raw('LOWER(ca.ref_classification) as ref_classification'),
                            'ca.ref_position',
                            'ca.ref_employee',
                            'bus.bus_no',
                            DB::raw('LOWER(emp.fullname) as fullname'),
                            DB::raw('LOWER(emp.positionname) as positionname'),
                            'emp.employeeids as emp_id',
                            'emp.companies as company',
                            'emp.companiesid as comp_id',
                            'crew.flag',
                            DB::raw("CASE
                                WHEN crew.flag = 2 THEN 'cleared'
                                WHEN crew.flag = 1 THEN 'pending'
                                ELSE 'revoked'
                            END as status_flag"),
                            DB::raw("CASE
                                WHEN crew.flag = 3 THEN 'The conductor clearance has been revoked due to a policy violation. The conductor is no longer authorized to operate this fleet.'
                                WHEN crew.flag = 1 THEN ''
                                ELSE ''
                            END as status_desc")
            )
            ->when($type == 0, function ($query) use ($id) {
                return $query->where('ca.ref_employee', '=', $id);
            })
            ->when(
                ($type == 1 && $position == 3 && ($cleared_data == null && $conductor_data == null)) || 
                ($type == 1 && $position == 3 && $conductor_data != 'invalid' && $cleared_data=='true'),
                function ($query) use ($id) {
                    return $query->where('ca.ref_bus', '=', $id)
                                 ->where('crew.ref_position', '=', 3);
                }
            )
            ->when(
                ($type == 1 && $position != 3 && ($cleared_data == null && $conductor_data == null)) || 
                ($type == 1 && $position != 3 && $conductor_data != 'invalid' && $cleared_data=='true'),
                function ($query) use ($id, $conductor_data) {
                    return $query->where('ca.ref_bus', '=', $id)
                                 ->whereIn('crew.ref_position', [4, 186])
                                 ->orWhere(function ($q) use ($conductor_data, $id) {
                                     $q->where('crew.ref_emp', $conductor_data)
                                       ->where('ca.ref_bus', '=', $id)
                                       ->whereIn('crew.ref_position', [4, 186]);
                                 });
                }
            )
            
            ->when($at!=null && $type == 0, function ($query) use ($date_effective) {
               
                return $query->whereRaw('DATE(ca.effective_at) BETWEEN ? AND ?', [
                        date("Y-m-d", strtotime($date_effective)),
                        now()->toDateString()
                    ])
                    ->orderBy('crew.id', 'asc') 
                    ->limit(1);  
            })
            ->when($at!=null && $type == 1 , function ($query) use ($date_effective) {
           
                return $query->whereRaw('DATE(ca.effective_at) BETWEEN ? AND ?', [
                        date("Y-m-d", strtotime($date_effective)),
                        now()->toDateString()
                    ])
                    ->orderBy('crew.id', 'asc')  
                    ->limit(1); 
            }, function ($query) {
              
                    return $query->where('ca.effective_at', '<=', now())
                    ->orderBy('crew.id', 'desc') 
                    ->limit(1); 
           
                
            })



            ->groupBy(
                'crew.ref_emp',
                'crew.flag',
                'ca.effective_at',
                'crew.datetime_created',
                'ca.ref_bus',
                'bus.bus_no',
                'ca.ref_classification',
                'ca.ref_position',
                'ca.ref_employee',
                'emp.fullname',
                'emp.positionname',
                'emp.employeeids',
                'emp.companies',
                'emp.companiesid'
            )
            ->get();
    }


    public function next_schedule( $ref_bus, $at)
    {
        return  DB::table('tbl_crew_assignments')
        ->where('ref_bus', '=',$ref_bus)  // Pass the ref_employee dynamically
        ->where(DB::raw("DATE_FORMAT(effective_at, '%Y-%m-%d')"), '>',date("Y-m-d", strtotime($at)))
        ->orderBy('id', 'asc')
        ->limit(1)
        ->pluck('effective_at')
        ->first();
        
    
    }

    public function effectivity_crew_specific($id,$at){


        return  DB::table('tbl_crew_assignments')
        ->select('effective_at', 'ref_bus')
        ->where('ref_employee', '=', $id)
        ->whereDate('effective_at', '<=', $at)
        ->orderBy('id', 'desc')
        ->limit(1)
        ->first();
    }

    public function effectivity_crew($id,$at){


        return  DB::table('tbl_crew_assignments')
        ->select('effective_at', 'ref_bus')
        ->where('ref_bus', '=', $id)
        ->whereDate('effective_at', '<=', $at)
        ->orderBy('id', 'desc')
        ->limit(1)
        ->first();
    }



    public function search_crew($id){


        return  DB::table('tbl_crew_clearance')
        ->select( '*')
        ->where('ref_emp', '=', $id)
        ->orderBy('id', 'desc')
        ->limit(1)
        ->first();
    }
}
