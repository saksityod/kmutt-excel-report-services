<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\AllStudentReport;
use DB;
use Excel;
use File;
use Validator;
use Response;

class AllStudentReportController extends Controller
{

  public function ImportAllExcel(Request $request)
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
              $honor = AllStudentReport::where('academic_year',$i->$column1)
                    ->where('faculty_name',$i->$column2)
                    ->where('department_name',$i->$column3)
                    ->where('project_name',$i->$column4)
                    ->where('education_name',$i->$column5)
                    ->first();

              if(empty($honor)) {
                // เมื่อไม่เคยมีข้อมูลที่ import ให้ insert ข้อมูลลงในตาราง
                $honor = new AllStudentReport;
                $honor->academic_year = $i->$column1;
                $honor->faculty_name = $i->$column2;
                $honor->department_name = $i->$column3;
                $honor->project_name = $i->$column4;
                $honor->education_name = $i->$column5;
                $honor->no_of_all_student_plan = $i->$column6;
                $honor->no_of_all_student = $i->$column7;
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
                  $honor = DB::select("UPDATE all_student_report
                    SET no_of_all_student_plan = ".$i->$column6."
                    , no_of_all_student = ".$i->$column7."
                    , updated_by = '".$user."'
                    , updated_dttm = now()
                    WHERE academic_year = ".$i->$column1."
                    AND faculty_name = '".$i->$column2."'
                    AND department_name = '".$i->$column3."'
                    AND project_name = '".$i->$column4."'
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

  public function ExportAllExcel(Request $request)
  {
      // select ข้อมูลที่ต้องการ export
      $query = "SELECT *
        FROM all_student_report
        WHERE 1 = 1";

      empty($request->param_year) ?: ($query .= " AND academic_year = ".$request->param_year." ");
      empty($request->param_faculty) ?: ($query .= " AND faculty_name = '".$request->param_faculty."' ");

      $items = DB::select($query." ORDER BY academic_year DESC, faculty_name ASC
              , department_name ASC, project_name ASC, education_name ASC");

      // ระบุชื่อไฟล์
      $filename = "import_all_student_template";
      $x = Excel::create($filename, function($excel) use($items, $filename) {
      $excel->sheet($filename, function($sheet) use($items) {

      // ระบุ record แรกของไฟล์
      $sheet->appendRow(array("ปีการศึกษา", "คณะ", "ภาค", "โครงการหลักสูตร/ชั้นปี", "ระดับการศึกษา", "แผน", "จริง"));

      // ข้อมูลที่ใส่ในไฟล์
      foreach ($items as $i) {
        $sheet->appendRow(array(
            $i->academic_year,
            $i->faculty_name,
            $i->department_name,
            $i->project_name,
            $i->education_name,
            $i->no_of_all_student_plan,
            $i->no_of_all_student,
        ));
      }
      });
      })->export('xlsx');
  }

  ////////////////////////////////////////////////////////////////////////////

  public function ImportAllPercentExcel(Request $request)
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
              $honor = AllStudentReport::where('academic_year',$i->$column1)
                    ->where('faculty_name',$i->$column2)
                    ->where('department_name',$i->$column3)
                    ->where('project_name',$i->$column4)
                    ->where('education_name',$i->$column5)
                    ->first();

              if(empty($honor)) {
                // เมื่อไม่เคยมีข้อมูลที่ import ให้ insert ข้อมูลลงในตาราง
                $honor = new AllStudentReport;
                $honor->academic_year = $i->$column1;
                $honor->faculty_name = $i->$column2;
                $honor->department_name = $i->$column3;
                $honor->project_name = $i->$column4;
                $honor->education_name = $i->$column5;
                $honor->no_of_all_student_plan = $i->$column6;
                $honor->no_of_all_student = $i->$column7;
                $honor->percent_all_student = $i->$column8;
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
                  $honor = DB::select("UPDATE all_student_report
                    SET no_of_all_student_plan = ".$i->$column6."
                    , no_of_all_student = ".$i->$column7."
                    , percent_all_student = ".$i->$column8."
                    , updated_by = '".$user."'
                    , updated_dttm = now()
                    WHERE academic_year = ".$i->$column1."
                    AND faculty_name = '".$i->$column2."'
                    AND department_name = '".$i->$column3."'
                    AND project_name = '".$i->$column4."'
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

  public function ExportAllPercentExcel(Request $request)
  {
      // select ข้อมูลที่ต้องการ export
      $query = "SELECT *
        FROM all_student_report
        WHERE 1 = 1";

      empty($request->param_year) ?: ($query .= " AND academic_year = ".$request->param_year." ");
      empty($request->param_faculty) ?: ($query .= " AND faculty_name = '".$request->param_faculty."' ");

      $items = DB::select($query." ORDER BY academic_year DESC, faculty_name ASC
              , department_name ASC, project_name ASC, education_name ASC");

      // ระบุชื่อไฟล์
      $filename = "import_all_student(%)_template";
      $x = Excel::create($filename, function($excel) use($items, $filename) {
      $excel->sheet($filename, function($sheet) use($items) {

      // ระบุ record แรกของไฟล์
      $sheet->appendRow(array("ปีการศึกษา", "คณะ", "ภาค", "โครงการหลักสูตร/ชั้นปี", "ระดับการศึกษา", "แผน", "จริง", "ร้อยละ(จริงเปรียบเทียบ)"));

      // ข้อมูลที่ใส่ในไฟล์
      foreach ($items as $i) {
        $sheet->appendRow(array(
            $i->academic_year,
            $i->faculty_name,
            $i->department_name,
            $i->project_name,
            $i->education_name,
            $i->no_of_all_student_plan,
            $i->no_of_all_student,
            $i->percent_all_student,
        ));
      }
      });
      })->export('xlsx');
  }

  ////////////////////////////////////////////////////////////////////////////

  public function YearList()
  {
      $Year = DB::select("SELECT academic_year
        FROM all_student_report
        GROUP BY academic_year
        ORDER BY academic_year DESC");

      return response()->json($Year);
  }

  public function FacultyList(Request $request)
  {
      $query = "SELECT faculty_name
        FROM all_student_report
        WHERE 1 = 1 ";

      empty($request->param_year) ?: ($query .= " AND academic_year = ".$request->param_year." ");

      $Faculty = DB::select($query." GROUP BY faculty_name
            ORDER BY faculty_name ASC");

      return response()->json($Faculty);
  }

}