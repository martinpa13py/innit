<?php
namespace Auxin\Plugin\Pro\Elementor\Elements\Theme_Builder;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Core\Kits\Documents\Tabs\Global_Colors;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

/**
 * Elementor 'Post_Excerpt' widget.
 *
 * Elementor widget that displays an 'Post_Excerpt'.
 *
 * @since 1.0.0
 */
class Post_Excerpt extends Widget_Base {

    /**
     * Get widget name.
     *
     * Retrieve 'Post_Excerpt' widget name.
     *
     * @since 1.0.0
     * @access public
     *
     * @return string Widget name.
     */
    public function get_name() {
        return 'aux_post_excerpt';
    }

    /**
     * Get widget title.
     *
     * Retrieve 'Post_Excerpt' widget title.
     *
     * @since 1.0.0
     * @access public
     *
     * @return string Widget title.
     */
    public function get_title() {
        return __('Post Excerpt', PLUGIN_DOMAIN );
    }

    /**
     * Get widget icon.
     *
     * Retrieve 'Post_Excerpt' widget icon.
     *
     * @since 1.0.0
     * @access public
     *
     * @return string Widget icon.
     */
    public function get_icon() {
        return 'eicon-post-excerpt auxin-badge';
    }

    /**
     * Get widget categories.
     *
     * Retrieve 'Post_Excerpt' widget icon.
     *
     * @since 1.0.0
     * @access public
     *
     * @return string Widget icon.
     */
    public function get_categories() {
        return array( 'auxin-core', 'auxin-theme-elements-single' );
    }

    /**
     * Register 'Post_Excerpt' widget controls.
     *
     * Adds different input fields to allow the user to change and customize the widget settings.
     *
     * @since 1.0.0
     * @access protected
     */
    protected function register_controls() {

		$this->start_controls_section(
			'section_content',
			[
				'label' => __( 'Content', PLUGIN_DOMAIN ),
			]
		);

        $this->add_control(
            'trim_excerpt',
            array(
                'label'       => __('Trim Excerpt', PLUGIN_DOMAIN),
                'label_block' => true,
				'type'        => Controls_Manager::NUMBER,
				'default'     => '200',
                'min'         => 1,
                'step'        => 1
            )
        );

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style',
			[
				'label' => __( 'Style', PLUGIN_DOMAIN ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'title_color',
			[
				'label' => __( 'Text Color', PLUGIN_DOMAIN ),
				'type' => Controls_Manager::COLOR,
				'global' => [
					'default' => Global_Colors::COLOR_TEXT,
				],
				'selectors' => [
					'{{WRAPPER}}' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'typography',
				'global' => [
					'default' => Global_Typography::TYPOGRAPHY_TEXT,
				],
				'selector' => '{{WRAPPER}}',
			]
		);

		$this->end_controls_section();

	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		auxin_the_trim_excerpt( null, (int) $settings['trim_excerpt'], null, true );
		echo '<div class="clear"></div>';
	}

}
