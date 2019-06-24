<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\AdmissionScoreReport;
use DB;
use Excel;
use File;
use Validator;
use Response;

class AdmissionScoreReportController extends Controller
{
  public function ImportAdmissionScoreExcel(Request $request)
  {
      $user = $request->input('user');

      set_time_limit(0);
      ini_set('memory_limit', '1024M');
      config(['excel.import.heading' => 'original']);     //ใส่เพื่อให้สามารถอ่านชื่อหัวคอลัมภ์เป็นภาษาไทยได้

      $errors = array();

      //ระบุชื่อหัวคอลัมภ์ของไฟล์ที่ import เข้า
      $column1 = "ปีการศึกษา";
      $column2 = "รหัส";
      $column3 = "คณะ/สาขา";
      $column4 = "จำนวนสมัคร";
      $column5 = "จำนวนรับ";
      $column6 = "จำนวนผ่าน";
      $column7 = "คะแนนสูงสุด";
      $column8 = "คะแนนต่ำสุด";
      $column9 = "คะแนนเฉลี่ย";
      $column10 = "SD";
      // $user = "admin";

      // ใช้ข้อมูลจากไฟล์ที่ import
      foreach ($request->file() as $f) {
        $items = Excel::load($f, function($reader){})->get();
        foreach ($items as $i) {

            // ตรวจสอบข้อมูลที่ import
            $validator = Validator::make($i->toArray(), [
                $column1 => 'required|integer',
                $column2 => 'required|max:20',
                $column3 => 'required|max:255',
                $column4 => 'integer',
                $column5 => 'integer',
                $column6 => 'integer',
                $column7 => 'numeric',
                $column8 => 'numeric',
                $column9 => 'numeric',
                $column10 => 'numeric',
            ]);

            // กำหนดค่าว่างให้เป็น null สำหรับกรณีที่ไม่มีการระบุข้อมูลในไฟล์ excel
            $i->$column4 = (empty($i->$column4)) ? 'null' : $i->$column4;
            $i->$column5 = (empty($i->$column5)) ? 'null' : $i->$column5;
            $i->$column6 = (empty($i->$column6)) ? 'null' : $i->$column6;
            $i->$column7 = (empty($i->$column7)) ? 'null' : $i->$column7;
            $i->$column8 = (empty($i->$column8)) ? 'null' : $i->$column8;
            $i->$column9 = (empty($i->$column9)) ? 'null' : $i->$column9;
            $i->$column10 = (empty($i->$column10)) ? 'null' : $i->$column10;

            // หากข้อมูลที่ import ไม่ต้องตามเงื่อนไขให้ return error
            if ($validator->fails()) {
                $errors[] = ['faculty_name' => $i->$column3, 'errors' => $validator->errors()];
                return response()->json(['status' => 400, 'errors' => $errors]);
            }
            else {

              // ตรวจสอบข้อมูลที่ import ว่ามีอยู่ในตารางแล้วหรือไม่
              $honor = AdmissionScoreReport::where('academic_year',$i->$column1)
                    ->where('field_code',$i->$column2)
                    ->where('faculty_field_name',$i->$column3)
                    ->first();

              if(empty($honor)) {
                // เมื่อไม่เคยมีข้อมูลที่ import ให้ insert ข้อมูลลงในตาราง
                $honor = new AdmissionScoreReport;
                $honor->academic_year = $i->$column1;
                $honor->field_code = $i->$column2;
                $honor->faculty_field_name = $i->$column3;
                $honor->no_of_candidate = $i->$column4;
                $honor->no_of_admission = $i->$column5;
                $honor->no_of_qualified = $i->$column6;
                $honor->max_score = $i->$column7;
                $honor->min_score = $i->$column8;
                $honor->average_score = $i->$column9;
                $honor->sd_score = $i->$column10;
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
                  $honor = DB::select("UPDATE admission_score
                    SET no_of_candidate = ".$i->$column4."
                    , no_of_admission = ".$i->$column5."
                    , no_of_qualified = ".$i->$column6."
                    , max_score = ".$i->$column7."
                    , min_score = ".$i->$column8."
                    , average_score = ".$i->$column9."
                    , sd_score = ".$i->$column10."
                    , updated_by = '".$user."'
                    , updated_dttm = now()
                    WHERE academic_year = ".$i->$column1."
                    AND field_code = '".$i->$column2."'
                    AND faculty_field_name = '".$i->$column3."' ");
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

  public function ExportAdmissionScoreExcel(Request $request)
  {
      // select ข้อมูลที่ต้องการ export
      $query = "SELECT *
        FROM admission_score
        WHERE 1 = 1";

      empty($request->param_year) ?: ($query .= " AND academic_year = ".$request->param_year." ");
      empty($request->param_faculty_field) ?: ($query .= " AND faculty_name = '".$request->param_faculty_field."' ");

      $items = DB::select($query." ORDER BY academic_year DESC, field_code ASC");

      // ระบุชื่อไฟล์
      $filename = "import_admission_score_template";
      $x = Excel::create($filename, function($excel) use($items, $filename) {
      $excel->sheet($filename, function($sheet) use($items) {

      // ระบุ record แรกของไฟล์
      $sheet->appendRow(array("ปีการศึกษา", "รหัส", "คณะ/สาขา", "จำนวนสมัคร", "จำนวนรับ", "จำนวนผ่าน", "คะแนนสูงสุด", "คะแนนต่ำสุด", "คะแนนเฉลี่ย", "SD"));

      // ข้อมูลที่ใส่ในไฟล์
      foreach ($items as $i) {
        $sheet->appendRow(array(
            $i->academic_year,
            $i->field_code,
            $i->faculty_field_name,
            $i->no_of_candidate,
            $i->no_of_admission,
            $i->no_of_qualified,
            $i->max_score,
            $i->min_score,
            $i->average_score,
            $i->sd_score,
        ));
      }
      });
      })->export('xlsx');
  }

  ////////////////////////////////////////////////////////////////////////////

  public function YearList()
  {
      $Year = DB::select("SELECT academic_year
        FROM admission_score
        GROUP BY academic_year
        ORDER BY academic_year DESC");

      return response()->json($Year);
  }

  public function FacultyFieldList(Request $request)
  {
      $query = "SELECT faculty_field_name
        FROM admission_score
        WHERE 1 = 1 ";

      empty($request->param_year) ?: ($query .= " AND academic_year = ".$request->param_year." ");

      $Faculty = DB::select($query." GROUP BY faculty_field_name
            ORDER BY faculty_field_name ASC");

      return response()->json($Faculty);
  }

  public function CheckRoleUser(Request $request)
  {
      // set disable button import, download(export)
      $sql_close_button = "
        SELECT 0 as is_import
        , 0 as is_export";

      $close_button = DB::select($sql_close_button);

      // bachelor_admission report เท่านั้น
      if ($request->report != "admission_score"){
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
            AND portlet_name = 'Admission Score Report' ");

        }

        return response()->json(['status' => 200, 'data' => $user_botton]);
      }else {
        $close_button = DB::select($sql_close_button);
        return response()->json(['status' => 400, 'error' => "Don't have user '".$request->user."'", 'data' => $close_button]);
      }
  }

}
