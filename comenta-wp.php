<?php
/*
Plugin Name: Comenta WP
Version: 0.9.11
Plugin URI: http://comenta.bloks.cat
Description: Publish all your comments directly to a twitter account.
Author: Oriol Farré i Miquel Laboria
Author URI: http://comenta.bloks.cat
*/



/*
		@todo: Demanar l'usuari de twitter al fer al comentari. Així es podran fer twitts de l'estil "Nou comentari al post XXX: http://YYY ( via @usuari )"
/*
	CHANGE LOG: 
		0.9.10 (11/05/2009):
			* Afegida la possibilitat d'enviar la URL a twitter sense escurçar. Twitter decidirà si s'ha d'escurçar o no...
			* Millorat el funcionament de retallar els titols dels posts
			
		0.9.9 (4/05/2009):
			Eliminada la publicació a twitter quan s'edita un comentari
*/		

define( "LEN_TWITTER", 125 );
define( "TAG_FINAL", "#Comenta" );

$plugin_dir = basename(dirname(__FILE__)). '/lang';
load_plugin_textdomain( 'comenta-wp', 'wp-content/plugins/' . $plugin_dir , $plugin_dir );


class comentawp {
	var $site_email;
	var $site_name;
	
	function send_notification( $cid ) {
		global $wpdb;
		$cid = ( int ) $cid;

//		$comment = $wpdb->get_row( "SELECT * FROM $wpdb->comments WHERE comment_ID = '$cid' LIMIT 1" );
		$comment = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->comments WHERE comment_ID = '%d' LIMIT 1", $cid ) );

//		$post = $wpdb->get_row( "SELECT * FROM $wpdb->posts WHERE ID = '$comment->comment_post_ID' LIMIT 1" );
		$post = $wpdb->get_row( $wpdb->prepare( "SELECT post_title FROM $wpdb->posts WHERE ID = '%d' LIMIT 1", $comment->comment_post_ID ) );

		if ( $comment->comment_approved == '1' ) {
			
			/*
				%a: Autor del comentari
				%t: Titol del post
				%u: URL de l'enllaç
			*/
			
			$msg = trim( get_option( "comenta_twitter_msg" ) );
			
			//Posa l'autor al lloc on toca
			$msg = str_replace( "%a", trim( $comment->comment_author ), $msg );
			
			// Es calcula quina longitud té el missatge escrit per l'usuari amb el nom de l'autor i el tag
			$msgOriginalNet = str_replace( "%t", "", $msg );
			$msgOriginalNet = str_replace( "%u", "", $msgOriginalNet );
			//Concatena el tag del final missatge
			$msgOriginalNet = $msgOriginalNet." ".TAG_FINAL;
			
		
			$lenMsgOriginal = strlen( $msgOriginalNet );
			
			//Caràcters màxims permesos pel títol del post
			$lenTitol = LEN_TWITTER - $lenMsgOriginal;
			
			//S'eliminen el número de caràcters sobrants del titol
			$msgTitol = $this->substrwords( trim( $post->post_title ), $lenTitol );
			
			//Posa el titol del post retallat
			$msg = str_replace( "%t", trim( $msgTitol ), $msg );
			//Posa la URL del post
			$msg = str_replace( "%u", $this->shortUrl( get_comment_link( $comment ) ), $msg );
			$msg = $msg." ".TAG_FINAL;
						
			//S'envia el missatge a twitter.
			$this->send_Twitter( $cid, $msg );
		}

		return $cid;
	}

	/**
	 *	Retorna la url escurçada pel mètode escollit.
	 */
	function shortUrl( $url ){
		$shrt = get_option( 'comenta_twitter_short_url' );
		//Si no s'ha informat cap opció sobre reducció de URL, s'utilitza el tinyurl
		if( empty( $shrt ) || $shrt = "twitter" ){
			return $url;
		}else{
			return $this->$shrt( $url );
		}
	}

	/**
	 *	Retorna la url escurçada amb TinyUrl
	 */	
	function tinyurl( $url ){
		return ( trim( file_get_contents( 'http://tinyurl.com/api-create.php?url='.$url ) ) );
	}
	
	/**
	 *	Retorna la url escurçada amb is.gd
	 */	
	function isgd( $url ){
		return ( trim( file_get_contents( 'http://is.gd/api.php?longurl='.$url ) ) );
	}
	
