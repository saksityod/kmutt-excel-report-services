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

// Honor Bachelor Report
Route::get('Honor/YearList', 'HonorBachelorReportController@YearList');
Route::get('Honor/FacultyList', 'HonorBachelorReportController@FacultyList');
Route::post('Honor/Import/HonorBachelor', 'HonorBachelorReportController@ImportExcel');
Route::post('Honor/Export', 'HonorBachelorReportController@ExportExcel');

// New Student Report
Route::get('NewStudent/YearList', 'NewStudentReportController@YearList');
Route::get('NewStudent/FacultyList', 'NewStudentReportController@FacultyList');
Route::post('NewStudent/Import', 'NewStudentReportController@ImportNewExcel');
Route::post('NewStudent/Export', 'NewStudentReportController@ExportNewExcel');
Route::post('NewStudent/Import/Percent', 'NewStudentReportController@ImportNewPercentExcel');
Route::post('NewStudent/Export/Percent', 'NewStudentReportController@ExportNewPercentExcel');

// All Student Report
Route::get('AllStudent/YearList', 'AllStudentReportController@YearList');
Route::get('AllStudent/FacultyList', 'AllStudentReportController@FacultyList');
Route::get('AllStudent/DepartmentList', 'AllStudentReportController@DepartmentList');
Route::get('AllStudent/EducationList', 'AllStudentReportController@EducationList');
Route::post('AllStudent/Import', 'AllStudentReportController@ImportAllExcel');
Route::post('AllStudent/Export', 'AllStudentReportController@ExportAllExcel');
Route::post('AllStudent/Import/Percent', 'AllStudentReportController@ImportAllPercentExcel');
Route::post('AllStudent/Export/Percent', 'AllStudentReportController@ExportAllPercentExcel');

// Graduate Student Report
Route::get('GraduateStudent/YearList', 'GraduateStudentReportController@YearList');
Route::get('GraduateStudent/FacultyList', 'GraduateStudentReportController@FacultyList');
Route::post('GraduateStudent/Import', 'GraduateStudentReportController@ImportGraduateExcel');
Route::post('GraduateStudent/Export', 'GraduateStudentReportController@ExportGraduateExcel');
Route::post('GraduateStudent/Import/Percent', 'GraduateStudentReportController@ImportGraduatePercentExcel');
Route::post('GraduateStudent/Export/Percent', 'GraduateStudentReportController@ExportGraduatePercentExcel');

// Jasper Report
Route::Resource('generate', 'JasperController@generate');
