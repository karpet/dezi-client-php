<?php


// $pest_path = realpath(dirname(__FILE__).'/../pest');
// //error_log("pest_path=$pest_path");
// set_include_path(get_include_path() . PATH_SEPARATOR . $pest_path);
// require_once 'PestJSON.php';
// require_once 'Dezi_Doc.php';
// require_once 'Dezi_Response.php';
// require_once 'Dezi_HTTPResponse.php';

/*

Dezi_Client - interact with a Dezi server

SYNOPSIS

 require_once 'Dezi_Client.php';

 # open a connection
 my $client = new Dezi::Client(array(
    'server'  => 'http://localhost:5000',
 ));

 # add/update a filesystem document to the index
 $client->index( 'path/to/file.html' );

 # add/update an in-memory document to the index
 $client->index( $html_doc, 'foo/bar.html' );

 # add/update a Dezi::Doc to the index
 $client->index( $dezi_doc );

 # remove a document from the index
 $client->delete( '/doc/uri/relative/to/index' );

 # search the index
 my $response = $client->search(array( 'q' => 'foo' ));

 # iterate over results
 foreach ($response->results as $result) {
     printf("--\n uri: %s\n title: %s\n score: %s\n",
        $result->uri, $result->title, $result->score);
 }

 # print stats
 printf("       hits: %d\n", $response->total);
 printf("search time: %s\n", $response->search_time);
 printf(" build time: %s\n", $response->build_time);
 printf("      query: %s\n", $response->query);

*/

namespace Dezi;

class Dezi_Client {

    public $server = 'http://localhost:5000';
    public $about_server;
    public $search_uri;
    public $index_uri;
    public $commit_uri;
    public $rollback_uri;
    public $fields;
    public $facets;
    public $last_response;
    private $username;
    private $password;
    public static $VERSION = '0.002003';



    /**
     * Private method. Creates user agent.
     *
     * @param string  $url_base
     * @param unknown $cls      (optional)
     * @return PestJSON object
     */
    private function _new_user_agent($url_base, $cls='PestJSON') {
        $pest = new $cls($url_base);
        $pest->curl_opts[CURLOPT_USERAGENT] = 'dezi-client-php ' . self::$VERSION;
        if ($this->username && $this->password) {
            $pest->curl_opts[CURLOPT_USERPWD] = $this->username . ':' . $this->password;
        }
        return $pest;
    }


    /**
     * Constructor. Returns a new Client.
     *
     * @param array   $args (optional)
     */
    public function __construct($args=array()) {
        if (isset($args['server'])) {
            $this->server = $args['server'];
        }
        if (array_key_exists('search', $args) && array_key_exists('index', $args)) {
            $this->search_uri = $args['server'] . $args['search'];
            $this->index_uri  = $args['server'] . $args['index'];
        }
        else {

            $pest = $this->_new_user_agent($this->server);
            try {
                $resp = $pest->get('/');
            }
            catch (Pest_NotFound $err) {
                // 404
                die(sprintf("404 response from server: %s/\n", $this->server));
            }
            catch (Pest_UnknownResponse $err) {
                die(sprintf("Initial connect failed to server: %s/\n", $this->server));
            }

            //error_log( var_export($resp, true) );
            $this->about_server = $resp;
            $this->search_uri = $resp['search'];
            $this->index_uri  = $resp['index'];
            $this->fields     = $resp['fields'];
            $this->facets     = $resp['facets'];
            $this->commit_uri = $resp['commit'];
            $this->rollback_uri = $resp['rollback'];
        }
        $this->username = isset($args['username']) ? $args['username'] : null;
        $this->password = isset($args['password']) ? $args['password'] : null;

    }


    /**
     * ping() - test if the server is alive.
     *
     * @return false on failure, JSON on success
     */
    public function ping() {
        $pest = $this->_new_user_agent($this->server);
        try { $resp = $pest->get('/'); }
        catch(Pest_NotFound $err) { return false; }
        catch(Pest_UnknownResponse $err) { return false; }
        return $resp;
    }



