<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use DB;
use Validator;
use Exception;
use App\ReportRoleMapping;
use App\Http\Requests;
use App\Http\Controllers\Controller;

class ReportOperationPermissionsController extends Controller
{

    public function index()
    {
        $mapp = DB::select("
          SELECT report_role_mapping_id
          , portlet_name
          , role_id
          , role_id as role_name
          , is_import
          , is_export
          FROM report_role_mapping ");

        $connect = DB::connection('mysql_lportal');

        $role_ = $connect->select("
          SELECT roleId
          , name
          FROM Role_ ");

        foreach ($mapp as $m) {
          foreach ($role_ as $r) {
            if ($m->role_id == $r->roleId){
              $m->role_name = $r->name;
            }
          }// end role_
        }// end mapp

        return response()->json($mapp);
    }

    public function role_list()
    {
        $connect = DB::connection('mysql_lportal');

        $role_ = $connect->select("
          SELECT roleId
          , name
          FROM Role_");

        return response()->json($role_);
    }

    public function show($report_role_mapping_id)
  	{
  		try {
  			$mapp = ReportRoleMapping::findOrFail($report_role_mapping_id);
  		} catch (ModelNotFoundException $e) {
  			return response()->json(['status' => 404, 'data' => 'Report role mapping not found.']);
  		}
      $connect = DB::connection('mysql_lportal');

      $role_ = $connect->select("
        SELECT roleId
        , name
        FROM Role_");

        foreach ($role_ as $r) {
          if ($mapp->role_id == $r->roleId){
            $mapp->role_name = $r->name;
          }
        }// end role_

  		return response()->json($mapp);
  	}

    public function store(Request $request)
    {
        $check_data = DB::table('report_role_mapping')
          ->where('portlet_name', $request->portlet_name)
          ->where('role_id', $request->role_id)
          ->count();

        if ($check_data > 0){
          return response()->json(['status' => 404, 'data' => 'Report name and role are duplicate.']);
        }

        $validator = Validator::make($request->all(), [
    			'portlet_name' => 'required|max:100',
          'role_id' => 'required|integer',
    			'is_import' => 'required|boolean',
    			'is_export' => 'required|boolean',
    		]);

        if ($validator->fails()) {
    			return response()->json(['status' => 400, 'data' => $validator->errors()]);
    		} else {
    			$item = new ReportRoleMapping;
          $item->portlet_name = $request->portlet_name;
          $item->role_id = $request->role_id;
          $item->is_import = $request->is_import;
          $item->is_export = $request->is_export;
    			$item->created_by = $request->user;
    			$item->updated_by = $request->user;
    			$item->save();
    		}

    		return response()->json(['status' => 200, 'data' => $item]);
    }

    public function update(Request $request, $report_role_mapping_id)
  	{
      $check_data = DB::table('report_role_mapping')
        ->where('portlet_name', $request->portlet_name)
        ->where('role_id', $request->role_id)
        ->where('report_role_mapping_id', '!=' ,$report_role_mapping_id)
        ->count();

      if ($check_data > 0){
        return response()->json(['status' => 404, 'data' => 'Report name and role are duplicate.']);
      }

  		try {
  			$item = ReportRoleMapping::findOrFail($report_role_mapping_id);
  		} catch (ModelNotFoundException $e) {
  			return response()->json(['status' => 404, 'data' => 'Report role mapping not found.']);
  		}

      $validator = Validator::make($request->all(), [
        'portlet_name' => 'required|max:100',
        'role_id' => 'required|integer',
        'is_import' => 'required|boolean',
        'is_export' => 'required|boolean',
      ]);

  		if ($validator->fails()) {
  			return response()->json(['status' => 400, 'data' => $validator->errors()]);
  		} else {
  			$item->portlet_name = $request->portlet_name;
        $item->role_id = $request->role_id;
        $item->is_import = $request->is_import;
        $item->is_export = $request->is_export;
  			$item->updated_by = $request->user;
  			$item->save();
  		}

  		return response()->json(['status' => 200, 'data' => $item]);

  	}

    public function destroy($report_role_mapping_id)
  	{
  		try {
  			$item = ReportRoleMapping::findOrFail($report_role_mapping_id);
  		} catch (ModelNotFoundException $e) {
  			return response()->json(['status' => 404, 'data' => 'Report role mapping not found.']);
  		}

  		try {
  			$item->delete();
  		} catch (Exception $e) {
  			return response()->json($e->errorInfo);
  		}

  		return response()->json(['status' => 200]);

  	}

}
