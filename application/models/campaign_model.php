<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class campaign_model extends CI_Model {


	//var $pagination	 		= NULL;
	var $jurisdictions 		= array();


	var $protected_field	= null;


	public function __construct(){
		parent::__construct();
		
		$this->load->helper('api');					
		
		
		// Determine the environment we're run from for debugging/output 
		if (php_sapi_name() == 'cli') {   
			if (isset($_SERVER['TERM'])) {   
				$this->environment = 'terminal';  
			} else {   
				$this->environment = 'cron';
			}   
		} else { 
			$this->environment = 'server';
		}						
						
		//$this->office					= $this->office();

	}
	
	public function datagov_office($office_id) {
	
		$this->db->select('*');		
		$this->db->where('office_id', $office_id);				
		$query = $this->db->get('datagov_campaign');
        
		if ($query->num_rows() > 0) {
		   return $query->row();				
		} else {
		   return false; 
		}		
		
	}
	
	
	public function datagov_model() {
		
		$model = new stdClass();

		$model->office_id						= null;               
		$model->contact_name					= null;            
		$model->contact_email					= null;           
		$model->datajson_status					= null;  			          
		$model->datapage_status					= null; 					
		$model->digitalstrategy_status			= null; 							

		$model->datagov_harvest					= null;	
		$model->inventory_posted				= null;	
		$model->inventory_superset				= null;	
		$model->datajson_posted					= null;	
		$model->datajson_slashdata				= null;	
		$model->feedback						= null;	
		$model->schedule_posted					= null;	
		$model->publication_process_posted		= null;	
		
		return $model;
	}


	public function note_model() {
		
		$model = new stdClass();

		$model->date							= null;               
		$model->author							= null;            
		$model->note							= null;           
		$model->note_html						= null;  			          
	
		$note = new stdClass();

		$note->current							= $model; 
		
		return $note;
	}

	
	





	public function datajson_crawl() {
		
		$model = new stdClass();

		$model->id 					= null;
		$model->office_id 			= null;
		$model->datajson_url 		= null;
		$model->crawl_cycle 		= null;
		$model->crawl_status 		= null;
		$model->start 				= null;		
		$model->end 				= null;				
		
		return $model;
	}	


	public function metadata_record() {

		$model = new stdClass();
		
		$model->id 						= null;
		$model->office_id 				= null;
		$model->datajson_url 			= null;
		$model->identifier 				= null;
		$model->json_body 				= null;
		$model->schema_valid 			= null;
		$model->validation_errors 		= null;
		$model->last_modified_header 	= null;
		$model->last_modified_body 		= null;
		$model->last_crawled 			= null;
		$model->crawl_cycle 			= null;
		
		return $model;
	}



	public function metadata_resource() {

		$model = new stdClass();

		$model->id                         = null;
 		$model->metadata_record_id         = null;
 		$model->metadata_record_identifier = null;
 		$model->url                        = null;
		
		return $model;
	}




 

	
	
	public function uri_header($url, $redirect_count = 0) {
		
		$status = curl_header($url);	
		$status = $status['info'];	//content_type and http_code		

		if($status['redirect_count'] == 0 && !(empty($redirect_count))) $status['redirect_count'] = 1;		
		$status['redirect_count'] = $status['redirect_count'] + $redirect_count;

		if(!empty($status['redirect_url'])) {
			if($status['redirect_count'] == 0 && $redirect_count == 0) $status['redirect_count'] = 1;
			
			if ($status['redirect_count'] > 5) return $status;
			$status = $this->uri_header($status['redirect_url'], $status['redirect_count']);
		}		
		
		if(!empty($status)) {
			return $status;
		} else {
			return false; 
		}
	}
		
	
	public function validate_datajson_old($uri) {
		
		$this->load->helper('jsonschema');					

		$schema = json_decode(file_get_contents(realpath('./schema/catalog.json')));		

		if($data = @file_get_contents($uri)) {
    		$data = json_decode($data);
        
    		if(!empty($data)) {
    			return Jsv4::validate($data, $schema);
    		} else {
    			return false;
    		}		    
		} else {
		    return false;
		}
	}
	
	public function validate_datajson($datajson_url = null, $datajson = null, $headers = null, $schema = null, $return_source = false) {


		if ($datajson_url) {
			

			$datajson_header = ($headers) ? $headers : $this->campaign->uri_header($datajson_url);
			//$datajson = json_encode($datajson_header);

			$errors = array();

			//if($datajson_header['http_code'] !== 200) {
			//	$errors[] = "The URL for the data.json file is not accessible";
			//}			

			// Max file size
			$max_size = $this->config->item('max_size');

			// Load the JSON
			if(empty($datajson_header['download_content_length']) || $datajson_header['download_content_length'] < $max_size) {

				$opts = array(
							  'http'=>array(
							    'method'=>"GET",
							    'user_agent'=>"Data.gov data.json crawler"							              
							  )
							);

				$context = stream_context_create($opts);	

				$datajson = file_get_contents($datajson_url, false, $context);
			}

			if(!empty($datajson) && (empty($datajson_header['download_content_length']) || $datajson_header['download_content_length'] < 0)) {
				$datajson_header['download_content_length'] = strlen($datajson);
			}			

			// Set max size around 5mb
			if($datajson_header['download_content_length'] > $max_size) {
				
				$filesize = human_filesize($datajson_header['download_content_length']);
				$errors[] = "The data.json file is " . $filesize . " which is currently too large to parse with this tool. Sorry.";						
			}     	

			if(!empty($errors)) {

				$valid_json = is_json($datajson);

				return array('valid_json' => $valid_json, 'valid' => false, 'fail' => $errors, 'download_content_length' => $datajson_header['download_content_length']);				
			}


		}   

		if ($datajson) {

	        // Clean up the data a bit

		    /*
		    This is to help accomodate encoding issues, eg invalid newlines. See: 
		    http://forum.jquery.com/topic/json-with-newlines-in-strings-should-be-valid#14737000000866332 
		    http://stackoverflow.com/posts/17846592/revisions
		    */
		    $datajson = preg_replace('/[ ]{2,}|[\t]/', ' ', trim($datajson)); 
            //$data = str_replace(array("\r", "\n", "\\n", "\r\n"), " ", $data);	
            //$data = preg_replace('!\s+!', ' ', $data);
            //$data = str_replace(' "', '"', $data);            
            $datajson = preg_replace('/,\s*([\]}])/m', '$1', utf8_encode($datajson));               
		    
            
            /* 
		    This is to replace any possible BOM "Byte order mark" that might be present
		    See: http://stackoverflow.com/questions/10290849/how-to-remove-multiple-utf-8-bom-sequences-before-doctype
		    and
		    http://stackoverflow.com/questions/3255993/how-do-i-remove-i-from-the-beginning-of-a-file
		    */    
            // $bom = pack('H*','EFBBBF');
            // $datajson = preg_replace("/^$bom/", '', $datajson);
            $datajson = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $datajson);
		}

		
		if ($datajson && is_json($datajson)) {

			$response = $this->campaign->jsonschema_validator($datajson, $schema);	

			$catalog = json_decode($datajson);

			$response['total_records'] = count($catalog);
			$response['valid_json'] = true;

			if ($return_source) {
				$response['source'] = $catalog;
			}

			return $response;

		} else {
			$errors[] = "This does not appear to be valid JSON";		
			return array('valid_json' => false, 'valid' => false, 'fail' => $errors, 'download_content_length' => $datajson_header['download_content_length']);				
		}
		


	}	
	
	public function jsonschema_validator($data, $schema = null) {

        
		if($data) {	 

			$schema_variant = (!empty($schema)) ? "$schema/" : "";
			$path = './schema/' . $schema_variant . 'catalog.json';

			//echo $path; exit;

			// Get the schema and data as objects
	        $retriever = new JsonSchema\Uri\UriRetriever;       	                   
	        $schema = $retriever->retrieve('file://' . realpath($path));
		       

        	 //header('Content-type: application/json');
        	 //print $data;
        	 //exit; 	 		    
    	 		    		
    		$data = json_decode($data);
    		    		
		    if(!empty($data)) {    		
                // If you use $ref or if you are unsure, resolve those references here
                // This modifies the $schema object
                $refResolver = new JsonSchema\RefResolver($retriever);
                $refResolver->resolve($schema, 'file://' . __DIR__ . '/../../schema/' . $schema_variant);

                // Validate
                $validator = new JsonSchema\Validator();
                $validator->check($data, $schema);

                if ($validator->isValid()) {
                    $results = array('valid' => true, 'errors' => null);
                } else {                
                    $errors =  $validator->getErrors();                                

                    $results = array('valid' => false, 'errors' => $errors);                    
                }    		
            
          	   //header('Content-type: application/json');
          	   //print json_encode($results);
          	   //exit;            
            
                return $results;
            } else {
                return false;
            }
    		
    	}  



	}

	
	

	
	public function update_status($update) {		
		
		$this->db->select('datajson_status');		
		$this->db->where('office_id', $update->office_id);						
		$query = $this->db->get('datagov_campaign');				
		
		if ($query->num_rows() > 0) {
			// update
			
			if ($this->environment == 'terminal') {
				echo 'Updating ' . $update->office_id . PHP_EOL . PHP_EOL;
			}	
			
			//$current_data = $query->row_array();				
			//$update = array_mash($update, $current_data);
			
			$this->db->where('office_id', $update->office_id);						
			$this->db->update('datagov_campaign', $update);					
			
			
			
		} else {
			// insert
			
			if ($this->environment == 'terminal') {
				echo 'Adding ' . $update->office_id . PHP_EOL . PHP_EOL;
			}					
			
			$this->db->insert('datagov_campaign', $update);					
			
		}		
		
	}


	public function update_note($update) {		
		
		$this->db->select('note');		
		$this->db->where('office_id', $update->office_id);	
		$this->db->where('field_name', $update->field_name);	
		$query = $this->db->get('notes');				
		
		if ($query->num_rows() > 0) {
			// update
			
			if ($this->environment == 'terminal') {
				echo 'Updating ' . $update->office_id . PHP_EOL . PHP_EOL;
			}	
			
			//$current_data = $query->row_array();				
			//$update = array_mash($update, $current_data);
			
			$this->db->where('office_id', $update->office_id);	
			$this->db->where('field_name', $update->field_name);	

			$this->db->update('notes', $update);					
			
			
			
		} else {
			// insert
			
			if ($this->environment == 'terminal') {
				echo 'Adding ' . $update->office_id . PHP_EOL . PHP_EOL;
			}					
			
			$this->db->insert('notes', $update);					
			
		}		
		
	}

	public function get_notes($office_id) {
		
		$query = $this->db->get_where('notes', array('office_id' => $office_id));	

		return $query;
		
	}		

	
	
	
	
	public function datajson_schema() {
		
		$schema = json_decode(file_get_contents(realpath('./schema/catalog.json')));

		if (!empty($schema->items->{'$ref'})) {
			
			$schema = json_decode(file_get_contents(realpath('./schema/' . $schema->items->{'$ref'})));

		}		
		return $schema;
		
	}
	
	
	public function schema_to_model($schema) {
		
		$model = new stdClass();
		
		foreach ($schema as $key => $value) {
			
			if(!empty($value->items) && $value->type == 'array') {
				 $model->$key = array();								
			} else {
				$model->$key = null;				
			}
			
		}
		
		return $model;
		
	}
	
	public function get_datagov_json($orgs, $geospatial = false, $rows = 100, $offset = 0, $raw = false, $allow_harvest_sources = 'true') {
		
		$allow_harvest_sources = (empty($allow_harvest_sources)) ? 'true' : $allow_harvest_sources; 

		if ($geospatial == 'both') {
		    $filter = "%20";
		} else if ($geospatial == 'true') {
		    $filter = 'metadata_type:geospatial%20AND%20';
		} else {
			$filter = '-metadata_type:geospatial%20AND%20';
		}

		if ($allow_harvest_sources !== 'true') {
			$filter .= "AND%20-harvest_source_id:[''%20TO%20*]";
		} 

		if(strpos($orgs, 'http://') !== false) {

			$uri = $orgs;
			$from_export = true;

		} else {

			$orgs = rawurlencode($orgs);
			$query = $filter . "-type:harvest%20AND%20organization:(" . $orgs . ")&rows=" . $rows . '&start=' . $offset;
			$uri = 'http://catalog.data.gov/api/3/action/package_search?q=' . $query;
			$from_export = false;
		}

		$datagov_json = curl_from_json($uri, false);
				
		if($from_export) {

			$object_shim = new stdClass();
			$object_shim->result 			= new stdClass();
			$object_shim->result->count 	= count($datagov_json);
			$object_shim->result->results 	= $datagov_json;

			$datagov_json = $object_shim;
		}

		if(empty($datagov_json)) return false;
				
		if($raw == true) {			
			return $datagov_json;
		} else {			
			return $datagov_json->result->results;
		}
		
	}
	
	public function datajson_crosswalk($raw_data, $datajson_model) {
	
		$distributions = array();
		foreach($raw_data->resources as $resource) {
			$distribution = new stdClass();
			
			$distribution->accessURL 	= $resource->url;
			$distribution->format		= $resource->format;
			
			$distributions[] = $distribution;			
		}
	
		if(!empty($raw_data->tags)) {
			$tags = array();
			foreach ($raw_data->tags as $tag) {
				$tags[] = $tag->name;				
			}
		} else {
			$tags = null;
		}
		
		if(!empty($raw_data->extras)) {
		    
		    foreach($raw_data->extras as $extra) {
		        
		        if ($extra->key == 'tags') {
		            $extra_tags = $extra->value;
		            $datajson_model->keyword = (!empty($extra_tags)) ? array_map('trim',explode(",",$extra_tags)) : null;
		        }
		        
		        if ($extra->key == 'data-dictiionary' OR $extra->key == 'data-dictionary') {
		            $datajson_model->dataDictionary = $extra->value;
		        }

		        if ($extra->key == 'person') {
		            $datajson_model->contactPoint = $extra->value;
		        }
		        
		        if ($extra->key == 'contact-email') {
		            $datajson_model->mbox = $extra->value;
		        }	
		        
		        if ($extra->key == 'frequency-of-update') {
		            $datajson_model->accrualPeriodicity = $extra->value;
		        }	        		        
		        
		        if ($extra->key == 'issued') {
		            $datajson_model->issued = date(DATE_ISO8601, strtotime($extra->value));
		        }		        
		        
		        if ($extra->key == 'theme') {
		            $datajson_model->theme = $extra->value;
		        }		        
		        
		        if ($extra->key == 'access-level') {
		            $datajson_model->accessLevel = $extra->value;
		        }
		        
		        if ($extra->key == 'license' OR $extra->key == 'licence') {
		            $license = trim($extra->value);
		            
		            if(!empty($license)) {
		                $datajson_model->license = $license;
		            }
		            
		        }		        		        
		        
		        
		        
		    }
		    
		    
        }	
        
	
		
		
	    $datajson_model->accessURL                          = null; 
//		$datajson_model->accessLevel                        = $datajson_model->accessLevel;
		$datajson_model->accessLevelComment                 = null;
//		$datajson_model->accrualPeriodicity                 = $datajson_model->accrualPeriodicity;
		$datajson_model->bureauCode                         = null;
		$datajson_model->contactPoint                       = (!empty($datajson_model->contactPoint)) ? $datajson_model->contactPoint : $raw_data->maintainer;
//		$datajson_model->dataDictionary                     = $datajson_model->dataDictionary;
		$datajson_model->dataQuality                        = null;
		$datajson_model->description                        = $raw_data->notes;
		$datajson_model->distribution                       = $distributions;
	    $datajson_model->format                             = null;		
		$datajson_model->identifier                         = $raw_data->id;
//		$datajson_model->issued                             = $datajson_model->issued;
		$datajson_model->keyword                            = (!empty($datajson_model->keyword)) ? $datajson_model->keyword : $tags;
		$datajson_model->landingPage                        = null;
		$datajson_model->language                           = null;
//		$datajson_model->license                            = $datajson_model->license;
		$datajson_model->mbox                               = (!empty($datajson_model->mbox)) ? $datajson_model->mbox : $raw_data->maintainer_email;
		$datajson_model->modified                           = date(DATE_ISO8601, strtotime($raw_data->metadata_modified));
		$datajson_model->PrimaryITInvestmentUII             = null;
		$datajson_model->programCode                        = null;
		$datajson_model->publisher                          = $raw_data->organization->title;
		$datajson_model->references                         = null;
		$datajson_model->spatial                            = null;
		$datajson_model->systemOfRecords                    = null;
		$datajson_model->temporal                           = null;
//		$datajson_model->theme                              = $datajson_model->theme;
		$datajson_model->title                              = $raw_data->title;
		$datajson_model->webService                         = null;
	
		return $datajson_model;
	}	
	

}

?>