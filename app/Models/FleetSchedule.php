<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class FleetSchedule extends Model
{
    use HasFactory;

    protected $connection = 'fleet';
    protected $table = 'tbl_dispatching_list'; // Specify the table name
}
