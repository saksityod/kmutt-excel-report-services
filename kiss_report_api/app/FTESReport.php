<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FTESReport extends Model
{
  const CREATED_AT = 'created_dttm';
  const UPDATED_AT = 'updated_dttm';
  protected $table = 'ftes';
  protected $primaryKey = NULL;
  public $incrementing = false;
  //public $timestamps = false;
  protected $guarded = array();
}
