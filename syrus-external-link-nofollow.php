<?php
/*
Plugin Name: Syrus External Link NoFollow
Plugin URI: https://wordpress.org/plugins/syrus-external-link-nofollow/
Description: Una volta attivato il plugin, a tutti i link esterni verranno aggiunti gli attributi <code>rel=&quot;nofollow&quot;</code> e <code>target=&quot;_blank&quot;</code>
Version: 1.0.0
Author: Syrus Industry
Author URI: http://www.syrusindustry.com
License: GPL2
*/

if( !defined('ABSPATH') ) die('-1');


//procedure di attivazione e disattivazione del plugin

function attiva_syrus_nofollow(){
  add_option( 'domini_esclusi', '');
  add_option( 'applica', '1');
}

function disattiva_syrus_nofollow(){
  delete_option( 'applica' );
  delete_option( 'domini_esclusi' );
}

register_activation_hook( __FILE__, 'attiva_syrus_nofollow' );
register_deactivation_hook( __FILE__, 'disattiva_syrus_nofollow' );


function register_syrus_nofollow_settings() {
	register_setting( 'syrus-nofollow-settings-group', 'domini_esclusi' );
	register_setting( 'syrus-nofollow-settings-group', 'applica' );
}

add_action( 'admin_init', 'register_syrus_nofollow_settings' );

function syrus_nofollow_plugin_menu() {
	add_options_page('Syrus No Follow', 'Syrus No Follow', 'manage_options', 'syrus_nofollow', 'syrus_nofollow_function');
}

add_action( 'admin_menu', 'syrus_nofollow_plugin_menu');

function syrus_nofollow_function(){
  $applica = get_option('applica');
  $domini_esclusi = get_option('domini_esclusi');

  // echo "applica = $applica - domini_esclusi = $domini_esclusi<br>";
  ?>
  <div class="row">
    <h1 style="text-align:center">Syrus No Follow</h1>
    <hr>
  </div>
  <div class="row">
    <h4 style="text-align:center">Impostazioni</h4>
  </div>

  <div class="row">
    	<form method="post" action="options.php" enctype="multipart/form-data">
        <?php settings_fields( 'syrus-nofollow-settings-group' ); ?>

        <table  align="center" style="margin-top:30px">
          <tr>
            <td>Attivo? </td>
            <td><input <?php echo ($applica == 1)?'checked="checked"':''; ?>  type="checkbox" name="applica" id="applica" value="1" /></td>
          </tr>
          <tr>
            <td>Domini Esclusi</td>
            <td><textarea name="domini_esclusi" rows="5" id="domini_esclusi" class="large-text" placeholder="miodominio.it, miodominio.com"><?php echo $domini_esclusi?></textarea></td>
          </tr>
          <tr>
            <td><input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" /></td>
          </tr>
        </table>

      </form>
  </div>

  <?php
}

function syrus_nofollow_url_parse( $content ) {

	$regexp = "<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>";
	if(preg_match_all("/$regexp/siU", $content, $matches, PREG_SET_ORDER)) {
		if( !empty($matches) ) {


			$ownDomain = $_SERVER['HTTP_HOST'];

      //domini esclusi
			$exclude_domains_list = array();
			if(get_option('domini_esclusi')!='') {
				$exclude_domains_list = explode(",",get_option('domini_esclusi'));
			}

			for ($i=0; $i < count($matches); $i++)
			{

				$tag  = $matches[$i][0];
				$tag2 = $matches[$i][0];
				$url  = $matches[$i][0];

				// bypasso le ancore interne
				$res = preg_match('/href(\s)*=(\s)*"[#|\/]*[a-zA-Z0-9-_\/]+"/',$url);
				if($res) {
					continue;
				}

				$pos = strpos($url,$ownDomain);
				if ($pos === false) {

					$domainCheckFlag = true;

					if(count($exclude_domains_list)>0) {
						$exclude_domains_list = array_filter($exclude_domains_list);
						foreach($exclude_domains_list as $domain) {
							$domain = trim($domain);
							if($domain!='') {
								$domainCheck = strpos($url,$domain);
								if($domainCheck === false) {
									continue;
								} else {
									$domainCheckFlag = false;
									break;
								}
							}
						}
					}

					$noFollow = '';

					// aggiungo target=_blank
					$pattern = '/target\s*=\s*"\s*_(blank|parent|self|top)\s*"/';
					preg_match($pattern, $tag2, $match, PREG_OFFSET_CAPTURE);
					if( count($match) < 1 )
						$noFollow .= ' target="_blank"';

					//escludo domini o aggiungo nofollow
					if($domainCheckFlag) {
						$pattern = '/rel\s*=\s*"\s*[n|d]ofollow\s*"/';
						preg_match($pattern, $tag2, $match, PREG_OFFSET_CAPTURE);
						if( count($match) < 1 )
							$noFollow .= ' rel="nofollow"';
					}

					// aggiungo target/nofollow all'url
					$tag = rtrim ($tag,'>');
					$tag .= $noFollow.'>';
					$content = str_replace($tag2,$tag,$content);
				}
			}
		}
	}

	$content = str_replace(']]>', ']]&gt;', $content);
	return $content;
}


if( get_option('applica') ) {
  add_filter( 'the_content', 'syrus_nofollow_url_parse');
}