	/**
	 *	Retorna la url escurçada amb Lost.in
	 */	
	function lostin( $url ){
		return ( trim( file_get_contents( 'http://lost.in/?addirect='.$url ) ) );
	}


//	function on_edit( $cid ) {
//		$cid = ( int ) $cid;
//		$this->send_notification( $cid );		

//		return $cid;
//	}


	function send_Twitter( $cid, $text ) {
		$url = 'http://twitter.com/statuses/update.xml?source=comenta&status='.urlencode(stripslashes(urldecode($text)));
		$curl_handle = curl_init();
		
		$usuari = get_option( 'comenta_twitter_user' );
		$contrasenya = get_option( 'comenta_twitter_password' );
		
		if( !empty( $usuari ) && !empty( $contrasenya ) ){
			curl_setopt( $curl_handle, CURLOPT_URL, "$url" );
			curl_setopt( $curl_handle, CURLOPT_VERBOSE, 1 );
	   	curl_setopt( $curl_handle, CURLOPT_RETURNTRANSFER, 1 );
	    	curl_setopt( $curl_handle, CURLOPT_USERPWD, "$usuari:$contrasenya" );
	    	curl_setopt( $curl_handle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1 );
	    	curl_setopt( $curl_handle, CURLOPT_POST, 1 );
			curl_setopt( $curl_handle, CURLOPT_HTTPHEADER, array( 'Expect:' ) ); 

			$buffer = curl_exec( $curl_handle );
			curl_close( $curl_handle );

		}
	}

	function send_mail( $to, $subject, $message ) {
		$subject = '[' . get_bloginfo( 'name' ) . '] ' . $subject;

		// strip out some chars that might cause issues, and assemble vars
		$site_name = str_replace( '"', "'", $this->site_name );
		$site_email = str_replace( array( '<', '>' ), array( '', '' ), $this->site_email );
		$charset = get_settings( 'blog_charset' );

		$headers = "From: \"{$site_name}\" <{$site_email}>\n";
		$headers .= "MIME-Version: 1.0\n";
		$headers .= "Content-Type: text/plain; charset = \"{$charset}\"\n";
		return wp_mail( $to, $subject, $message, $headers );
	}
	
		//Retalla un string sense tallar paraules per la meitat
	function substrwords( $text, $maxchar, $end = '..' ){
		if( strlen( $text ) > $maxchar ){
			$words = explode( " ", $text );
			$output = '';
			$i = 0;
			
			while( 1 ){
				$length = ( strlen( $output ) + strlen( $words[$i] ) );
				if( $length > $maxchar ){
					break;
				}else{
					$output = $output." ".$words[$i];
					++$i;
				};
			};
			$output .= $end;
		}else{
			$output = $text;
		}
		
		return $output;
	}
	

} // class comenta

function comentaWP_start(  ) {
	global $comentaWP;

	if ( !$comentaWP ) {
		load_plugin_textdomain( 'comentawp' );
		$comentaWP = new comentawp(  );
	}
}

// priority is very low ( 50 ) because we want to let anti-spam plugins have their way first.
add_action( 'comment_post', create_function( '$a', 'global $comentaWP; comentaWP_start(  ); return $comentaWP->send_notification( $a );' ), 50 );
add_action( 'wp_set_comment_status', create_function( '$a', 'global $comentaWP; comentaWP_start(  ); return $comentaWP->send_notification( $a );' ) );
//add_action( 'edit_comment', array( 'comentawp', 'on_edit' ) );



/*
 * Configuració del Comenta!
 */


// Es mostra la opció del menú dins de les Opcions Generals del WordPress.
add_action( 'admin_menu', 'comenta_admin_menu' );
function comenta_admin_menu(  ) {
	add_submenu_page( 'options-general.php', 'Comenta WP configuration', 'Comenta WP', 10, 'options-comenta', 'comenta_config' );
//	add_options_page( 'Configuració del Comenta WP', 'Comenta WP', 5, basename( __FILE__ ), 'comenta_config' );
}



/*
 * Pàgina de configuració del Comenta
 */

