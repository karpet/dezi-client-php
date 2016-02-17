<?php

/*

Doc - a Dezi client document

SYNOPSIS

 // add doc to the index
 $html = "<html>hello world</html>";
 $doc = new \Dezi\Doc(array(
     'mime_type' => 'text/html',
     'uri'       => 'foo/bar.html',
     'mtime'     => time(),
     'size'      => length $html,
     'content'   => $html,
 ));
 $client->index( $doc );

 $doc2 = new \Dezi\Doc(array(
    'uri' => 'auto/xml/magic',
 ));
 $doc2->set_field('title', 'ima magic');
 $doc2->set_field('foo', array('one', 'two'));
 $doc2->set_field('body', 'hello world!');
 $client->index( $doc2 );

 // search results are also Doc objects
 foreach ($response->results as $doc) {
     printf("hit: %s %s\n", $doc->score, $doc->uri);
 }

*/

namespace Dezi;

class Doc {

    public $mime_type;
    public $summary;
    public $title;
    public $content;
    public $uri;
    public $mtime;
    public $size;
    public $score;
    private $fields = array();
    protected $VERSION = '2.0.1';

    private static $mime_types = array(
        'txt' => 'text/plain',
        'htm' => 'text/html',
        'html' => 'text/html',
        'php' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'swf' => 'application/x-shockwave-flash',
        'flv' => 'video/x-flv',

        // images
        'png' => 'image/png',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'ico' => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        'svg' => 'image/svg+xml',
        'svgz' => 'image/svg+xml',

        // archives
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        'exe' => 'application/x-msdownload',
        'msi' => 'application/x-msdownload',
        'cab' => 'application/vnd.ms-cab-compressed',

        // audio/video
        'mp3' => 'audio/mpeg',
        'qt' => 'video/quicktime',
        'mov' => 'video/quicktime',

        // adobe
        'pdf' => 'application/pdf',
        'psd' => 'image/vnd.adobe.photoshop',
        'ai' => 'application/postscript',
        'eps' => 'application/postscript',
        'ps' => 'application/postscript',

        // ms office
        'doc' => 'application/msword',
        'rtf' => 'application/rtf',
        'xls' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',

        // open office
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
    );



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
     * Static method. Returns MIME type string for a file.
     *
     * @param string  $file
     * @return string mime_type
     */
    public static function get_mime_type($file) {
        $ext = strtolower(array_pop(explode('.', $file)));
        //error_log("file=$file ext=$ext");

        if (array_key_exists($ext, self::$mime_types)) {
            //error_log("mime=".self::$mime_types[$ext]);
            return self::$mime_types[$ext];
        }
        elseif (file_exists($file) && function_exists('mime_content_type')) {
            //error_log("mime_content_type for $file");
            return mime_content_type($file);
        }
        else {
            return 'application/octet-stream';
        }
    }


    /**
     * as_string - return Doc object, serialized.
     *
     * @return string
     */
    public function as_string() {
        if (count($this->fields)) {
            return $this->to_xml();
        }
        else {
            return $this->content;
        }
    }



    /**
     * get_field
     *
     * @param string  $name
     * @return string
     */
    public function get_field($name) {
        if (!isset($this->fields[$name])) {
            return null;
        }
        else {
            return $this->fields[$name];
        }
    }



    /**
     * set_field
     *
     * @param string  $name
     * @param string_or_array $value
     */
    public function set_field($name, $value) {
        $this->fields[$name] = $value;
    }


    /**
     * to_xml - convert fields to XML
     *
     * @return string $xml
     */
    public function to_xml() {
        $this->mime_type = 'application/xml';
        $xml = "<doc>";
        foreach ($this->fields as $f=>$v) {
            $f_safe = htmlspecialchars($f, ENT_QUOTES, 'UTF-8');
            if (is_array($v)) {
                foreach ($v as $value) {
                    $xml .= sprintf("<%s>%s</%s>", $f_safe, htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), $f_safe);
                }
            }
            else {
                $xml .= sprintf("<%s>%s</%s>", $f_safe, htmlspecialchars($v, ENT_QUOTES, 'UTF-8'), $f_safe);
            }
        }
        $xml .= "</doc>";

        //error_log($xml);

        return $xml;

    }



}
