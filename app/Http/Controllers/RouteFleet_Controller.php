<?php



namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use  App\Models\CrewAssignment;
use  App\Models\Employee;
use  App\Models\Fleet;
use  App\Models\Crew;
use App\Services\RouteService; 

class RouteFleet_Controller extends Controller
{
    protected $routeService;

    // Constructor injection
    public function __construct(RouteService $routeService)
    {
        $this->routeService = $routeService;
    }

    public function fleet_route($id, Request $request){
        

        $fleet_details_arr=array();
        $at='';
        $fleet =  $this->routeService->fleet_search($id);
        $checkExisting=false; 
        if(!empty($request->query())){
            $checkExisting=true; 
            $at = $request->query('at');
      
      
        }



        if (empty($fleet)) {
            return $this->fleet_record($id);
        }
        else
        {
         $fleet_details = $this->routeService->getRouteByFleet($id);
           
            if (empty($fleet_details) || count($fleet_details)==0)
            {
     
              return  $this->responsedefault();
       
            }
          else{
             foreach ($fleet_details as $record) {

                        if($at==null){
                            $fleet_details_arr = [

                                'timestamp' => now()->toDateTimeString(),
                            
                                    'origin'=>[
                                        'id'=>$record->terminal_a_id,
                                        'name'=>$record->terminal_a,
                                        'type'=>'origin',
                                    ],
                                    'destination'=>[
                                        'id'=>$record->terminal_b_id,
                                        'name'=>$record->terminal_b,
                                        'type'=>'destination',
                                    ]


                               
                            
                            ];
                            
                        }
                        else{

                            $fleet_details_arr=null; 
                        }
                  
     
     
                 }
          }
             
            $data=$fleet_details_arr;
            return response()->json($data);

        }

 
       
        }



 private function fleet_record($id){
     
            $fleet_list =$this->fleet_list($id);
    
            if(!empty($fleet_list)){
             
                return  $this->responsedefault();
                  }
                else
                    {

                return response()->json([
                   'error' =>'not found',
                   'message' => 'Fleet with ID '.$id.' does not exist' ], 404);
                    }
       
                }
    
     private function fleet_list($id){
       $fleet_list = Fleet::where('id', '=',$id)
        ->first();
         return $fleet_list;
          }


private function responsedefault(){
    return response()->json([
        'timestamp' => now()->toDateTimeString(),
        "route"=> null,
        "error"=> "The route for this fleet is not yet assigned."
    ],);

}


    }