function comenta_config(  ){

	if ( isset( $_POST['action'] ) ) {
		// Si deixen l'usuari en blanc, s'elimina el registre de la base de dades
		if( empty( $_POST['comenta_twitter_user'] ) ){
			delete_option( 'comenta_twitter_user' );
			delete_option( 'comenta_twitter_password' );
			delete_option( 'comenta_twitter_msg' );
			delete_option( 'comenta_twitter_short_url' );
		}
	}

	//Si no té missatge, en generem un per defecte.
	if( trim( get_option( 'comenta_twitter_msg' ) ) == "" ){
		update_option( 'comenta_twitter_msg', _( "%a has commented in %t: %u" ) );
	
	}

?>


<div class = "wrap">
<h2><?php _e( 'Comenta WP configuration' , 'comenta-wp'); ?></h2>

<form method = "post" action = "options.php">
	<?php wp_nonce_field( 'update-options' ); ?>
	
	     <p><?php printf( __( 'Introduce your Twitter user and password. All the comments made to your blog will be posted directly to this twitter account.' , 'comenta-wp') ); ?></p>
	
	
	<table class = "form-table">
	
		<tr valign = "top">
			<th scope = "row"><?php _e( 'Twitter user name:' , 'comenta-wp'); ?></th>
			<td><input type = "text" name = "comenta_twitter_user" value = "<?php echo get_option( 'comenta_twitter_user' ); ?>" /></td>
		</tr>
		 
		<tr valign = "top">
			<th scope = "row"><?php _e( 'Twitter password:' , 'comenta-wp'); ?></th>
			<td><input type = "password" name = "comenta_twitter_password" value = "<?php echo get_option( 'comenta_twitter_password' ); ?>" /></td>
		</tr>
	
		<tr valign = "top">
			<th scope = "row"><?php _e( 'Message:' , 'comenta-wp'); ?></th>
			<td><input type = "text" name = "comenta_twitter_msg" value = "<?php echo get_option( 'comenta_twitter_msg' ); ?>" size = "50" />
				<br />
				<ul>
					<li><?php _e( '<strong>%a:</strong> Comment Author' , 'comenta-wp'); ?></li>
					<li><?php _e( '<strong>%t:</strong> Post title' , 'comenta-wp'); ?></li>
					<li><?php _e( '<strong>%u:</strong> Post link' , 'comenta-wp'); ?></li>
				</ul>
			</td>
		</tr>
		
		<tr valign = "top">
		<th scope = "row"><?php _e( 'URL Shorten Service:' , 'comenta-wp'); ?></th>
		<td>
			<select name = "comenta_twitter_short_url" >
				<option value = "twitter" <?php if( get_option( 'comenta_twitter_short_url' ) == "twitter" ) echo 'selected = "selected"'?>>Twitter.com</option>
				<option value = "tinyurl" <?php if( get_option( 'comenta_twitter_short_url' ) == "tinyurl" ) echo 'selected = "selected"'?>>TinyURL.com</option>
				<option value = "isgd" <?php if( get_option( 'comenta_twitter_short_url' ) == "isgd" ) echo 'selected = "selected"'?>>is.gd</option>
				<option value = "lostin" <?php if( get_option( 'comenta_twitter_short_url' ) == "lostin" ) echo 'selected = "selected"'?>>Lost.in</option>
			</select>
			<br />
			<p><?php _e( '<strong>Twitter.com:</strong> Let twitter choose if the URL must be shortened or not.' , 'comenta-wp'); ?></p>
		</td>
		</tr>
	
	</table>
	
	<input type = "hidden" name = "action" value = "update" />
	<input type = "hidden" name = "page_options" value = "comenta_twitter_user, comenta_twitter_password, comenta_twitter_msg, comenta_twitter_short_url" />
	
	<p class = "submit">
		<input type = "submit" class = "button-primary" value = "<?php _e( 'Save Changes' , 'comenta-wp') ?>" />
	</p>

</form>

<?php


}


//Si no hi ha informat el nom d'usuari i la contrasenya de twitter, informem que s'ha de registrar :- )
if ( !get_option( 'comenta_twitter_user ' ) && !get_option( 'comenta_twitter_password' ) && !isset( $_POST['submit'] ) ) {
	function comenta_warning(  ) {
		echo "
		<div id = 'comenta-warning' class = 'updated fade'><p><strong>".__( 'Comenta WP is almost configured.' , 'comenta-wp')."</strong> ".sprintf( __( 'You have to configure you <a href = "%1$s">twitter username and password</a>.' , 'comenta-wp'), "options-general.php?page = options-comenta" )."</p></div>
		";
	}
	add_action( 'admin_notices', 'comenta_warning' );
	return;
}


//add_action( 'wp_set_comment_status', create_function( '$a', 'global $comentaWP; comentaWP_start(  ); return $comentaWP->send_notification( $a );' ) );
//add_action( 'edit_comment', array( 'comenta', 'on_edit' ) );

 ?>