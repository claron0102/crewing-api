<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use  App\Models\CrewAssignment;
use  App\Models\Employee;
use  App\Models\Crew;
use App\Services\CrewClearanceService;  // Import the service class
use App\Services\RouteService;  
class CrewClearance_Controller extends Controller
{
    protected $CrewClearanceService;

    // Constructor injection
    public function __construct(CrewClearanceService $CrewClearanceService)
    {
        $this->CrewClearanceService = $CrewClearanceService;
    }
 
    public function conductor_verification($id, Request $request)
    {

 
     

        $employee =  $this->CrewClearanceService->search_crew($id);
    
        if (empty($employee)) {
          
            return $this->employee_record($id);
         
        }else{
        
 $at = $request->query('at');
        $date_effective='0000-00-00';
        $conductor_data =0;
        $cleared_data =0;
     
         if(!empty($at))  
            {

            $earliestEffectiveAt=$this->CrewClearanceService->effectivity_crew_specific($id,$at);
           
          
            if (empty($earliestEffectiveAt)) {
                return $this->employeenotfound($id);
            }
    
            $nxt_sched=$this->CrewClearanceService->next_schedule($earliestEffectiveAt->ref_bus,$at);
            $date_effective=date("Y-m-d", strtotime($earliestEffectiveAt->effective_at));   
          
        }

        $fleet = $this->CrewClearanceService->getCrewClearanceDetails($id, $at, $date_effective,0,0,$conductor_data,$cleared_data);
      
          $timestamp = now()->toISOString();  
         
            $data =array();
            if (empty($fleet)|| count($fleet)==0) {
                $employee=$this->employee_record($id);
            return $employee;
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
              $data= $dataItem;
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
                      ->whereIn('ref_position',[4,186])
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
                                  "error"=> "Nos fleet assigned to this conductor at the given time."
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
    
       
    }

//////////////////////////////////////////////////////////////////////////////////////////

//////////////////////////////////////////////////////////////////////////////////////////
public function fleet_crew($id, Request $request)
{
   /// GET v1/fleets/1234/crew?at=2024-12-01T12:00:00Z

    $data=array();
    $driver_arr=array();
    $conductor_arr=array();

    $crew = Crew::where('id', '=',$id)
    ->first();
        if (empty($crew)) {
            return $this->crewnotfound($id);
        }

        
        $date_effective='';
        $conductor_data='';
        $cleared_data='';
        $at='';
        $checkExisting=false;
        
        if(!empty($request->query())){
            $checkExisting=true; 
            $at = $request->query('at');
            $atexisting=$request->has('at')?true:false;
            $conductor_data = $request->has('conductor')?$request->query('conductor'):'invalid';
            $cleared_data = $request->has('cleared')?$request->query('cleared'):'invalid';
      
      
        }
   
   
        if(!empty($at))  
        {

        $earliestEffectiveAt=$this->CrewClearanceService->effectivity_crew($id,$at);
        if (empty($earliestEffectiveAt)) {
            return $this->employeenotfound($id);
        }

        $nxt_sched=$this->CrewClearanceService->next_schedule($earliestEffectiveAt->ref_bus,$at);
        $date_effective=date("Y-m-d", strtotime($earliestEffectiveAt->effective_at));   
      
    }

   if($checkExisting==false||$checkExisting==true&&$cleared_data=='true'||$checkExisting==true&&$atexisting=='true'){
    $driver=$this->CrewClearanceService->getCrewClearanceDetails($id, $at, $date_effective,1,3,$conductor_data,$cleared_data);
    $conductor=$this->CrewClearanceService->getCrewClearanceDetails($id, $at, $date_effective,1,4,$conductor_data,$cleared_data);
   }
else{
   return  $this->invalidRequestQuery();


}


       if (empty($driver))
       {

        $driver_arr = null;

       }
     else{
        foreach ($driver as $record) {
            if($at==null){
                $driver_arr = [
                    'id'=>$record->ref_employee,
                    'classification'=>$record->ref_classification,
                    'company'=>[
                            'id'=>$record->comp_id,
                            'name'=>$record->company
                    ],
                    'clearance_status'=>$record->status_flag,
                 
                ];
                if($record->flag==1)
                {
                $driver_arr['clearance_reason']="The driver has pending clearance request.";
                }
              }
              else
              {
                 $nextAssignment='';
                  
                  if (date("Y-m-d",strtotime($record->effective_at))==date("Y-m-d", strtotime($at))) {
         
                    $nextAssignment = CrewAssignment::where('effective_at', '=', "{$record->effective_at}")
                    ->where('ref_bus',"{$record->ref_bus}")
                    ->where('ref_position',3)
                    ->orderBy('effective_at', 'desc')
                    ->first();
  
                    $driver_arr = [
                        'id'=>$record->ref_employee,
                        'classification'=>$record->ref_classification,
                        'company'=>[
                                'id'=>$record->comp_id,
                                'name'=>$record->company
                        ],
                        'clearance_status'=>$record->flag=3?'cleared':$record->status_flag,
                         'assigned_at'=>$record->effective_at,
                        'effective_at'=>$nxt_sched
                    ];


                  }
              
                  if (date("Y-m-d",strtotime($record->effective_at))< date("Y-m-d", strtotime($at))) {

                      
                    $nextAssignment = CrewAssignment::whereBetween('effective_at', [DATE("Y-m-d", strtotime($record->effective_at)),  date("Y-m-d", strtotime($at))])
                    ->where('ref_bus',"{$record->ref_bus}")
                    ->whereIn('ref_position',[3])
                    ->orderBy('id', 'desc')
                    
                    ->first();


                 
                        if($nextAssignment->ref_employee==$record->ref_employee){
                            $driver_arr = [
                                'id'=>$record->ref_employee,
                                'ref_classification'=>$record->ref_classification,
                                'company'=>[
                                    'id'=>$record->comp_id,
                                    'name'=>$record->company
                                     ],
                                'clearance_status'=>$record->flag=3?'cleared':$record->status_flag,
                                 'assigned_at'=>$record->effective_at,
                                'effective_at'=>$nxt_sched
                            ];
                        }
                        else{
                            $driver_arr =null;
                        }
                    }

            if ($nextAssignment==null){

                $driver_arr = null;
            }
              
              ///END DRIVER DATA
                }


            }
     }
        
   
if(empty($conductor)){

    $conductor_arr = null;

}
else{

    foreach ($conductor as $record) {
        if($at==null){
            $conductor_arr = [
                'id'=>$record->ref_employee,
                'classification'=>$record->ref_classification,
                'company'=>[
                    'id'=>$record->comp_id,
                    'name'=>$record->company
                     ],
                'clearance_status'=>$record->status_flag,
             
            ];
            if($record->flag==1)
            {
            $conductor_arr['clearance_reason']="The driver has pending clearance request.";
            }
          }
          


          else
          {
             $nextAssignment='';
              
              if (date("Y-m-d",strtotime($record->effective_at))==date("Y-m-d", strtotime($at))) {
     
                $nextAssignment = CrewAssignment::where('effective_at', '=', "{$record->effective_at}")
                ->where('ref_bus',"{$record->ref_bus}")
                ->whereIn('ref_position',[4,186])
                ->orderBy('effective_at', 'desc')
                ->first();

                $conductor_arr = [
                    'id'=>$record->ref_employee,
                    'classification'=>$record->ref_classification,
                    'company'=>[
                        'id'=>$record->comp_id,
                        'name'=>$record->company
                        ],
                    'clearance_status'=>$record->flag=3?'cleared':$record->status_flag,
                     'assigned_at'=>$record->effective_at,
                    'effective_at'=>$nxt_sched
                ];


              }
              
              if (date("Y-m-d",strtotime($record->effective_at))< date("Y-m-d", strtotime($at))) {

                  
                $nextAssignment = CrewAssignment::whereBetween('effective_at', [DATE("Y-m-d", strtotime($record->effective_at)),  date("Y-m-d", strtotime($at))])
                ->where('ref_bus',"{$record->ref_bus}")
                ->whereIn('ref_position',[4,186])
                ->orderBy('id', 'desc')
                
                ->first();

            
             
                    if($nextAssignment->ref_employee==$record->ref_employee){
                        $conductor_arr = [
                            'id'=>$record->ref_employee,
                            'classification'=>$record->ref_classification,
                            'company'=>[
                                'id'=>$record->comp_id,
                                'name'=>$record->company
                                 ],
                            'clearance_status'=>$record->flag=3?'cleared':$record->status_flag,
                             'assigned_at'=>$record->effective_at,
                            'effective_at'=>$nxt_sched
                        ];
                    }
                    else{
                        $conductor_arr  = null;
                    }
                }

        if ($nextAssignment==null){

            $conductor_arr = null;
        }
          
          ///END CONDUCTOR DATA
            }


        }


}
      


        
        $dataItem = [
            'timestamp' => now()->toDateTimeString (),
            'driver'=> empty($driver_arr)?null:$driver_arr,
            "conductor"=>empty($conductor_arr)?null:$conductor_arr,
        ];


      $data=$dataItem;
 
      return response()->json($data);




}

    private function employee_record($emp_id){
     
        $employee =$this->employee_record_list($emp_id);

        if(!empty($employee)){

            return response()->json([
                'timestamp' => now()->toDateTimeString(),
                "status"=>"unassigned",
                "fleet"=> null,
                "error"=> "no fleet assigned to this conductor at the given time."
            ],);
              }
            else
                {
                    return response()->json([
                        'error' =>'not found',
                        'message' => 'conductor with ID '.$emp_id.' does not exist.' ], 404);
                }
    }


    private function employeenotfound($id){
        return response()->json([
               'error' =>'not found',
               'message' => 'conductor with ID '.$id.' does not exist.' ], 404);
       }
  private function invalidRequestQuery(){
    return response()->json([
        'error' => 'invalid parameter',
        'message' => 'please check the provided parameters.',
    ], 400);
       }
   
    private function employee_record_list($emp_id){
       $employee = Employee::where('index_id', '=',$emp_id)
           ->orderBy('index_id', 'asc')
           ->first();
           return $employee;
           }
 
    private function crewnotfound($id){
       return response()->json([
          'error' =>'not Found',
          'message' => 'conductor with ID '.$id.' does not exist.'], 404);
        }


}
