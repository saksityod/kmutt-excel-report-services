<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\GraduateStudentReport;
use DB;
use Excel;
use File;
use Validator;
use Response;

class GraduateStudentReportController extends Controller
{

  public function ImportGraduateExcel(Request $request)
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
      $column5 = "ระดับการศึกษา";
      $column6 = "แผน";
      $column7 = "จริง";
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
                $column6 => 'required|integer',
                $column7 => 'required|integer',
            ]);

            // หากข้อมูลที่ import ไม่ต้องตามเงื่อนไขให้ return error
            if ($validator->fails()) {
                $errors[] = ['faculty_name' => $i->$column2, 'errors' => $validator->errors()];
                return response()->json(['status' => 400, 'errors' => $errors]);
            }
            else {

              // ตรวจสอบข้อมูลที่ import ว่ามีอยู่ในตารางแล้วหรือไม่
              $honor = GraduateStudentReport::where('academic_year',$i->$column1)
                    ->where('faculty_name',$i->$column2)
                    ->where('department_name',$i->$column3)
                    ->where('field_name',$i->$column4)
                    ->where('education_name',$i->$column5)
                    ->first();

              if(empty($honor)) {
                // เมื่อไม่เคยมีข้อมูลที่ import ให้ insert ข้อมูลลงในตาราง
                $honor = new GraduateStudentReport;
                $honor->academic_year = $i->$column1;
                $honor->faculty_name = $i->$column2;
                $honor->department_name = $i->$column3;
                $honor->field_name = $i->$column4;
                $honor->education_name = $i->$column5;
                $honor->no_of_graduate_student_plan = $i->$column6;
                $honor->no_of_graduate_student = $i->$column7;
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
                  $honor = DB::select("UPDATE graduate_student_report
                    SET no_of_graduate_student_plan = ".$i->$column6."
                    , no_of_graduate_student = ".$i->$column7."
                    , updated_by = '".$user."'
                    , updated_dttm = now()
                    WHERE academic_year = ".$i->$column1."
                    AND faculty_name = '".$i->$column2."'
                    AND department_name = '".$i->$column3."'
                    AND field_name = '".$i->$column4."'
                    AND education_name = '".$i->$column5."' ");
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

  public function ExportGraduateExcel(Request $request)
  {
      // select ข้อมูลที่ต้องการ export
      $query = "SELECT *
        FROM graduate_student_report
        WHERE 1 = 1";

      empty($request->param_year) ?: ($query .= " AND academic_year = ".$request->param_year." ");
      empty($request->param_faculty) ?: ($query .= " AND faculty_name = '".$request->param_faculty."' ");
      empty($request->param_department) ?: ($query .= " AND department_name = '".$request->param_department."' ");

      if ($request->param_education == 1) { $query .= " AND education_name LIKE '%ตรี%' "; }
      else if ($request->param_education == 2) { $query .= " AND education_name LIKE '%โท%' "; }
      else if ($request->param_education == 3) { $query .= " AND education_name LIKE '%เอก%' "; }

      $items = DB::select($query." ORDER BY academic_year DESC, faculty_name ASC
              , department_name ASC, field_name ASC, education_name ASC");

      // ระบุชื่อไฟล์
      $filename = "import_graduate_template";
      $x = Excel::create($filename, function($excel) use($items, $filename) {
      $excel->sheet($filename, function($sheet) use($items) {

      // ระบุ record แรกของไฟล์
      $sheet->appendRow(array("ปีการศึกษา", "คณะ", "ภาค", "สาขา", "ระดับการศึกษา", "แผน", "จริง"));

      // ข้อมูลที่ใส่ในไฟล์
      foreach ($items as $i) {
        $sheet->appendRow(array(
            $i->academic_year,
            $i->faculty_name,
            $i->department_name,
            $i->field_name,
            $i->education_name,
            $i->no_of_graduate_student_plan,
            $i->no_of_graduate_student,
        ));
      }
      });
      })->export('xlsx');
  }

  ////////////////////////////////////////////////////////////////////////////

  public function ImportGraduatePercentExcel(Request $request)
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
      $column5 = "ระดับการศึกษา";
      $column6 = "แผน";
      $column7 = "จริง";
      $column8 = "ร้อยละ(จริงเปรียบเทียบ)";
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
                $column6 => 'required|integer',
                $column7 => 'required|integer',
                $column8 => 'required|numeric',
            ]);

            // หากข้อมูลที่ import ไม่ต้องตามเงื่อนไขให้ return error
            if ($validator->fails()) {
                $errors[] = ['faculty_name' => $i->$column2, 'errors' => $validator->errors()];
                return response()->json(['status' => 400, 'errors' => $errors]);
            }
            else {

              // ตรวจสอบข้อมูลที่ import ว่ามีอยู่ในตารางแล้วหรือไม่
              $honor = GraduateStudentReport::where('academic_year',$i->$column1)
                    ->where('faculty_name',$i->$column2)
                    ->where('department_name',$i->$column3)
                    ->where('field_name',$i->$column4)
                    ->where('education_name',$i->$column5)
                    ->first();

              if(empty($honor)) {
                // เมื่อไม่เคยมีข้อมูลที่ import ให้ insert ข้อมูลลงในตาราง
                $honor = new GraduateStudentReport;
                $honor->academic_year = $i->$column1;
                $honor->faculty_name = $i->$column2;
                $honor->department_name = $i->$column3;
                $honor->field_name = $i->$column4;
                $honor->education_name = $i->$column5;
                $honor->no_of_graduate_student_plan = $i->$column6;
                $honor->no_of_graduate_student = $i->$column7;
                $honor->percent_graduate_student = $i->$column8;
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
                  $honor = DB::select("UPDATE graduate_student_report
                    SET no_of_graduate_student_plan = ".$i->$column6."
                    , no_of_graduate_student = ".$i->$column7."
                    , percent_graduate_student = ".$i->$column8."
                    , updated_by = '".$user."'
                    , updated_dttm = now()
                    WHERE academic_year = ".$i->$column1."
                    AND faculty_name = '".$i->$column2."'
                    AND department_name = '".$i->$column3."'
                    AND field_name = '".$i->$column4."'
                    AND education_name = '".$i->$column5."' ");
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

  public function ExportGraduatePercentExcel(Request $request)
  {
      // select ข้อมูลที่ต้องการ export
      $query = "SELECT *
        FROM graduate_student_report
        WHERE 1 = 1";

      empty($request->param_year) ?: ($query .= " AND academic_year = ".$request->param_year." ");
      empty($request->param_faculty) ?: ($query .= " AND faculty_name = '".$request->param_faculty."' ");
      empty($request->param_department) ?: ($query .= " AND department_name = '".$request->param_department."' ");

      if ($request->param_education == 1) { $query .= " AND education_name LIKE '%ตรี%' "; }
      else if ($request->param_education == 2) { $query .= " AND education_name LIKE '%โท%' "; }
      else if ($request->param_education == 3) { $query .= " AND education_name LIKE '%เอก%' "; }

      $items = DB::select($query." ORDER BY academic_year DESC, faculty_name ASC
              , department_name ASC, field_name ASC, education_name ASC");

      // ระบุชื่อไฟล์
      $filename = "import_graduate(%)_template";
      $x = Excel::create($filename, function($excel) use($items, $filename) {
      $excel->sheet($filename, function($sheet) use($items) {

      // ระบุ record แรกของไฟล์
      $sheet->appendRow(array("ปีการศึกษา", "คณะ", "ภาค", "สาขา", "ระดับการศึกษา", "แผน", "จริง", "ร้อยละ(จริงเปรียบเทียบ)"));

      // ข้อมูลที่ใส่ในไฟล์
      foreach ($items as $i) {
        $sheet->appendRow(array(
            $i->academic_year,
            $i->faculty_name,
            $i->department_name,
            $i->field_name,
            $i->education_name,
            $i->no_of_graduate_student_plan,
            $i->no_of_graduate_student,
            $i->percent_graduate_student,
        ));
      }
      });
      })->export('xlsx');
  }

  ////////////////////////////////////////////////////////////////////////////

  public function YearList()
  {
      $Year = DB::select("SELECT academic_year
        FROM graduate_student_report
        GROUP BY academic_year
        ORDER BY academic_year DESC");

      return response()->json($Year);
  }

  public function EducationList(Request $request)
  {
      $query = "SELECT (CASE WHEN education_name LIKE '%ตรี%' THEN 1 ELSE
          (CASE WHEN education_name LIKE '%โท%' THEN 2 ELSE
            (CASE WHEN education_name LIKE '%เอก%' THEN 3 ELSE 0 END)
          END)
        END) as education_id
        , education_name
        FROM graduate_student_report
        WHERE 1 = 1 ";

      empty($request->param_year) ?: ($query .= " AND academic_year = ".$request->param_year." ");

      $Education = DB::select($query." GROUP BY education_name
            ORDER BY 1 ASC");

      return response()->json($Education);
  }

  public function FacultyList(Request $request)
  {
      $query = "SELECT faculty_name
        FROM graduate_student_report
        WHERE 1 = 1 ";

      empty($request->param_year) ?: ($query .= " AND academic_year = ".$request->param_year." ");

      if ($request->param_education == 1) { $query .= " AND education_name LIKE '%ตรี%' "; }
      else if ($request->param_education == 2) { $query .= " AND education_name LIKE '%โท%' "; }
      else if ($request->param_education == 3) { $query .= " AND education_name LIKE '%เอก%' "; }

      $Faculty = DB::select($query." GROUP BY faculty_name
              ORDER BY faculty_name ASC");

      return response()->json($Faculty);
  }

  public function DepartmentList(Request $request)
  {
      $query = "SELECT department_name
        FROM graduate_student_report
        WHERE 1 = 1 ";

      empty($request->param_year) ?: ($query .= " AND academic_year = ".$request->param_year." ");
      empty($request->param_faculty) ?: ($query .= " AND faculty_name = '".$request->param_faculty."' ");

      if ($request->param_education == 1) { $query .= " AND education_name LIKE '%ตรี%' "; }
      else if ($request->param_education == 2) { $query .= " AND education_name LIKE '%โท%' "; }
      else if ($request->param_education == 3) { $query .= " AND education_name LIKE '%เอก%' "; }

      $Department = DB::select($query." GROUP BY department_name
            ORDER BY department_name ASC");

      return response()->json($Department);
  }

  public function CheckRoleUser(Request $request)
  {
      // set name report by page
      if ($request->report == "graduate"){
        $report = "Graduate Student Report";
      }else if ($request->report == "graduate_percent"){
        $report = "Graduate Student Report (%)";
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
