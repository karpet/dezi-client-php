<?php

class Dezi_Doc {

    public $mime_type;
    public $summary;
    public $title;
    public $content;
    public $uri;
    public $mtime;
    public $size;
    public $score;
    protected $VERSION = '0.001000';



    /**
     *
     *
     * @param unknown $args (optional)
     */
    public function __construct($args=array()) {
        foreach ($args as $k=>$v) {
            $this->$k = $v;
        }
    }


    /**
     *
     *
     * @param unknown $file
     * @return unknown
     */
    public function get_mime_type($file) {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME);

            if (!$finfo) {
                throw new Exception("Opening fileinfo database failed");
            }

            $types = finfo_file($finfo, $file);

            return $types[0];
        }
        elseif (function_exists('mime_content_type')) {
            return mime_content_type($file);
        }
        else {
            throw new Exception("No MIME content type detection support in this PHP version");
        }
    }














}
