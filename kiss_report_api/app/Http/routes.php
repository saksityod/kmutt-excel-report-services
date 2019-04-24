<?php

if (isset($_SERVER['HTTP_ORIGIN'])) {
	header('Access-Control-Allow-Credentials: true');
	header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
	header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');
	header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, useXDomain, withCredentials');
	header('Keep-Alive: off');
}

Route::get('/', function () {
    return view('welcome');
});

// Report Operation Permissions
Route::get('report_role_mapping', 'ReportOperationPermissionsController@index');
Route::get('report_role_mapping/role_list', 'ReportOperationPermissionsController@role_list');
Route::get('report_role_mapping/{report_role_mapping_id}', 'ReportOperationPermissionsController@show');
Route::post('report_role_mapping', 'ReportOperationPermissionsController@store');
Route::patch('report_role_mapping/{report_role_mapping_id}', 'ReportOperationPermissionsController@update');
Route::delete('report_role_mapping/{report_role_mapping_id}', 'ReportOperationPermissionsController@destroy');

// Honor Bachelor Report
Route::get('Honor/YearList', 'HonorBachelorReportController@YearList');
Route::get('Honor/FacultyList', 'HonorBachelorReportController@FacultyList');
Route::get('Honor/CheckRoleUser', 'HonorBachelorReportController@CheckRoleUser');
Route::post('Honor/Import/HonorBachelor', 'HonorBachelorReportController@ImportExcel');
Route::post('Honor/Export', 'HonorBachelorReportController@ExportExcel');

// New Student Report
Route::get('NewStudent/YearList', 'NewStudentReportController@YearList');
Route::get('NewStudent/FacultyList', 'NewStudentReportController@FacultyList');
Route::get('NewStudent/DepartmentList', 'NewStudentReportController@DepartmentList');
Route::get('NewStudent/EducationList', 'NewStudentReportController@EducationList');
Route::get('NewStudent/CheckRoleUser', 'NewStudentReportController@CheckRoleUser');
Route::post('NewStudent/Import', 'NewStudentReportController@ImportNewExcel');
Route::post('NewStudent/Export', 'NewStudentReportController@ExportNewExcel');
Route::post('NewStudent/Import/Percent', 'NewStudentReportController@ImportNewPercentExcel');
Route::post('NewStudent/Export/Percent', 'NewStudentReportController@ExportNewPercentExcel');

// All Student Report
Route::get('AllStudent/YearList', 'AllStudentReportController@YearList');
Route::get('AllStudent/FacultyList', 'AllStudentReportController@FacultyList');
Route::get('AllStudent/DepartmentList', 'AllStudentReportController@DepartmentList');
Route::get('AllStudent/EducationList', 'AllStudentReportController@EducationList');
Route::get('AllStudent/CheckRoleUser', 'AllStudentReportController@CheckRoleUser');
Route::post('AllStudent/Import', 'AllStudentReportController@ImportAllExcel');
Route::post('AllStudent/Export', 'AllStudentReportController@ExportAllExcel');
Route::post('AllStudent/Import/Percent', 'AllStudentReportController@ImportAllPercentExcel');
Route::post('AllStudent/Export/Percent', 'AllStudentReportController@ExportAllPercentExcel');

// Graduate Student Report
Route::get('GraduateStudent/YearList', 'GraduateStudentReportController@YearList');
Route::get('GraduateStudent/FacultyList', 'GraduateStudentReportController@FacultyList');
Route::get('GraduateStudent/DepartmentList', 'GraduateStudentReportController@DepartmentList');
Route::get('GraduateStudent/EducationList', 'GraduateStudentReportController@EducationList');
Route::get('GraduateStudent/CheckRoleUser', 'GraduateStudentReportController@CheckRoleUser');
Route::post('GraduateStudent/Import', 'GraduateStudentReportController@ImportGraduateExcel');
Route::post('GraduateStudent/Export', 'GraduateStudentReportController@ExportGraduateExcel');
Route::post('GraduateStudent/Import/Percent', 'GraduateStudentReportController@ImportGraduatePercentExcel');
Route::post('GraduateStudent/Export/Percent', 'GraduateStudentReportController@ExportGraduatePercentExcel');

// Jasper Report
Route::Resource('generate', 'JasperController@generate');
