<?php
/**
 * Schema.org Display
 *
 * @author  Kazuya Takami
 * @version 1.0.0
 * @since   1.0.0
 */
class Structuring_Markup_Display {

	/**
	 * Constructor Define.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$db = new Structuring_Markup_Admin_Db();
		$this->set_schema( $db );
	}

	/**
	 * Setting schema.org
	 *
	 * @since 1.0.0
	 * @param Structuring_Markup_Admin_Db $db
	 */
	private function set_schema( Structuring_Markup_Admin_Db $db ) {
		$this->get_schema_data( $db, 'all' );
		if ( is_home() ) {
			$this->get_schema_data( $db, 'home' );
		}
		if ( is_single() ) {
			$this->get_schema_data( $db, 'post' );
		}
		if ( is_page() ) {
			$this->get_schema_data( $db, 'page' );
		}
	}

	/**
	 * Setting JSON-LD Template
	 *
	 * @since   1.0.0
	 * @version 1.1.0
	 * @param   Structuring_Markup_Admin_Db $db
	 * @param   string $output
	 */
	private function get_schema_data( Structuring_Markup_Admin_Db $db, $output ) {
		$results = $db->get_select_options( $output );

		if ( isset( $results ) ) {
			foreach ( $results as $row ) {
				if ( isset( $row->type ) ) {
					switch ( $row->type ) {
						case 'website':
							if ( isset( $row->options ) ) {
								$this->set_schema_website( unserialize( $row->options ) );
							}
							break;
						case 'organization':
							if ( isset( $row->options ) ) {
								$this->set_schema_organization( unserialize( $row->options ) );
							}
							break;
						case 'article':
							if ( isset( $row->options ) ) {
								$this->set_schema_article( unserialize( $row->options ) );
							}
							break;
						case 'news_article':
							if ( isset( $row->options ) ) {
								$this->set_schema_news_article( unserialize( $row->options ) );
							}
							break;
					}
				}
			}
		}
	}

	/**
	 * Setting JSON-LD Template
	 *
	 * @since 1.0.0
	 * @param array $args
	 */
	private function set_schema_json( array $args ) {
		echo '<script type="application/ld+json">' , PHP_EOL;
		echo json_encode( $args, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) , PHP_EOL;
		echo '</script>' , PHP_EOL;
	}

	/**
	 * Setting schema.org WebSite
	 *
	 * @since 1.0.0
	 * @param array $options
	 */
	private function set_schema_website( array $options ) {
		$args = array(
			"@context"      => "http://schema.org",
			"@type"         => "WebSite",
			"name"          => isset( $options['name'] ) ? esc_html( $options['name'] ) : "",
			"alternateName" => isset( $options['alternateName'] ) ? esc_html( $options['alternateName'] ) : "",
			"url"           => isset( $options['url'] ) ? esc_html( $options['url'] ) : ""
		);

		if ( isset( $options['potential_action'] ) && $options['potential_action'] === 'on' ) {
			$potential_action["potentialAction"] = array(
				"@type"       => "SearchAction",
				"target"      => isset( $options['target'] ) ? esc_html( $options['target'] ) . "{search_term_string}" : "",
				"query-input" => isset( $options['target'] ) ? "required name=search_term_string" : ""
			);
			$args = array_merge( $args, $potential_action );
		}

		$this->set_schema_json( $args );
	}

	/**
	 * Setting schema.org Organization
	 *
	 * @since 1.0.0
	 * @param array $options
	 */
	private function set_schema_organization( array $options ) {
		/** Logos */
		$args = array(
			"@context" => "http://schema.org",
			"@type"    => "Organization",
			"name"     => isset( $options['name'] ) ? esc_html( $options['name'] ) : "",
			"url"      => isset( $options['url'] ) ? esc_html( $options['url'] ) : "",
			"logo"     => isset( $options['logo'] ) ? esc_html( $options['logo'] ) : ""
		);

		/** Corporate Contact */
		if ( isset( $options['contact_point'] ) && $options['contact_point'] === 'on' ) {
			$contact_point["contactPoint"] = array(
				array(
					"@type"       => "ContactPoint",
					"telephone"   => isset( $options['telephone'] ) ? esc_html( $options['telephone'] ) : "",
					"contactType" => isset( $options['contact_type'] ) ? esc_html( $options['contact_type'] ) : ""
				)
			);
			$args = array_merge( $args, $contact_point );
		}

		/** Social Profiles */
		if ( isset( $options['social'] ) ) {
			$socials["sameAs"] = array();

			foreach ( $options['social'] as $value ) {
				if ( !empty( $value ) ) {
					$socials["sameAs"][] = ( esc_html( $value ) );
				}
			}
			$args = array_merge( $args, $socials );
		}
		$this->set_schema_json( $args );
	}

	/**
	 * Setting schema.org Article
	 *
	 * @since   1.1.0
	 * @version 1.1.1
	 */
	private function set_schema_article() {
		global $post;
		if ( has_post_thumbnail( $post->ID ) ) {
			list( $thumbnail_url, $thumbnail_width, $thumbnail_height ) = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'full' );
			$args = array(
				"@context"      => "http://schema.org",
				"@type"         => "Article",
				"headline"      => esc_html( $post->post_title ),
				"datePublished" => get_the_time( DATE_ISO8601, $post->ID ),
				"author"        => esc_html( get_the_author_meta( 'display_name', $post->post_author ) ),
				"image"         => array( $thumbnail_url ),
				"description"   => esc_html( $post->post_excerpt ),
				"articleBody"   => esc_html( $post->post_content )
			);
			$this->set_schema_json( $args );
		}
	}

	/**
	 * Setting schema.org NewsArticle
	 *
	 * @since 1.0.0
	 */
	private function set_schema_news_article() {
		global $post;
		if ( has_post_thumbnail( $post->ID ) ) {
			list( $thumbnail_url, $thumbnail_width, $thumbnail_height ) = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'full' );
			$args = array(
				"@context"      => "http://schema.org",
				"@type"         => "NewsArticle",
				"headline"      => esc_html( $post->post_title ),
				"datePublished" => get_the_time( DATE_ISO8601, $post->ID ),
				"image"         => array( $thumbnail_url ),
				"description"   => esc_html( $post->post_excerpt ),
				"articleBody"   => esc_html( $post->post_content )
			);
			$this->set_schema_json( $args );
		}
	}
}