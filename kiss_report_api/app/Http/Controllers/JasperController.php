<?php

namespace App\Http\Controllers;

use Auth;
use File;
use Illuminate\Http\Request;
use Log;
use DB;
use Storage;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Response  as FacadeResponse;
use Illuminate\Support\Facades\Input;
use App\DatabaseConnection;
use App\DatabaseType;

class JasperController extends Controller
{
    public function generate(Request $request)
    {
        $name_gen = md5(uniqid(rand(), true));
        $template_name = $request->template_name;
        $template_format = $request->template_format;
        $used_connection = $request->used_connection;
        $is_inline = $request->inline;

        $db_connection = env("DB_CONNECTION");
        $db_host = env("DB_HOST");
        $db_database = env("DB_DATABASE");
        $db_username = env("DB_USERNAME");
        $db_password = env("DB_PASSWORD");
        $db_port = env("DB_PORT");
        if(!empty($used_connection) && $used_connection != '0') {
            $databaseConnection = DatabaseConnection::where('is_report_connection', '=', $used_connection)->first();
            if(empty($databaseConnection)) {
                return "You are not set is_report_connection";
            } else {
                //$databaseConnection = DatabaseConnection::find('is_report_connection', '=', $used_connection);
                $databaseType = DatabaseType::find($databaseConnection->database_type_id);
                $db_connection = $databaseType->database_type;
                $db_host = $databaseConnection->ip_address;
                $db_database = $databaseConnection->database_name;
                $db_username = $databaseConnection->user_name;
                $db_password = $databaseConnection->password;
                $db_port = $databaseConnection->port;
            }
        }


        //$data = Input::all();
        //Log::info($data);
        // curl -X POST -d '{"logo":"/Users/imake/WORK/PROJECT/GJ/Jasper/jasper_service_api/resources/jasper/1588_6832_th.jpg","param_year":"2016","param_period":1,"param_level":"ALL","param_org":"ALL","param_kpi":"ALL"}' -v 'http://localhost:8000/generate?template_name=Appraisal_Report&template_format=pdf&used_connection=1'
        // curl -X POST -d '{"logo":"/imake/Jasper/jasper_service_api/resources/jasper/1588_6832_th.jpg","param_year":"2017","param_period":1,"param_level":"ALL","param_org":"ALL","param_kpi":"ALL"}' -v 'http://35.198.242.63:9000/generate?template_name=Appraisal_Report&template_format=pdf&used_connection=1'
        // curl -X POST -d '{}' -v 'http://localhost:8000/generate?template_name=Appraisal_Report&template_format=pdf&used_connection=1'
        // curl -X POST -d '{}' -v 'http://35.198.242.63:9000/generate?template_name=Appraisal_Report&template_format=pdf&used_connection=1'
        $params = [];
        $data_param = $request->data;
        if(!empty($data_param)){
            $params = json_decode($data_param, true);

            // foreach ($params as $key => $value) {
            //     $params[$key] =
            // }
        //return response()->json($params);
            Log::info('data_json');
            Log::info($params);
        }else{
            $params = json_decode($request->getContent(), true);
            Log::info(' from POST');
            Log::info($params);
        }

        $command = 'java -jar '.base_path('jasperStarter/lib/jasperstarter.jar').'  pr '.base_path('resources/jasper/'.$template_name.'.jasper')
            .'  -f '.$template_format.'  -o '.base_path('resources/generate/'.$name_gen);

     if(!empty($used_connection) && $used_connection == '1') {
         if (!empty($db_connection) && strlen(trim($db_connection)) > 0) {
             $command .= " -t " . $db_connection;
         }
         if (!empty($db_host) && strlen(trim($db_host)) > 0) {
             $command .= " -H " . $db_host;
         }
         if (!empty($db_database) && strlen(trim($db_database)) > 0) {
             $command .= " -n " . $db_database;
         }
         if (!empty($db_username) && strlen(trim($db_username)) > 0) {
             $command .= " -u " . $db_username;
         }
         if (!empty($db_password) && strlen(trim($db_password)) > 0) {
             $command .= " -p " . $db_password;
         }
         if (!empty($db_port) && strlen(trim($db_port)) > 0) {
             $command .= " --db-port " . $db_port;
         }
     }

        $ignore_param = ['template_name','template_format','used_connection','inline'];
        if ( !empty($params) ) {
            $command .= ' -P ';
            //$command .= ' ';
            foreach ($params as $key => $value) {
                if (!in_array($key, $ignore_param))

                  // เข้ารหัส SHA1 ให้กับข้อมูล parameter ที่จะส่งไปยัง report เนื่องจาก command line ใช้ภาษาไทยไม่ได้
                  $values = DB::select("SELECT SHA1('".$value."') as value");
                  $command .= $key.'='.$values[0]->value.' ';
            }

            if(!empty($request->subreport_bundle)){
        			if($request->subreport_bundle == "1"){
                    $command .= 'subreport_path='.base_path("resources/jasper").' ';
        				    // $params['subreport_path'] =  base_path("resources/jasper");
        			}
		        }
        }
        shell_exec($command);
        Log::info($command);
        $pathToFile = base_path('resources/generate/'.$name_gen.'.'.$template_format);

        $content_type = 'application/pdf';
        if($template_format == 'xls')
            $content_type = 'application/vnd.ms-excel';
        $headers = array(
            'Content-Type: '.$content_type,
        );

        $name = $template_name.'.'.$template_format;
        //return response()->download($pathToFile)->deleteFileAfterSend(true);
        //return  response()->download($pathToFile, $name, $headers)->deleteFileAfterSend(true);
         //$response->header('X-Frame-Options', 'SAMEORIGIN',false);
        if($is_inline == '1' && $template_format == 'pdf' ) {
            $content = file_get_contents($pathToFile);
            File::delete($pathToFile);
            return FacadeResponse::make($content, 200,
                array('content-type' => 'application/pdf', 'Content-Disposition' => 'inline;  filename= ' .$name));
        }else{
            return  response()->download($pathToFile, $name, $headers)->deleteFileAfterSend(true);
        }
    }

}
