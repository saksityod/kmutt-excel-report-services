<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\HonorBachelorReport;
use DB;
use Excel;
use File;
use Validator;
use Response;
use Exception;

class HonorBachelorReportController extends Controller
{

  public function ImportExcel(Request $request)
  {
      $user = $request->input('user');

      set_time_limit(0);
      ini_set('memory_limit', '1024M');
      config(['excel.import.heading' => 'original']);     //ใส่เพื่อให้สามารถอ่านชื่อหัวคอลัมภ์เป็นภาษาไทยได้

      $errors = array();

      //ระบุชื่อหัวคอลัมภ์ของไฟล์ที่ import เข้า
      $column1 = "ปีการศึกษา";
      $column2 = "คณะ";
      $column3 = "ภาค";
      $column4 = "โครงการหลักสูตร/ชั้นปี";
      $column5 = "ผู้สำเร็จฯ ป.ตรี ทั้งหมด";
      $column6 = "เกียรตินิยม อันดับ 1";
      $column7 = "เกียรตินิยม อันดับ 2";
      $column8 = "เกียรตินิยม รวม";
      $column9 = "ร้อยละ (ของผู้สำเร็จฯ ป.ตรี ทั้งหมด)";
      // $user = "admin";

      // ใช้ข้อมูลจากไฟล์ที่ import
		  foreach ($request->file() as $f) {
        $items = Excel::load($f, function($reader){})->get();
		    foreach ($items as $i) {

            // ตรวจสอบข้อมูลที่ import
            $validator = Validator::make($i->toArray(), [
                $column1 => 'required|integer',
                $column2 => 'required|max:255',
                $column3 => 'required|max:255',
                $column4 => 'required|max:255',
                $column5 => 'required|integer',
                $column6 => 'required|integer',
                $column7 => 'required|integer',
                $column8 => 'required|integer',
                $column9 => 'required|numeric'
            ]);

            // หากข้อมูลที่ import ไม่ต้องตามเงื่อนไขให้ return error
            if ($validator->fails()) {
      				  $errors[] = ['faculty_name' => $i->$column2, 'errors' => $validator->errors()];
                return response()->json(['status' => 400, 'errors' => $errors]);
      			}
            else {

              // ตรวจสอบข้อมูลที่ import ว่ามีอยู่ในตารางแล้วหรือไม่
              $honor = HonorBachelorReport::where('academic_year',$i->$column1)
                    ->where('faculty_name',$i->$column2)
                    ->where('department_name',$i->$column3)
                    ->where('project_name',$i->$column4)
                    ->first();

              if(empty($honor)) {
                // เมื่อไม่เคยมีข้อมูลที่ import ให้ insert ข้อมูลลงในตาราง
                $honor = new HonorBachelorReport;
                $honor->academic_year = $i->$column1;
                $honor->faculty_name = $i->$column2;
                $honor->department_name = $i->$column3;
                $honor->project_name = $i->$column4;
                $honor->no_of_graduate = $i->$column5;
                $honor->no_of_first_honor = $i->$column6;
                $honor->no_of_second_honor = $i->$column7;
                $honor->no_of_total_honor = $i->$column8;
                $honor->percent_of_honor = $i->$column9;
                $honor->created_by = $user;
                $honor->updated_by = $user;
                try {
    							$honor->save();
    						} catch (Exception $e) {
    							$errors[] = ['errors' => substr($e,0,254)];
    						}
              }
              else {
                // หากมีข้อมูลที่ import ในตารางแล้ว ให้ update ข้อมูล
                try {
                  $honor = DB::select("UPDATE honor_bachelor_report
                    SET no_of_graduate = ".$i->$column5."
                    , no_of_first_honor = ".$i->$column6."
                    , no_of_second_honor = ".$i->$column7."
                    , no_of_total_honor = ".$i->$column8."
                    , percent_of_honor = ".$i->$column9."
                    , updated_by = '".$user."'
                    , updated_dttm = now()
                    WHERE academic_year = ".$i->$column1."
                    AND faculty_name = '".$i->$column2."'
                    AND department_name = '".$i->$column3."'
                    AND project_name = '".$i->$column4."' ");
    						} catch (Exception $e) {
    							$errors[] = ['errors' => substr($e,0,254)];
    						}
              }
            }// end validator not fails

          }// end foreach
      }
      // return status ตาม errors
      if (empty($errors)){
        return response()->json(['status' => 200, 'errors' => $errors]);
      }else {
        return response()->json(['status' => 400, 'errors' => $errors]);
      }
  }

  public function ExportExcel(Request $request)
  {
      // select ข้อมูลที่ต้องการ export
      $query = "SELECT *
        FROM honor_bachelor_report
        WHERE 1 = 1";

      empty($request->param_year) ?: ($query .= " AND academic_year = ".$request->param_year." ");
      empty($request->param_faculty) ?: ($query .= " AND faculty_name = '".$request->param_faculty."' ");

      $items = DB::select($query." ORDER BY academic_year DESC, faculty_name ASC
              , department_name ASC, project_name ASC");

      // ระบุชื่อไฟล์
      $filename = "import_honor_bachelor_template";
      $x = Excel::create($filename, function($excel) use($items, $filename) {
      $excel->sheet($filename, function($sheet) use($items) {

      // ระบุ record แรกของไฟล์
      $sheet->appendRow(array("ปีการศึกษา", "คณะ", "ภาค", "โครงการหลักสูตร/ชั้นปี", "ผู้สำเร็จฯ ป.ตรี ทั้งหมด", "เกียรตินิยม อันดับ 1", "เกียรตินิยม อันดับ 2", "เกียรตินิยม รวม", "ร้อยละ (ของผู้สำเร็จฯ ป.ตรี ทั้งหมด)"));

      // ข้อมูลที่ใส่ในไฟล์
      foreach ($items as $i) {
        $sheet->appendRow(array(
            $i->academic_year,
            $i->faculty_name,
            $i->department_name,
            $i->project_name,
            $i->no_of_graduate,
            $i->no_of_first_honor,
            $i->no_of_second_honor,
            $i->no_of_total_honor,
            $i->percent_of_honor,
        ));
      }
      });
      })->export('xlsx');
  }

  public function YearList()
  {
      $Year = DB::select("SELECT academic_year
        FROM honor_bachelor_report
        GROUP BY academic_year
        ORDER BY academic_year DESC");

      return response()->json($Year);
  }

  public function FacultyList(Request $request)
  {
      $query = "SELECT faculty_name
        FROM honor_bachelor_report
        WHERE 1 = 1";

      empty($request->param_year) ?: ($query .= " AND academic_year = ".$request->param_year." ");

      $Faculty = DB::select($query." GROUP BY faculty_name
          ORDER BY faculty_name ASC");

      return response()->json($Faculty);
  }

  public function CheckRoleUser(Request $request)
  {
      // set name report by page
      if ($request->report == "honor"){
        $report = "Honor Bachelor Report";
      }

      // connect database lportal
      $connect = DB::connection('mysql_lportal');

      // search role by user login
      $role_user = $connect->select("
        SELECT GROUP_CONCAT(re.roleId) as role_id
        FROM Role_ re
        INNER JOIN Users_Roles uro ON re.roleId = uro.roleId
        INNER JOIN User_ us ON uro.userId = us.userId
        WHERE us.screenName = '".$request->user."' ");

      // set disable button import, download(export)
      $sql_close_button = "
        SELECT 0 as is_import
        , 0 as is_export";

      if (!empty($role_user)) {
        foreach ($role_user as $ru) {

          if ($ru->role_id == null){
            $close_button = DB::select($sql_close_button);
            return response()->json(['status' => 400, 'error' => $request->user." don't have role.", 'data' => $close_button]);
          }

          // search is_import, is_export by role, report
          $user_botton = DB::select("
            SELECT COALESCE(SUM(is_import),0) as is_import
            , COALESCE(SUM(is_export),0) as is_export
            FROM report_role_mapping
            WHERE FIND_IN_SET(role_id,'".$ru->role_id."')
            AND portlet_name = '".$report."' ");

        }

        return response()->json(['status' => 200, 'data' => $user_botton]);
      }else {
        $close_button = DB::select($sql_close_button);
        return response()->json(['status' => 400, 'error' => "Don't have user '".$request->user."'", 'data' => $close_button]);
      }
  }


}
