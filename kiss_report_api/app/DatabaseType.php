<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DatabaseType extends Model
{
    protected $table = 'database_type';
    protected $primaryKey = 'database_type_id';
    public $incrementing = true;

    public $timestamps = false;
    //protected $guarded = array();
    protected $fillable = array('database_type');
}
