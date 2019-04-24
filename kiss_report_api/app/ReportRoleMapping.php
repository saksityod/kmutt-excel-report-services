<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReportRoleMapping extends Model
{
    const CREATED_AT = 'created_dttm';
    const UPDATED_AT = 'updated_dttm';
    protected $table = 'report_role_mapping';
    protected $primaryKey = 'report_role_mapping_id';
    public $incrementing = true;

    protected $guarded = array();
    protected $hidden = ['created_by', 'updated_by', 'created_dttm', 'updated_dttm'];
}
