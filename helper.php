<?php
/************************************************************************************
 @version mod_jomsocial_photos 1.1 for Jomsocial      				                                                                                    
 @author: Ioannis Maragos - www.minitek.gr  
 @license  GNU/GPL                                                          
 ***********************************************************************************/
// no direct access
defined('_JEXEC') or die('Restricted access');

class modJomsocialPhotoshelper
{
	function getPhotos( &$params )
	{
	 				// get user id
					$user	=& JFactory::getUser();
					$my_id = $user->id; 
					
	 				// database query		
					error_reporting(E_ALL & ~E_NOTICE);	
					$list = array();			
					$query = " SELECT a.id as albumid_b, a.type as album_type, a.groupid as album_groupid, b.id as photo_id, b.storage as photo_storage, b.albumid as photo_album, b.creator as photo_creatorid, b.permissions as photo_permissions, b.caption as photo_caption, b.image as photo_image, b.thumbnail as photo_thumbnail, b.hits as photo_hits, c.id as user_id, c.name as user_name, c.username as user_username "				
					." FROM #__community_photos_albums AS a "
					." LEFT JOIN #__community_photos AS b ON a.id=b.albumid "
					." LEFT JOIN #__users AS c ON a.creator=c.id "
					." WHERE b.published=1 ";
					
					// photos type conditions
					if ($params->get( 'albumid' )=="") {
						 if ( $params->get( 'photos_type' )=='user' || $params->get( 'photos_type' )=='group' ) {
						 $type = $params->get( 'photos_type' );
						 $query .= "AND a.type='". $params->get( 'photos_type' ) . "' ";
						 }
						 if ( $params->get( 'photos_type' )=='one_user' ) {
						 $query .= "AND b.creator='". $_REQUEST["userid"]. "' ";
						 }
						 if ( $params->get( 'photos_type' )=='one_group' && $_REQUEST["groupid"]!='' ) {			
						 $query .= "AND a.groupid='". $_REQUEST["groupid"]. "' ";
						 }
					} else 
					
					// specific albums	
					if ($params->get( 'albumid' )!="") {
					//$query .= " WHERE b.published=1 "
					$query .= " AND a.id=-1 ";
					$albumIDS = $params->get( 'albumid' );
					$albums = explode(",", $albumIDS);
					$albumNumber = count($albums);
									foreach ($albums as $album) {
									$query .= "OR a.id='". $album . "' ";
									}
					}
					// privacy settings
					if ($params->get( 'permission' )=='2') {
					$query .= "AND b.permissions LIKE '%' ";
					}
					if ($params->get( 'permission' )=='1') {
						 if ($my_id==0) {
						 $query .= "AND b.permissions=0 ";
						 }
						 if ($my_id!=0) {
						 $query .= "AND (b.permissions=0 OR b.permissions=20) ";
						 }
					}
					
					// order & limit
					$query .= " ORDER BY b.".$params->get( 'order' )." DESC "
				  ." LIMIT " . $params->get( 'pool_count' );				
					$db =& JFactory::getDBO();
					$db->setQuery( $query );		
					$rows = $db->loadObjectList();
					
					// shuffle rows
					if ($params->get( 'shuffle' )==1) {
					shuffle($rows);	
					}
					
					// get list items
					$i=0;


					//Read the JomSocial Config file to get AWS Bucket
					$my_query .= "SELECT * FROM #__community_config";				
					$my_db =& JFactory::getDBO();
					$my_db->setQuery( $my_query );		
					$my_rows = $my_db->loadObjectList();

					//Grab the Config File
					$config = $my_rows[1]->params;

					//Split each new line into it's own array key
					$config = explode("\n", $config);

					//Create new Config Object to Hold Parsed Data
					$new_config = array();	

					//Loop through and make keys the name of each config item				
					foreach ($config as $key => $value) {
						$item = explode("=", $value);
						$new_config[$item[0]] = $item[1];
					}	

					//Replace Original Config Object
					$config = $new_config;

					foreach ($rows as $row) 
					{
						if ($row->photo_storage=="s3")
						{
							$row->photo_thumbnail = "http://".$config['storages3bucket'].".s3.amazonaws.com/".$row->photo_thumbnail;
						}		

						$list["photos"][$i]["photo_album"]=$row->photo_album;
						$list["photos"][$i]["photo_id"]=$row->photo_id;
						$list["photos"][$i]["album_type"]=$row->album_type;
						$list["photos"][$i]["photo_creator"]=$row->photo_creatorid;
						$list["photos"][$i]["photo_caption"]=$row->photo_caption;
						$list["photos"][$i]["photo_image"]=$row->photo_image;
						$list["photos"][$i]["photo_thumbnail"]=$row->photo_thumbnail;
						$list["photos"][$i]["photo_hits"]=$row->photo_hits;
						$list["photos"][$i]["user_name"]=$row->user_name;
						$list["photos"][$i]["user_username"]=$row->user_username;
						$list["photos"][$i]["album_groupid"]=$row->album_groupid;
						$i++;		
					}
					return $list;
					
	}
}