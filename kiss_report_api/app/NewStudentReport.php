<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NewStudentReport extends Model
{
  const CREATED_AT = 'created_dttm';
  const UPDATED_AT = 'updated_dttm';
  protected $table = 'new_student_report';
  protected $primaryKey = NULL;
  public $incrementing = false;
  //public $timestamps = false;
  protected $guarded = array();
}
