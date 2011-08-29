<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');	
	
/**
 * CodeIgniter-WP Library
 *
 * @author 		peder fjällström & mattias hedman / earth people ab
 * @copyright 	use any way you see fit.
 * @version 	0.2.0
 */

define('CODEIGNITER-WP_VERSION', '0.1.0');

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
	
	/* ----------------------
	Public functions
	-----------------------*/

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
			'numberposts' => 10,
			'offset' => 0,
			'category' => 0,
			'orderby' => 'post_date',
			'order' => 'DESC',
			'include' => '',
			'exclude' => '',
			'meta_key' => '',
			'meta_value' =>'',
			'post_type' => 'post',
			'post_status' => 'draft, publish, future, pending, private'
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
	
	public function wp_insert_post($installation = '', $args = array()){
		$defaults = array(
			'menu_order' => '',
			'comment_status' => 'open',
			'ping_status' => 'open',
			'pinged' => '',
			'post_author' => '',
			'post_content' => '',
			'post_date' => date('Y-m-d H:i:s'),
			'post_date_gmt' => date('Y-m-d H:i:s'),
			'post_modified_gmt' => date('Y-m-d H:i:s'),
			'post_modified' => date('Y-m-d H:i:s'),
			'post_excerpt' => '',
			'post_name' => '',
			'post_parent' => '',
			'post_password' => '',
			'post_status' => 'draft',
			'post_title' => '',
			'post_type' => 'post',
			'to_ping' => '',
		);
		
		$default_post_categorys = array('post_category' => array(1));
		if($args['post_category']){
			$post_categorys = array('post_category' => $args['post_category']);
		}
		
		$default_tags = array('tags_input' => '');
		if($args['tags_input']){
			$tags = array('tags_input' => $args['tags_input']);
		}
		
		if($args['ID']){
			$id = $args['ID'];
		}
		
		unset($args['post_category']);
		unset($args['tags_input']);
		unset($args['ID']);
		
		$r = $this->_wp_parse_args($args,$defaults);
		$wpdb = $this->installations[$installation]->wpconfig->wpdb;
		if($id) {
			if($wpdb->where('ID',$id)->update('posts',$r)) {
				return $id;
			} else {
				return false;
			}
		} else {
			if($wpdb->insert('posts',$r)) {
				return $wpdb->insert_id();
			} else {
				return false;
			}
		}
	}
	
	public function get_comments($installation = '', $args = array()){
		$defaults = array(
			'author_email' => '',
			'ID' => '',
			'karma' => '',
			'number' => '',
			'offset' => '',
			'orderby' => 'comment_date_gmt',
			'order' => 'DESC',
			'parent' => '',
			'post_id' => '',
			'status' => '',
			'type' => '',
			'user_id' => ''
		);
		
		$r = $this->_wp_parse_args($args, $defaults);
		$wpdb = $this->installations[$installation]->wpconfig->wpdb;
		$wpdb
			->select('*')
			->from('comments');
		if($r['author_email']){
			$wpdb->where_in('comment_author_email',$r['author_email']);
		}
		if($r['ID']){
			$wpdb->where_in('comment_ID',$r['ID']);
		}
		if($r['karma']){
			$wpdb->where_in('comment_karma',$r['karma']);
		}
		if($r['parent']){
			$wpdb->where_in('comment_parent',$r['parent']);
		}
		if($r['post_id']){
			$wpdb->where_in('comment_post_ID',$r['post_id']);
		}
		if($r['status']){
			$wpdb->where_in('comment_approved',$r['status']);
		}
		if($r['type']){
			$wpdb->where_in('comment_type',$r['type']);
		}
		if($r['user_id']){
			$wpdb->where_in('user_id',$r['user_id']);
		}
		$wpdb->order_by($r['orderby'], $r['order']);
		if($r['number'] >= 0){
			if($r['offset'] >= 1){
				$wpdb->limit($r['number'],$r['offset']);
			} else {
				$wpdb->limit($r['number']);
			}
		}
		$comments = $wpdb->get();
		if($comments->num_rows()>0){
			return $comments->result();
		}
	}
	
	public function wp_insert_comment($installation = '', $args = array()){
		$defaults = array(
			'comment_post_ID' => '',
			'comment_author' => '',	
			'comment_author_email' => '',
			'comment_author_url' => '',
			'comment_content' => '',
			'comment_type' => '',
			'comment_parent' => 0,
			'user_id' => '',
			'comment_author_IP' => '',
			'comment_agent' => '',
			'comment_date' => '',
			'comment_date_gmt' => '',
			'comment_approved' => ''
		);
		$r = $this->_wp_parse_args($args, $defaults);
		$wpdb = $this->installations[$installation]->wpconfig->wpdb;
		if($wpdb->insert('comments',$r)){
			return true;
		} else {
			return false;
		}
	}
	
	public function get_children($installation = '', $args = array()){
		$defaults = array(
			'post_parent'		=> 0,
			'offset'			=> 0,
			'category'			=> '',
			'orderby'			=> 'post_date',
			'order'				=> 'DESC',
			'post_status'		=> 'any',
			'post_type'			=> 'any',
			'include'			=> '',
			'exclude'			=> '',
			'meta_key'			=> '',
			'meta_value'		=> '',
			'post_mime_type'	=> '',
			'post_parent'		=> 0,
			'numberposts'		=> -1
		);
		
		$r = $this->_wp_parse_args($args, $defaults);
		$wpdb = $this->installations[$installation]->wpconfig->wpdb;
		$wpdb
			->select('*')
			->from('posts')
			->where('post_parent',$r['post_parent']);
		if($r['post_type'] !== 'any'){
			$wpdb->where_in('post_type', explode(', ',$r['post_type']));
		}
		if($r['post_status'] !== 'any'){
			$wpdb->where_in('post_status', explode(', ',$r['post_status']));
		}
		$wpdb->order_by($r['orderby'], $r['order']);
		if($r['numberposts'] !== -1){
			if($r['offset'] >= 1) {
				$wpdb->limit($r['numberposts'],$r['offset']);
			} else {
				$wpdb->limit($r['numberposts']);
			}
		}
		$posts = $wpdb->get();
		if($posts->num_rows()>0){
			return $posts->result();
		}
	}
	
	public function get_users($installation = '', $args = array()){
		$defaults = array(
			'include' => array(),
			'exclude' => array(),
			'orderby' => 'user_login',
			'order' => 'ASC',
			'offset' => '',
			'number' => ''
		);
		
		$r = $this->_wp_parse_args($args,$defaults);
		$wpdb = $this->installations[$installation]->wpconfig->wpdb;
		$wpdb
			->select('*')
			->from('users');
		if($r['include']){
			$count = count($r['include']);
			for($i=0;$i<$count;$i++){
				$wpdb->or_where('ID',$r['include'][$i]);
			}
		}
		if($r['exclude']){
			$count = count($r['exclude']);
			for($i=0;$i<$count;$i++){
				$wpdb->where_not_in('ID',$r['exclude'][$i]);
			}
		}
		$wpdb->order_by($r['orderby'], $r['order']);
		if($r['number'] !== -1){
			if($r['offset'] >= 1) {
				$wpdb->limit($r['number'],$r['offset']);
			} else {
				$wpdb->limit($r['number']);
			}
		}
		
		$posts = $wpdb->get();
		if($posts->num_rows()>0){
			return $posts->result();
		}
	}
	
	/* ----------------------
	Private functions
	-----------------------*/
	
	private function get_posts($installation = '', $args = array()){
		$defaults = array(
			'numberposts'	=> 10,
			'offset'		=> 0,
			'category'		=> 0,
			'orderby'		=> 'post_date',
			'order'			=> 'DESC',
			'include'		=> '',
			'exclude'		=> '',
			'meta_key'		=> '',
			'meta_value'	=> '',
			'post_type'		=> 'post',
			'post_status'	=> 'draft, publish, future, pending, private'
		);
		$r = $this->_wp_parse_args($args, $defaults);
		$wpdb = $this->installations[$installation]->wpconfig->wpdb;
		$wpdb
			->select('*')
			->from('posts')
			->where('post_type', $r['post_type'])
			->where_in('post_status', explode(', ',$r['post_status']))
			->order_by($r['orderby'], $r['order'])
			->limit($r['numberposts'], $r['offset']);
		$posts = $wpdb->get();
		if($posts->num_rows()>0){
			return $posts->result();
		}
	}
	
	private function _wp_parse_args($args, $defaults = '') {
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