    /**
     * index() - add or update a document in the index
     *
     * Expects either a string file, string in-memory document,
     * or a Dezi_Doc object.
     *
     * @param string_or_object $doc
     * @param string  $uri          (optional)
     * @param string  $content_type (optional)
     * @return Dezi_HTTPResponse $resp
     */
    public function index($doc, $uri=null, $content_type=null) {
        $buf = null;
        $mtime = time();
        if (!is_object($doc) && file_exists($doc)) {
            $buf = file_get_contents($doc);
            $mtime = filemtime($doc);
            if ($uri==null) {
                $uri = $doc;
            }
        }
        elseif (!is_object($doc)) {
            $buf = $doc;
            if ($uri==null) {
                throw new \Exception("uri required");
            }
        }
        elseif (is_object($doc) && is_a($doc, '\Dezi\Dezi_Doc')) {
            $buf = $doc->as_string();
            $mtime = $doc->mtime;
            if ($uri==null) {
                $uri = $doc->uri;
            }
            if ($content_type==null) {
                $content_type = $doc->mime_type;
            }
        }
        else {
            throw new \Exception("doc must be a file, in-memory buffer, or \Dezi\Dezi_Doc object");
        }

        if ($content_type==null) {
            $content_type = Dezi_Doc::get_mime_type($uri);
        }

        //error_log("content_type=$content_type");
        $pest = $this->_new_user_agent($this->index_uri, 'Pest');
        $resp = $pest->post("/$uri", $buf, array(
                'Content-Type: '.$content_type,
                'X-SOS-Last-Modified: '.$mtime,
            )
        );
        $http_resp = new Dezi_HTTPResponse();
        $http_resp->status = $pest->lastStatus();
        $http_resp->content = $resp;

        return $http_resp;

    }



    /**
     * search() - Fetch search results from a Dezi server.
     *
     * $params may be any key/value pair as described in Search::OpenSearch.
     *
     * Returns a Dezi_Response on success and 0 on failure. Check
     * $client->last_response on failure.
     *
     * @param array   $params
     * @return Dezi_Response object
     */
    public function search($params) {
        $pest = $this->_new_user_agent($this->search_uri);
        $params['t'] = 'JSON';  // force response type
        $query = http_build_query($params);
        $resp = $pest->get("?$query");
        $http_resp = new Dezi_HTTPResponse();
        $http_resp->status = $pest->lastStatus();
        $http_resp->content = $resp;
        $this->last_response = $http_resp;
        if ($pest->lastStatus() != '200') {
            return 0;
        }
        $dezi_response = new Dezi_Response($resp);
        return $dezi_response;
    }



    /**
     * delete() - remove a document from the index.
     *
     * $uri should be the document URI.
     *
     * @param string  $uri
     * @return Dezi_HTTPResponse $resp
     */
    public function delete($uri) {
        $pest = $this->_new_user_agent($this->index_uri, 'Pest');
        $resp = $pest->delete("/$uri");
        $http_resp = new Dezi_HTTPResponse();
        $http_resp->status = $pest->lastStatus();
        $http_resp->content = $resp;
        return $http_resp;
    }


    /**
     * Returns boolean indicating whether commit() or rollback()
     * are supported by the Dezi server.
     * Lack of server support will not prevent you from
     * calling commit() or rollback(), but you should expect
     * a 400 response from those methods if this method returns false.
     *
     * @return boolean
     */
    public function server_supports_transactions() {
        foreach ($this->about_server['methods'] as $m) {
            if ($m['description'] == 'complete any pending updates') {
                return true;
            }
        }
        return false;
    }


    /**
     * commit() - complete a transaction, saving changes to the server's index.
     *
     * @return Dezi_HTTPResponse $resp
     */
    public function commit() {
        $pest = $this->_new_user_agent($this->commit_uri, 'Pest');
        $resp = $pest->post("/", "");
        $http_resp = new Dezi_HTTPResponse();
        $http_resp->status = $pest->lastStatus();
        $http_resp->content = $resp;

        return $http_resp;
    }





    /**
     * rollback() - abort a transaction
     *
     * @return Dezi_HTTPResponse $resp
     */
    public function rollback() {
        $pest = $this->_new_user_agent($this->rollback_uri, 'Pest');
        $resp = $pest->post("/", "");
        $http_resp = new Dezi_HTTPResponse();
        $http_resp->status = $pest->lastStatus();
        $http_resp->content = $resp;

        return $http_resp;
    }


}
