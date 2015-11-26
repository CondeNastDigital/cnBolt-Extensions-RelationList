<?php

namespace Bolt\Extension\CND\RelationList;

use Bolt;
use Bolt\Application;
use Bolt\BaseExtension;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Bolt\Events\StorageEvents;
use Bolt\Config;
use Bolt\Content;

class Extension extends BaseExtension
{
    private static $typeName = "relationlist";

    public function __construct(Application $app)
    {
        parent::__construct($app);
        $this->app['config']->getFields()->addField(new RelationListField());
        if ($this->app['config']->getWhichEnd()=='backend') {
            $this->app['htmlsnippets'] = true;
            $this->app['twig.loader.filesystem']->prependPath(__DIR__."/twig");
        }

    }


    /**
     * Initialize the extension
     * 
     * @return {null}
     */
    public function initialize() {
        $this->addJquery();
        
        $this->addJavascript('assets/RelationList.js', true);
        $this->addCss('assets/styles.css', true);

        // Add custom twig filters
        $this->addTwigFilter('json_decode', 'json_decode');
        
        // Define routes
        $this->app->get("/relationlist/finditems/{contenttype}/{field}/{search}", array($this, 'findItems'));
        $this->app->post("/relationlist/fetchJsonList", array($this, 'fetchContentElementArray'));
    }


    /**
     * Decode JSON string to an array
     * 
     * @param  string $string JSON string
     * 
     * @return array         Decoded JSON string
     */
    public function json_decode($string) {
        return json_decode($string, true);
    }
    

    /**
     * Get the field name
     * @return string
     */
    public function getName()
    {
        return Extension::$typeName;
    }

    /**
      * Create an JSON response with error message
      *
      * @return JsonResponse
      */
    private function makeErrorResponse( $message ) {
      return new JsonResponse(array(
          "status" => "error",
          "message" => $message
        ));
    }

    /**
      * Create an JSON response with error message
      * 
      * @return JsonResponse
      */
    private function makeDataResponse( $data ) {
      return new JsonResponse(array(
          "status" => "okay",
          "data" => $data
        ));
    }

    /**
      * Fetch a JSON object list of elements
      * 
      * @return Content elements as JSON
      */
    public function fetchContentElementArray( Request $request ) {
        $elements = $request->request->get("elements");

        if( !$this->app["users"]->isAllowed("edit") )
            return $this->makeErrorResponse("Insufficient access rights!");

        if ( !isset( $elements ) || !is_array( $elements ) )
            return $this->makeErrorResponse("Given elements are not in a valid format.");

        $elements = $this->filterIdentifierList( $elements );
        $elements = $this->mapContentElementIdList( $elements ); // mapping list -> prep for db queries


        $contentTypes = array_keys( $elements );
        $results = array();

        foreach ( $contentTypes as $contentType ) {
            // Retrieve content objects
            $contentObjects = $this->app["storage"]->getContent( $contentType, $elements[$contentType] );

            if ( !is_array($contentObjects) && get_class($contentObjects) == "Bolt\Content" ) {
                $newList = array();
                $newList[$contentObjects->id] = $contentObjects;
                $contentObjects = $newList;
            }

            foreach ($contentObjects as $cObject) {
                if ( !get_class($cObject) == "Bolt\Content" )
                  throw new Exception("Problem parsing getContent results!");

                $obj = array();
                $obj["id"] = $cObject->contenttype["name"] . "/" . $cObject->id;
                $obj["title"] = $cObject->getTitle();
                $dateChanged = new \DateTime( $cObject->get("datechanged") );
                $obj["datechanged"] = $dateChanged->format('Y-m-d H') . " Uhr";
                $obj["contenttype"] = $cObject->contenttype["singular_name"];
                $obj["link"] = $cObject->editlink();

                $results[] = $obj;
                $obj = null;
            }

            if ( $obj ) $results[] = $obj;
        }

        return $this->makeDataResponse( array("results" => $results) );
    }



    /**
     * Maps array in format ["Page/5", "Page/1", "Record/6"] to
     * [
     *   "Page" => ["id" => ["5", "1"]],
     *   "Record" => ["id" => ["6"]]
     * ]
     * 
     * @param  array  $elements - Holds a list of content element ids
     * 
     * @return array - Nested list of ids, grouped by content type
     */
    public function mapContentElementIdList( array $elements ) {
        $result = array();

        foreach ( $elements as $element ) {
            $element = explode("/", $element);
            $contentType = $element[0];
            $elementId = $element[1];

            $result[$contentType]['id'][] = intval($elementId);
        }

        foreach( array_keys($result) as $key ) {
            $result[$key]['id'] = implode(" || ", $result[$key]['id']);
        }

        return $result;
    }

    /**
      * Validate id schema within a list of ids
      * Invalid Ids will be deleted
      *
      * Schema: [A-Za-z0-9_]*)\/([0-9]*)
      *
      * @param Array $ids - String list of content element Ids in the forma `contenttype/id`
      *
      * @return Array - String list of validated ids
      */
    private function filterIdentifierList( $ids ) {
        $result = array();
        $id_pattern = "/([A-Za-z0-9_]*)\\/([0-9]*)/";

        foreach ( $ids as $id ) {
            if ( is_string($id) && preg_match($id_pattern, $id) )
                $result[] = $id;
        }

        return $result;
    }

    /**
     * Get a list of matching contents per type
     * 
     * @param string $contenttype
     * @param string $field
     * @param string $search
     * 
     * @return JsonResponse
     */
    public function findItems($contenttype, $field, $search){
        $contenttype = preg_replace("/[^a-z0-9\\-_]+/i", "", $contenttype);
        $field       = preg_replace("/[^a-z0-9\\-_]+/i", "", $field);

        if(!$this->app["users"]->isAllowed("contenttype:$contenttype:edit"))
            return $this->makeErrorResponse("Insufficient access rights!");

        $config = $this->getFieldConfig($contenttype, $field);

        if ( !$config )
            return $this->makeErrorResponse("Missing field configuration! Please make sure, the `options` attribute is defined according to the `README.md` file.");
        
        $allowedTypes = isset($config["allowed-types"]) ? $config["allowed-types"] : array();

        $results = array();
        $content = $this->app['storage']->searchContent($search, $allowedTypes, null, 100, 0);

        if($content["results"]) {
            foreach ($content["results"] as $entry) {
                /* @var \Bolt\Content $entry */
                $results[] = array(
                    'id' => $entry->contenttype["singular_name"].'/'.$entry->id,
                    'title' => $entry->getTitle(),
                    'contenttype' => $entry->contenttype["singular_name"],
                    'slug' => $entry->editlink(),
                    'datechanged' => date('d.m.Y, H:i', strtotime($entry->get('datechanged'))));
            }
        }
        return $this->makeDataResponse( $results );
    }

    /**
     * Determine the field config from the contenttypes.yml
     * @param string $contenttype
     * @param string $field
     * @return array|false Returns false, if there is no configuration
     */
    protected function getFieldConfig($contenttype, $field){
        $contenttype = $this->app['storage']->getContentType($contenttype);

        if(!$contenttype)
            return false;

        if(isset($contenttype["fields"][$field]["options"]))
            return $contenttype["fields"][$field]["options"];

        return false;
    }
}


