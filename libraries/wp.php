<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');	
	
/**
 * CodeIgniter-WP Library
 *
 * @author 		peder fjällström / earth people ab
 * @copyright 	use any way you see fit
 * @version 	0.0.1
 */

define('CODEIGNITER-WP_VERSION', '0.0.4');

class Wp
{

    function __construct(){
		$this->CI =& get_instance();
		$this->installations = $this->CI->config->item('wp');
		if($this->installations){
			foreach($this->installations as $installation){
				$installation->wpconfig = $this->_get_wpconfig($installation);
			}
		}else{
			log_message('error', 'WP Spark: no installations specified in config/wp.php');
		}
	}

	public function get_installations(){
		return $this->installations;
	}
	
	public function get_post_meta($installation = '', $args = array()){
		$wpdb = $this->installations[$installation]->wpconfig->wpdb;
		$wpdb
			->select('meta_value')
			->from('postmeta')
			->where('post_id', $args['post_id'])
			->where('meta_key', $args['key']);
		$posts = $wpdb->get();
		if($posts->num_rows()>0){
			if($args['single']){
				return $posts->row()->meta_value;
			}else{
				foreach($posts->result() as $meta){
					$post_meta_array[] = $meta->meta_value;
				}
				return $post_meta_array;
			}
		}

	}

	public function wp_get_recent_posts($installation = '', $args = array()) {
		$defaults = array(
			'numberposts' => 10, 'offset' => 0,
			'category' => 0, 'orderby' => 'post_date',
			'order' => 'DESC', 'include' => '',
			'exclude' => '', 'meta_key' => '',
			'meta_value' =>'', 'post_type' => 'post', 'post_status' => 'draft, publish, future, pending, private',
			'suppress_filters' => true
		);
		$r = $this->_wp_parse_args($args, $defaults);
		$results = $this->get_posts($installation, $r);
		return $results ? $results : false;
	}
	
	public function get_post($installation = '', $ID = 0){
		$wpdb = $this->installations[$installation]->wpconfig->wpdb;
		$wpdb
			->select('*')
			->from('posts')
			->where('ID', $ID);
		$posts = $wpdb->get();
		if($posts->num_rows()>0){
			return $posts->row();
		}
	}
	
	private function get_posts($installation = '', $args = array()){
		$defaults = array(
			'numberposts' => 10, 'offset' => 0,
			'category' => 0, 'orderby' => 'post_date',
			'order' => 'DESC', 'include' => '',
			'exclude' => '', 'meta_key' => '',
			'meta_value' =>'', 'post_type' => 'post', 'post_status' => 'draft, publish, future, pending, private',
			'suppress_filters' => true
		);
		$r = $this->_wp_parse_args($args, $defaults);
		$results = $this->get_posts($installation, $r);
		
		$wpdb = $this->installations[$installation]->wpconfig->wpdb;
		$wpdb
			->select('*')
			->from('posts')
			->where('post_type', $args['post_type'])
			->where_in('post_status', explode(', ',$args['post_status']))
			->order_by($args['orderby'], $args['order'])
			->limit($args['numberposts'], $args['offset']);
		$posts = $wpdb->get();
		if($posts->num_rows()>0){
			return $posts->result();
		}
	}
	
	private function _wp_parse_args( $args, $defaults = '' ) {
		if(is_object($args)){
			$r = get_object_vars($args);
		}else if(is_array($args)){
			$r =& $args;
		}else{
			$this->_wp_parse_str($args, $r);
		}
		if(is_array($defaults)){
			return array_merge($defaults, $r);
		}
		return $r;
	}
	
	private function _wp_parse_str($string, &$array) {
		parse_str($string, $array);
	}
	
	private function _wp_connect($wpconfig){
		$db['hostname'] = $wpconfig->DB_HOST;
		$db['username'] = $wpconfig->DB_USER;
		$db['password'] = $wpconfig->DB_PASSWORD;
		$db['database'] = $wpconfig->DB_NAME;
		$db['dbdriver'] = 'mysql';
		$db['dbprefix'] = $wpconfig->DB_PREFIX ?: 'wp_';
		$db['pconnect'] = TRUE;
		$db['db_debug'] = TRUE;
		$db['cache_on'] = FALSE;
		$db['cachedir'] = '';
		$db['char_set'] = $wpconfig->DB_CHARSET ?: 'utf8';
		$db['dbcollat'] = $wpconfig->DB_COLLATE ?: 'utf8_general_ci';
		$db['swap_pre'] = '';
		$db['autoinit'] = FALSE;
		$db['stricton'] = FALSE;
		return $this->CI->load->database($db, true);
	}
	
	private function _get_wpconfig($wp = ''){
		$config_file = read_file($wp->path.'wp-config.php');
		if($config_file){
			$lines = explode("\n", $config_file);
			if($lines){
				foreach($lines as $line){
					if(strstr($line,'define(')){
						$regexp = '!define\(\'(.*?)\'(.*?)\'(.*?)\'\)!';
						preg_match_all($regexp, $line, $matches);
						if(isset($matches[1][0]) && isset($matches[3][0])){
							$wpconfig->{$matches[1][0]} = $matches[3][0];
						}
					}else if(strstr($line,'$table_prefix')){
						$regexp = '!\$table_prefix(.*?)\'(.*?)\'!';
						preg_match_all($regexp, $line, $matches);
						if(isset($matches[2][0])){
							$wpconfig->DB_PREFIX = $matches[2][0];
						}			
					}
				}
				if($wpconfig){
					$wpconfig->wpdb = $this->_wp_connect($wpconfig);
					unset($wpconfig->DB_PASSWORD, $wpconfig->DB_HOST, $wpconfig->DB_NAME, $wpconfig->DB_USER);
					return $wpconfig;
				}
			}
		}else{
			log_message('error', 'WP Spark: unable to read '.$wp->path.'wp-config.php');
		}
	}

}
