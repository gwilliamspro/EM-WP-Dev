<?php
/**
 * Plugin Name: Epic Marks Custom Blocks
 * Description: Custom Gutenberg blocks for Epic Marks website
 * Version: 1.0.6
 * Author: Epic Marks Development Team
 */

if (!defined('ABSPATH')) {
    exit;
}

class Epic_Marks_Blocks {

    public function __construct() {
        add_action('init', array($this, 'register_blocks'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_editor_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
    }

    public function register_blocks() {
        // Register wave block
        register_block_type('epic-marks/wave-block', array(
            'editor_script' => 'epic-marks-blocks',
            'style' => 'epic-marks-blocks-style',
            'render_callback' => array($this, 'render_wave_block')
        ));

        // Register countdown block
        register_block_type('epic-marks/countdown-block', array(
            'editor_script' => 'epic-marks-blocks',
            'style' => 'epic-marks-blocks-style',
            'render_callback' => array($this, 'render_countdown_block')
        ));

        // Register USP block
        register_block_type('epic-marks/usp-block', array(
            'editor_script' => 'epic-marks-blocks',
            'style' => 'epic-marks-blocks-style',
            'render_callback' => array($this, 'render_usp_block')
        ));
    }

    public function enqueue_editor_assets() {
        wp_enqueue_script(
            'epic-marks-blocks',
            plugins_url('assets/blocks.js', __FILE__),
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'),
            filemtime(plugin_dir_path(__FILE__) . 'assets/blocks.js')
        );

        wp_enqueue_style(
            'epic-marks-blocks-style',
            plugins_url('assets/blocks.css', __FILE__),
            array(),
            filemtime(plugin_dir_path(__FILE__) . 'assets/blocks.css')
        );
    }

    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            'epic-marks-blocks-style',
            plugins_url('assets/blocks.css', __FILE__),
            array(),
            filemtime(plugin_dir_path(__FILE__) . 'assets/blocks.css')
        );
    }

