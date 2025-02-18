<?php
namespace Auxin\Plugin\Pro\Elementor\Modules;


class Column {

    /**
     * Instance of this class.
     *
     * @var      object
     */
    protected static $instance = null;


    function __construct(){
        // Modify render
        // add_action( 'elementor/frontend/column/before_render', array( $this, 'modify_render' ) );

        // Add new controls
        // add_action( "elementor/element/column/{$section_id}/after_section_end", array( $this, 'add_controls' ) );
    }

    /**
     * Return an instance of this class.
     *
     * @return    object    A single instance of this class.
     */
    public static function get_instance() {
        // If the single instance hasn't been set, set it now.
        if ( null == self::$instance ) {
            self::$instance = new self;
        }

        return self::$instance;
    }


    /**
     * Modify the render of section element
     *
     * @param  Element_Section $section Instance of Section element
     *
     * @return void
     */
    public function modify_render( $section ){

    }

    /**
     * Add extra controls to section element
     *
     * @param  Element_Section $section Instance of Section element
     *
     * @return void
     */
    public function add_controls( $section ){

    }

}
