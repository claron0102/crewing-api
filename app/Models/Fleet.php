<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Fleet extends Model
{
    use HasFactory;

    protected $connection = 'wpf';
    protected $table = 'tbl_fleet_management'; // Specify the table name
}