    public function render_wave_block($attributes) {
        $wave_height = isset($attributes['waveHeight']) ? $attributes['waveHeight'] : 80;
        $wave_height_mobile = isset($attributes['waveHeightMobile']) ? $attributes['waveHeightMobile'] : 60;
        $wave_color = isset($attributes['waveColor']) ? $attributes['waveColor'] : '#627a94';
        $animate = isset($attributes['animate']) ? $attributes['animate'] : true;
        $rotate = isset($attributes['rotate']) ? $attributes['rotate'] : false;
        $background_color = isset($attributes['backgroundColor']) ? $attributes['backgroundColor'] : 'transparent';
        $bottom_section_height = isset($attributes['bottomSectionHeight']) ? $attributes['bottomSectionHeight'] : 100;

        $unique_id = 'wave-' . uniqid();
        $animate_class = $animate ? 'em-waves-animated' : '';
        $rotate_class = $rotate ? 'em-waves-rotated' : '';

        ob_start();
        ?>
        <style>
            #<?php echo $unique_id; ?>-container {
                position: relative;
                width: 100vw;
                left: 50%;
                right: 50%;
                margin-left: -50vw;
                margin-right: -50vw;
                background: <?php echo esc_attr($background_color); ?>;
            }
            #<?php echo $unique_id; ?>-waves {
                position: relative;
                height: <?php echo $wave_height_mobile; ?>px;
                min-height: <?php echo $wave_height_mobile; ?>px;
                max-height: <?php echo $wave_height_mobile; ?>px;
                overflow: hidden;
            }
            @media (min-width: 768px) {
                #<?php echo $unique_id; ?>-waves {
                    height: <?php echo $wave_height; ?>px;
                    min-height: <?php echo $wave_height; ?>px;
                    max-height: <?php echo $wave_height; ?>px;
                }
            }
            #<?php echo $unique_id; ?>-waves svg {
                position: absolute;
                bottom: 0;
                width: 100%;
                height: 100%;
            }
            #<?php echo $unique_id; ?>-bottom {
                width: 100%;
                height: <?php echo $bottom_section_height; ?>px;
                background: <?php echo esc_attr($wave_color); ?>;
            }
            #<?php echo $unique_id; ?>-waves.em-waves-rotated {
                transform: rotate(180deg);
            }
            <?php if ($animate): ?>
            #<?php echo $unique_id; ?>-waves.em-waves-animated .wave-parallax-1 > use {
                animation: move-forever-1-<?php echo $unique_id; ?> 10s cubic-bezier(0.55, 0.5, 0.45, 0.5) infinite;
            }
            #<?php echo $unique_id; ?>-waves.em-waves-animated .wave-parallax-2 > use {
                animation: move-forever-2-<?php echo $unique_id; ?> 8s cubic-bezier(0.55, 0.5, 0.45, 0.5) infinite;
            }
            #<?php echo $unique_id; ?>-waves.em-waves-animated .wave-parallax-3 > use {
                animation: move-forever-3-<?php echo $unique_id; ?> 6s cubic-bezier(0.55, 0.5, 0.45, 0.5) infinite;
            }
            #<?php echo $unique_id; ?>-waves.em-waves-animated .wave-parallax-4 > use {
                animation: move-forever-4-<?php echo $unique_id; ?> 4s cubic-bezier(0.55, 0.5, 0.45, 0.5) infinite;
            }
            @keyframes move-forever-1-<?php echo $unique_id; ?> {
                0% { transform: translate3d(85px, 0, 0); }
                100% { transform: translate3d(-90px, 0, 0); }
            }
            @keyframes move-forever-2-<?php echo $unique_id; ?> {
                0% { transform: translate3d(-90px, 0, 0); }
                100% { transform: translate3d(85px, 0, 0); }
            }
            @keyframes move-forever-3-<?php echo $unique_id; ?> {
                0% { transform: translate3d(85px, 0, 0); }
                100% { transform: translate3d(-90px, 0, 0); }
            }
            @keyframes move-forever-4-<?php echo $unique_id; ?> {
                0% { transform: translate3d(-90px, 0, 0); }
                100% { transform: translate3d(85px, 0, 0); }
            }
            <?php endif; ?>
        </style>
        <div id="<?php echo $unique_id; ?>-container" class="em-wave-full-container">
            <div id="<?php echo $unique_id; ?>-waves" class="em-wave-container <?php echo $animate_class; ?> <?php echo $rotate_class; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 24 150 28" preserveAspectRatio="none" shape-rendering="auto">
                    <defs>
                        <path id="gentle-wave-<?php echo $unique_id; ?>" d="M-160 44c30 0 58-18 88-18s 58 18 88 18 58-18 88-18 58 18 88 18 v44h-352z"/>
                    </defs>
                    <g class="wave-parallax-1">
                        <use xlink:href="#gentle-wave-<?php echo $unique_id; ?>" x="50" y="3" fill="<?php echo esc_attr($wave_color); ?>" fill-opacity="0.4"/>
                    </g>
                    <g class="wave-parallax-2">
                        <use xlink:href="#gentle-wave-<?php echo $unique_id; ?>" x="50" y="0" fill="<?php echo esc_attr($wave_color); ?>" fill-opacity="0.3"/>
                    </g>
                    <g class="wave-parallax-3">
                        <use xlink:href="#gentle-wave-<?php echo $unique_id; ?>" x="50" y="9" fill="<?php echo esc_attr($wave_color); ?>" fill-opacity="0.2"/>
                    </g>
                    <g class="wave-parallax-4">
                        <use xlink:href="#gentle-wave-<?php echo $unique_id; ?>" x="50" y="6" fill="<?php echo esc_attr($wave_color); ?>" fill-opacity="0.1"/>
                    </g>
                </svg>
            </div>
            <div id="<?php echo $unique_id; ?>-bottom" class="em-wave-bottom-section"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_countdown_block($attributes) {
        $background_color = isset($attributes['backgroundColor']) ? $attributes['backgroundColor'] : '#627a94';
        $text_color = isset($attributes['textColor']) ? $attributes['textColor'] : '#ffffff';
        $text_size = isset($attributes['textSize']) ? $attributes['textSize'] : 18;
        $countdown_text = isset($attributes['countdownText']) ? $attributes['countdownText'] : 'Order in 2h 30m to ship today (by 2:00 PM CT).';
        $padding_top = isset($attributes['paddingTop']) ? $attributes['paddingTop'] : 12;
        $padding_bottom = isset($attributes['paddingBottom']) ? $attributes['paddingBottom'] : 12;

        $unique_id = 'countdown-' . uniqid();

        ob_start();
        ?>
        <div id="<?php echo $unique_id; ?>" class="em-countdown-banner" style="
            position: relative;
            width: 100vw;
            left: 50%;
            right: 50%;
            margin-left: -50vw;
            margin-right: -50vw;
            background: <?php echo esc_attr($background_color); ?>;
            color: <?php echo esc_attr($text_color); ?>;
            padding: <?php echo esc_attr($padding_top); ?>px 1rem <?php echo esc_attr($padding_bottom); ?>px 1rem;
            text-align: center;
            font-size: <?php echo esc_attr($text_size); ?>px;
            font-weight: 700;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.4;
        ">
            <span id="<?php echo $unique_id; ?>-text"><?php echo esc_html($countdown_text); ?></span>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_usp_block($attributes) {
        $icon = isset($attributes['icon']) ? trim($attributes['icon']) : '';
        $title = isset($attributes['title']) ? $attributes['title'] : 'Feature Title';
        $icon_size = isset($attributes['iconSize']) ? $attributes['iconSize'] : 32;
        $title_size = isset($attributes['titleSize']) ? $attributes['titleSize'] : 16;
        $text_color = isset($attributes['textColor']) ? $attributes['textColor'] : '#454C57';
        $background_color = isset($attributes['backgroundColor']) ? $attributes['backgroundColor'] : 'transparent';
        $border_radius = isset($attributes['borderRadius']) ? $attributes['borderRadius'] : 0;
        $box_shadow = isset($attributes['boxShadow']) ? $attributes['boxShadow'] : false;
        $padding = isset($attributes['padding']) ? $attributes['padding'] : 16;

        $unique_id = 'usp-' . uniqid();
        $shadow_style = $box_shadow ? '0 2px 6px rgba(0,0,0,0.08)' : 'none';

        ob_start();
        ?>
        <div id="<?php echo $unique_id; ?>" class="em-usp-item" style="
            text-align: center;
            color: <?php echo esc_attr($text_color); ?>;
            background: <?php echo esc_attr($background_color); ?>;
            border-radius: <?php echo esc_attr($border_radius); ?>px;
            box-shadow: <?php echo $shadow_style; ?>;
            padding: <?php echo esc_attr($padding); ?>px;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        ">
            <?php if (!empty($icon)): ?>
            <div class="em-usp-icon" style="font-size: <?php echo esc_attr($icon_size); ?>px; margin-bottom: 8px; line-height: 1;">
                <?php echo esc_html($icon); ?>
            </div>
            <?php endif; ?>
            <div class="em-usp-title" style="font-size: <?php echo esc_attr($title_size); ?>px; font-weight: 600; text-transform: uppercase;">
                <?php echo esc_html($title); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

new Epic_Marks_Blocks();
