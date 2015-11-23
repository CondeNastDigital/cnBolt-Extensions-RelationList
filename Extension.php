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
    private $dataValidation;

    private static $typeName = "relationlist";

    public function __construct(Application $app)
    {
        parent::__construct($app);
        $this->app['config']->getFields()->addField(new RelationListField());
        if ($this->app['config']->getWhichEnd()=='backend') {
            $this->app['htmlsnippets'] = true;
            $this->app['twig.loader.filesystem']->prependPath(__DIR__."/twig");
        }

        // TODO: Evaluate, if the backend validation is possible
        //$this->app['dispatcher']->addListener(StorageEvents::PRE_SAVE, array("Bolt\Extension\CND\RelationList\Extension", 'presaveRelationList'));
    }


    /**
     * Callback method that controls the data validation for this extensions new field type
     * 
     * @param  \Bolt\Events\StorageEvent $event
     * 
     * @return boolean True, is validation passed
     */
    /*
    public function presaveRelationList( \Bolt\Events\StorageEvent $event ) {
      // get content + config
      $contentType = $event->getContentType();
      $content = $event->getContent();

      // get `relationlist` fields
      $fields = Extension::getRelationListFields( $content );
      $messageCount = 0;
      $validationResults = array();

      // validate fields
      foreach ( $fields as $key => $field ) {
        $validation = Extension::validateRelationListField( $field, $content );
        $validationResults[] = $validation;
        $messageCount += count($validation);
      }

      global $app;

      if ( $messageCount > 0 ) {
        $app['session']->getFlashBag()->add('error', "There is a problem, sir!");
        return false;
      }

      $app['session']->getFlashBag()->add('success', "Everything alright!");
      return true;
    }
    */


    /**
     * Validate contenttype field based on the field information
     * 
     * @param  array $field - Field information array
     * 
     * @return array List of messages
     */
    /*
    private static function validateRelationListField( array $field, \Bolt\Content &$content ) {
      if ( !isset($field) || count(array_keys($field)) !== 1 )
        return array("Internal error: Invalid field infos!");

      $errors = array();
      $fieldName = array_keys($field)[0]; // access field info
      $fieldInfos = $field[$fieldName];

      if ( !isset( $fieldInfos["options"] ) )
        return array();

      $options = $fieldInfos["options"];
      $fieldValue = $content->get($fieldName);
      $fieldValue = json_decode($fieldValue, true);

      /*
        Check `min` option
       *
      if ( isset( $options["min"] ) && is_numeric($options["min"]) ) {
        $min = intval($options["min"]);

        if ( count($fieldValue) < $min ) {
          $errors[$fieldName] = "Please select at least " . $min . " item(s)!";
        }
      }

      /*
        Check `max` option
       *
      if ( isset( $options["max"] ) && is_numeric($options["max"]) ) {
        $max = intval($options["max"]);

        if ( count($fieldValue) > $max ) {
          $errors[$fieldName] = "Please select a maximum of " . $min . " item(s)!";
        }
      }

      return $errors;
    }
    */

    /**
     * Retrieve the field infos for the content object`s relation list fields
     * 
     * @param \Bolt\Content &$content
     * 
     * @return array Field info list that matches the typeName of this class
     */
    /*
    
    private static function getRelationListFields( \Bolt\Content &$content ) {
      $result = array();
      $fields =  array_keys( $content->getValues() );

      // get field config
      foreach( $fields as $field ) {
        $fi = $content->fieldinfo($field);
        if ( $fi["type"] == Extension::$typeName ) {
          $result[] = array($field => $fi);
        }
      }

      return $result;
    }
    */

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
     * @return [type] [description]
     */
    public function getName()
    {
        return Extension::$typeName;
    }

    /**
      * Create an JSON response with error message
      */
    private function makeErrorResponse( $message ) {
      return new JsonResponse( json_encode(array(
          "status" => "error",
          "message" => $message
        )));
    }

    /**
      * Create an JSON response with error message
      */
    private function makeDataResponse( $data ) {
      return new JsonResponse( json_encode(array(
          "status" => "okay",
          "data" => $data
        )));
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

        $results = array();
        foreach ( $elements as $element ) {
            $element = explode("/", $element);
            $contentType = $element[0];
            $elementId = $element[1];

            /*
              TODO: Reduce number of database requests by mapping the array
              and use $this->app["storage"]->getContent('pages', array('id' => array(3, 4, 5)))
            */
            $obj = $this->getContentObjectById( $contentType, $elementId, array("id", "title", "slug", "datechanged") );

            $contentObject = new Content( $this->app, $contentType, $obj );

            $obj["id"] = implode("/", $element);
            $obj["contenttype"] = $contentType;
            $obj["link"] = $contentObject->editlink();

            if ( $obj ) $results[] = $obj;
        }

        return $this->makeDataResponse( array("results" => $results) );
    }

    /**
      * Validate id schema within a list of ids
      * Invalid Ids will be deleted
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
      * Fetch a single content object with the given object id
      *
      * @param string $contenttype
      * @param int|string $id
      * @param array $fields - (Optional) Define fields to retrieve. If `null`, retrives all fields available.
      */    
    private function getContentObjectById( $contenttype, $id, $fields = null )
    {
        if ( !is_string( $contenttype ) || !is_numeric($id) )
            throw new Exception("Invalid parameter type!");

        if ( $fields == null )
            $fields = "*";

        $tableName = $this->app["storage"]->getContenttypeTablename( $contenttype );

        $query = "SELECT " . implode(", ", $fields) . " FROM " . $tableName . " WHERE id = " . $id . ";";
        $queryResults = $this->app['db']->fetchAll( $query );

        if ( count($queryResults) > 1 )
            throw new Exception("Invalid result count! Something went wrong!");

        if ( count($queryResults) == 0 )
            return null;

        return $queryResults[0];
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
        // TODO: Limit results!
        // TODO: filter by status = `published`
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


