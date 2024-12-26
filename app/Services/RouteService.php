<?php 



namespace App\Services;

use Illuminate\Support\Facades\DB;
use  App\Models\FleetSchedule;
use  App\Models\Fleet;
class RouteService
{
    public function getRouteByFleet(int $ref_bus, bool $at= null, string $date_effective = null)
    {

return DB::table('erp_wpf.tbl_fleet_management as fm')
    ->join('erp_wpf.tbl_fleet_route as fr', 'fm.ref_route', '=', 'fr.id')
    ->join('hrpmsys_02.tbl_company as comp', 'comp.id', '=', 'fm.ref_company')
    ->leftJoin('erp_management.tbl_terminal as em', 'em.id', '=', 'fr.ref_location_a')
    ->leftJoin('erp_management.tbl_terminal as em2', 'em2.id', '=', 'fr.ref_location_b')
    ->select(
        'fr.id as route_id',
        DB::raw('concat(em.name, "-", em2.name) as route_name'),
        'em.name as terminal_a',
        'em.id as terminal_a_id',
        'em2.name as terminal_b',
        'em2.id as terminal_b_id','comp.id as company_id','comp.name as company_name'
    )
    ->where('fm.id', '=', $ref_bus)
    ->get();

//    ->when($at!=null  , function ($query) use ($date_effective) {
   
//     return $query->whereRaw('DATE(ca.effective_at) BETWEEN ? AND ?', [
//             date("Y-m-d", strtotime($date_effective)),
//             now()->toDateString()
//         ])
//         ->orderBy('crew.id', 'asc')  
//         ->limit(1); 
//             }, function ($query) {
//                     return $query->where('ca.effective_at', '<=', now())
//                     ->orderBy('crew.id', 'desc') 
//                     ->limit(1); 
//             });

  
        }

        public function fleet_search( $id)
        {
            return  DB::table('erp_wpf.tbl_fleet_management')
            ->where('id', '=',$id)
            ->limit(1)
            ->pluck('bus_no')
            ->first();
            
        
        }


 public function fleet_history($fleetid)
 {

/////////////search by fleet status and route





 }




}