<?php
class WorkerLog {

	public static function error($applicationName, $cacheGroupName, $msg, $file, $line, $data = array()){
		$date = date("Y-m-d H:i:s");
		if(is_array($msg)){
			$msg = json_encode($msg);
		}
		if(empty($data)){
			$data = '';
        }
		if(is_array($data)){
			$data = json_encode($data);
		}
        if(!empty($data)){
            $data = '|'.$data;
        }
		$mssageFormat="%s|%s|%s|%s in %s on %s%s\n";
		$msg=sprintf($mssageFormat ,$date, $applicationName, $cacheGroupName, $msg, $file, $line, $data);
		$logFile = self::getLogFile("error");
		self::writeLog($msg, $logFile);
	}

	public static function writeLog($msg, $logFile){
        file_put_contents($logFile, $msg, FILE_APPEND);
	}

	public static function getLogFile($type){
		$date = date("Y-m-d");
        $file = self::path()."/{$date}.{$type}.log";
		$path = dirname($file);
		if(!file_exists($path)){
			mkdir($path, 0777, true);
		}
		return $file;
	}

	public static function path(){
        if(defined('CACHEHUB_WORKER_LOG_PATH')){
            $log_path = CACHEHUB_WORKER_LOG_PATH ; 
        }
        if(empty($log_path)) {
            $log_path = '/data/log/cacheHubWeb/worker';
        }
        $log_path = rtrim($log_path,'/');
		return $log_path;

	}
    
}
