<?php

class Logger {
    
    public $log_directory;
    public $full_directory;
    public $tenant;
    public $request_token;
    
    public function __construct($tenant)
    {
        $this->tenant = $tenant;
        $this->log_directory = DIR_LOGS;
        $this->request_token = uniqid();
                
        $this->date = date("Y-m-d");
        // Check if the directory exists
        if (!is_dir($this->log_directory.$tenant)) {
            mkdir($this->log_directory.$tenant);
        }
        if (!is_dir($this->log_directory.$tenant.'/'.$this->date)) {
            mkdir($this->log_directory.$tenant.'/'.$this->date);
        }
        $this->full_directory = $this->log_directory.$tenant.'/'.$this->date;
    }

    public function write($prepend, $message, $file)
    {
        $this->log_file = fopen($this->full_directory."/$file.log", "a");

        if(is_array($prepend)) {
            $prepend['token'] = $this->request_token;
            $prepend['url'] = $_SERVER['REQUEST_URI'];
            if(isset($message['timetocomplete'])) {
                $prepend['timetocomplete'] = $message['timetocomplete'];
                unset($message['timetocomplete']);
            }
            $prepend['data'] = $message;
            fwrite($this->log_file, json_encode($prepend)."\r\n\r\n");
        } else {
            $prepend .= "[$this->request_token]\t";
            fwrite($this->log_file, $prepend."\t".$_SERVER['REQUEST_URI']."\t".$message."\r\n\r\n");
        }
    }

    public function info($message)
    {
        // log information
        $full_date = date("Y-m-d H:i:s");
        $log = array('timestamp' => $full_date, 'type' => 'info');
        $this->write($log, $message, $this->date);
       // $this->write("[INFO]\t$full_date\t", $message, "all");
    }

    public function error($message)
    {
        // log errors
        $full_date = date("Y-m-d H:i:s");
        $log = array('timestamp' => $full_date, 'type' => 'error');
        $this->write($log, $message, $this->date);
    }

    public function exec($message)
    {
        $full_date = date("Y-m-d H:i:s");
        $log = array('timestamp' => $full_date, 'type' => 'exec');
        $this->write($log, $message, $this->date);
    }

    public function db($message)
    {
        $full_date = date("Y-m-d H:i:s");
        $log = array('timestamp' => $full_date, 'type' => 'mysql');
        $frames = debug_backtrace(2,2);
        $message['trace'] = array_slice($frames, -1)[0];
        $this->write($log, $message, $this->date);
    }


}
