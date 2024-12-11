<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use  App\Models\CrewAssignment;
use  App\Models\Employee;
class CrewClearance_Controller extends Controller
{
  
    public function index()
    {
        $CrewClearance = CrewClearance::get();
        return response()->json($CrewClearance);
    }

    public function show($id, Request $request)
    {

 
        $employee = $this->employee_record_list($id);
        if (empty($employee)) {
            return $this->employeenotfound($id);
        }
    
        // Get 'at' query parameter
        $at = $request->query('at');
        $date_effective='0000-00-00';
         if(!empty($at))  
            {
                $earliestEffectiveAt = DB::table('tbl_crew_assignments')
                ->select('effective_at', 'ref_bus')
                ->where('ref_employee', '=', $id)
                ->whereDate('effective_at', '<=', $at)
                ->orderBy('id', 'desc')
                ->limit(1)
                ->first();
        
            if (empty($earliestEffectiveAt)) {
                return $this->employeenotfound($id);
            }
    
        $nxt_sched = DB::table('tbl_crew_assignments')
        ->where('ref_bus', '=',$earliestEffectiveAt->ref_bus)  // Pass the ref_employee dynamically
        ->where(DB::raw("DATE_FORMAT(effective_at, '%Y-%m-%d')"), '>',date("Y-m-d", strtotime($at)))
        ->orderBy('id', 'asc')
        ->limit(1)
        ->pluck('effective_at')
        ->first();
    

         $date_effective=date("Y-m-d", strtotime($earliestEffectiveAt->effective_at));   
          
        }

      $fleet = DB::table('tbl_crew_clearance as crew')
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
            ->where('ca.ref_employee', '=', $id)

           
            ->when($at, function ($query) use ($at, $date_effective) {
                // This closure runs if $at is truthy
                return $query->whereRaw('DATE(ca.effective_at) BETWEEN ? AND ?', [
                        date("Y-m-d", strtotime($date_effective)), // Start date
                        now()->toDateString() // End date (current date)
                    ])
                    ->orderBy('crew.id', 'asc') // Order by crew.id ascending
                    ->limit(1); // Limit to 1 result
            }, function ($query) {
                // This closure runs if $at is falsy
                return $query->where('ca.effective_at', '<=', now()) // Filter records with effective_at <= now
                    ->orderBy('crew.id', 'desc') // Order by crew.id descending
                    ->limit(1); // Limit to 1 result
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
   

    
      $timestamp = now()->toISOString();  
   
      $data =array();
      if (empty($fleet)) {
         $employee=$this->employee_record($id);
       
      }

      foreach ($fleet as $record) {
          $fleetData = [
              'id'=>$record->ref_bus,
              'bus_no'=>$record->bus_no,
              'company'=>[
                  'id'=>$record->comp_id,
                  'name'=>$record->company


              ]
        ];


       if($at==null){

          $status = $record->ref_position == 3 ? 'Invalid' : $record->status_flag;
          $dataItem = [
              'timestamp' => now()->toDateTimeString(),
                  'status' => $status,
                  'classification'=>$record->ref_classification,
                  'assigned_at'=>$record->effective_at,
                  'effective_at'=>null,
                  'fleet' => $fleetData,
              ];
              if ($record->flag == 3) {
                  if ($record->ref_position != 3) {
                      $dataItem['error'] = $record->status_desc;
                  } else {
                      $dataItem['error'] = 'Invalid Position';
                  }
              }
              else{
                  if ($record->ref_position == 3) {
                      $dataItem['error'] = 'Invalid Position';
                  }
              }
              $data[] = $dataItem;
              }
              else
              {
                  $nextAssignment='';
                  
                  if (date("Y-m-d",strtotime($record->effective_at))==date("Y-m-d", strtotime($at))) {
                      $nextAssignment = CrewAssignment::where('effective_at', '=', "{$record->effective_at}")
                      ->where('ref_bus',"{$record->ref_bus}")
                      ->orderBy('effective_at', 'desc')
                      ->first();

                      $dataItem = [
                        'timestamp' => now()->toDateTimeString(),
                        'status' => $record->effective_at >= date("Y-m-d", strtotime($at)) && $record->effective_at <=  $nextAssignment->effective_at ? "cleared":'Expired',
                        'classification'=>$record->ref_classification,
                        'assigned_at'=>$record->effective_at,
                        'effective_at'=>$nxt_sched,
                        'fleet' => $fleetData,
                        ];

                  }
                   
                      if (date("Y-m-d",strtotime($record->effective_at))< date("Y-m-d", strtotime($at))) {
                      $nextAssignment = CrewAssignment::whereBetween('effective_at', [DATE("Y-m-d", strtotime($record->effective_at)),  date("Y-m-d", strtotime($at))])
                      ->where('ref_bus',"{$record->ref_bus}")
                      ->orderBy('id', 'desc')
                      ->first();

                          if($nextAssignment->ref_employee==$record->ref_employee){
                              $dataItem = [
                                'timestamp' => now()->toDateTimeString(),
                                'status' => 'cleared',
                                'classification'=>$record->ref_classification,
                                'assigned_at'=>$record->effective_at,
                                'effective_at'=>$nxt_sched,
                                'fleet' => $fleetData,
                                
                                ];
                          }
                          else{
                              $dataItem = [
                                  'timestamp' => now()->toDateTimeString(),
                                  "status"=>"unassigned",
                                  "fleet"=> null,
                                  "error"=> "No fleet assigned to this conductor at the given time."
                                ];
                          }
                      }

              if ($nextAssignment==null){

                  $dataItem = [
                      'timestamp' => now()->toDateTimeString(),
                          'status' => 'unassigned',
                          'fleet' =>  null,
                          "message"=> "No fleet assigned to this conductor at the given time."
                    ];
              }
              $data = $dataItem;
         }   
    
    }
    return response()->json($data);

    }



 private function employeenotfound($id){

     return response()->json([
            'error' =>'Not Found',
            'message' => 'Conductor with ID '.$id.' does not exist.'
        ], 404);
 }
    private function employee_record_list($emp_id){
        $employee = Employee::where('index_id', '=',$emp_id)
        ->orderBy('index_id', 'asc')
        ->first();

        return $employee;
            
        }
       

    private function employee_record($emp_id){
        $employee = Employee::where('index_id', '=',$emp_id)
        ->orderBy('index_id', 'asc')
        ->first();

        if(!empty($employee)){

            return response()->json([
                'timestamp' => now()->toDateTimeString(),
                "status"=>"unassigned",
                "fleet"=> null,
                "error"=> "No fleet assigned to this conductor at the given time."
            ],);

            
        }
        else
        {
            return response()->json([
                'error' =>'Not Found',
                'message' => 'Conductor with ID '.$emp_id.' does not exist.'
            ], 404);

         
        }
  
    }

  

 
}
