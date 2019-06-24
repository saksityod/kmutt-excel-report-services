<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\BachelorAdmissionReport;
use DB;
use Excel;
use File;
use Validator;
use Response;

class BachelorAdmissionReportController extends Controller
{
  public function ImportBachelorAdmissionExcel(Request $request)
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
      $column4 = "สาขา";
      $column5 = "ประเภทการรับสมัคร";
      $column6 = "จำนวนผู้สมัคร";
      $column7 = "จำนวนผู้มีสิทธิ์เข้าศึกษาต่อ";
      $column8 = "จำนวนผู้ยืนยันสิทธิ์";
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
                $column5 => 'required|max:255',
                $column6 => 'integer',
                $column7 => 'integer',
                $column8 => 'integer',
            ]);

            // กำหนดค่าว่างให้เป็น null สำหรับกรณีที่ไม่มีการระบุข้อมูลในไฟล์ excel
            $i->$column6 = (empty($i->$column6)) ? 'null' : $i->$column6;
            $i->$column7 = (empty($i->$column7)) ? 'null' : $i->$column7;
            $i->$column8 = (empty($i->$column8)) ? 'null' : $i->$column8;

            // หากข้อมูลที่ import ไม่ต้องตามเงื่อนไขให้ return error
            if ($validator->fails()) {
                $errors[] = ['faculty_name' => $i->$column2, 'errors' => $validator->errors()];
                return response()->json(['status' => 400, 'errors' => $errors]);
            }
            else {

              // ตรวจสอบข้อมูลที่ import ว่ามีอยู่ในตารางแล้วหรือไม่
              $honor = BachelorAdmissionReport::where('academic_year',$i->$column1)
                    ->where('faculty_name',$i->$column2)
                    ->where('department_name',$i->$column3)
                    ->where('field_name',$i->$column4)
                    ->where('admission_type',$i->$column5)
                    ->first();

              if(empty($honor)) {
                // เมื่อไม่เคยมีข้อมูลที่ import ให้ insert ข้อมูลลงในตาราง
                $honor = new BachelorAdmissionReport;
                $honor->academic_year = $i->$column1;
                $honor->faculty_name = $i->$column2;
                $honor->department_name = $i->$column3;
                $honor->field_name = $i->$column4;
                $honor->admission_type = $i->$column5;
                $honor->no_of_candidate = $i->$column6;
                $honor->no_of_qualified = $i->$column7;
                $honor->no_of_reported = $i->$column8;
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
                  $honor = DB::select("UPDATE bachelor_admission
                    SET no_of_candidate = ".$i->$column6."
                    , no_of_qualified = ".$i->$column7."
                    , no_of_reported = ".$i->$column8."
                    , updated_by = '".$user."'
                    , updated_dttm = now()
                    WHERE academic_year = ".$i->$column1."
                    AND faculty_name = '".$i->$column2."'
                    AND department_name = '".$i->$column3."'
                    AND field_name = '".$i->$column4."'
                    AND admission_type = '".$i->$column5."' ");
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

  public function ExportBachelorAdmissionExcel(Request $request)
  {
      // select ข้อมูลที่ต้องการ export
      $query = "SELECT *
        FROM bachelor_admission
        WHERE 1 = 1";

      empty($request->param_year) ?: ($query .= " AND academic_year = ".$request->param_year." ");
      empty($request->param_faculty) ?: ($query .= " AND faculty_name = '".$request->param_faculty."' ");
      empty($request->param_department) ?: ($query .= " AND department_name = '".$request->param_department."' ");

      $items = DB::select($query." ORDER BY academic_year DESC, faculty_name ASC
              , department_name ASC, field_name ASC");

      // ระบุชื่อไฟล์
      $filename = "bachelor_admission_template";
      $x = Excel::create($filename, function($excel) use($items, $filename) {
      $excel->sheet($filename, function($sheet) use($items) {

      // ระบุ record แรกของไฟล์
      $sheet->appendRow(array("ปีการศึกษา", "คณะ", "ภาค", "สาขา", "ประเภทการรับสมัคร", "จำนวนผู้สมัคร", "จำนวนผู้มีสิทธิ์เข้าศึกษาต่อ", "จำนวนผู้ยืนยันสิทธิ์"));

      // ข้อมูลที่ใส่ในไฟล์
      foreach ($items as $i) {
        $sheet->appendRow(array(
            $i->academic_year,
            $i->faculty_name,
            $i->department_name,
            $i->field_name,
            $i->admission_type,
            $i->no_of_candidate,
            $i->no_of_qualified,
            $i->no_of_reported,
        ));
      }
      });
      })->export('xlsx');
  }

  ////////////////////////////////////////////////////////////////////////////

  public function YearList()
  {
      $Year = DB::select("SELECT academic_year
        FROM bachelor_admission
        GROUP BY academic_year
        ORDER BY academic_year DESC");

      return response()->json($Year);
  }

  public function FacultyList(Request $request)
  {
      $query = "SELECT faculty_name
        FROM bachelor_admission
        WHERE 1 = 1 ";

      empty($request->param_year) ?: ($query .= " AND academic_year = ".$request->param_year." ");

      $Faculty = DB::select($query." GROUP BY faculty_name
            ORDER BY faculty_name ASC");

      return response()->json($Faculty);
  }

  public function DepartmentList(Request $request)
  {
      $query = "SELECT department_name
        FROM bachelor_admission
        WHERE 1 = 1 ";

      empty($request->param_year) ?: ($query .= " AND academic_year = ".$request->param_year." ");
      empty($request->param_faculty) ?: ($query .= " AND faculty_name = '".$request->param_faculty."' ");

      $Department = DB::select($query." GROUP BY department_name
            ORDER BY department_name ASC");

      return response()->json($Department);
  }

  public function CheckRoleUser(Request $request)
  {
      // set disable button import, download(export)
      $sql_close_button = "
        SELECT 0 as is_import
        , 0 as is_export";

      $close_button = DB::select($sql_close_button);

      // bachelor_admission report เท่านั้น
      if ($request->report != "bachelor_admission"){
        return response()->json(['status' => 400, 'error' => $request->user." don't have role.", 'data' => $close_button]);
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

      if (!empty($role_user)) {
        foreach ($role_user as $ru) {

          if ($ru->role_id == null){
            return response()->json(['status' => 400, 'error' => $request->user." don't have role.", 'data' => $close_button]);
          }

          // search is_import, is_export by role, report
          $user_botton = DB::select("
            SELECT COALESCE(SUM(is_import),0) as is_import
            , COALESCE(SUM(is_export),0) as is_export
            FROM report_role_mapping
            WHERE FIND_IN_SET(role_id,'".$ru->role_id."')
            AND portlet_name = 'Bachelor Admission Report' ");

        }

        return response()->json(['status' => 200, 'data' => $user_botton]);
      }else {
        $close_button = DB::select($sql_close_button);
        return response()->json(['status' => 400, 'error' => "Don't have user '".$request->user."'", 'data' => $close_button]);
      }
  }

}
