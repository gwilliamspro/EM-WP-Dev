<?php
/**
 * Box Manager Class
 *
 * Manages box definitions, selection algorithm, and dimensional weight calculations
 *
 * @package    Epic_Marks_Shipping
 * @subpackage Epic_Marks_Shipping/includes
 * @since      2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * EM_Box_Manager Class
 *
 * Handles box CRUD operations, selection logic, and dimensional weight calculations
 */
class EM_Box_Manager {

    /**
     * DIM weight divisor for UPS (standard)
     */
    const DIM_WEIGHT_DIVISOR = 166;

    /**
     * Get all boxes
     *
     * @return array Array of box definitions
     */
    public static function get_boxes() {
        $boxes = get_option( 'em_shipping_boxes', array() );

        // If no boxes exist, create defaults
        if ( empty( $boxes ) ) {
            $boxes = self::get_default_boxes();
            update_option( 'em_shipping_boxes', $boxes );
        }

        return $boxes;
    }

    /**
     * Get default box definitions
     *
     * @return array Default box library
     */
    public static function get_default_boxes() {
        return array(
            array(
                'id'                => 'padded-envelope',
                'name'              => 'Padded Envelope',
                'type'              => 'envelope',
                'inner_dimensions'  => array( 'length' => 12, 'width' => 9, 'height' => 1 ),
                'outer_dimensions'  => array( 'length' => 12.5, 'width' => 9.5, 'height' => 1.25 ),
                'max_weight'        => 3,
                'cost'              => 0.50,
                'typical_use'       => 'Single shirt, small accessories',
                'status'            => 'active',
            ),
            array(
                'id'                => 'small-box',
                'name'              => 'Small Box',
                'type'              => 'box',
                'inner_dimensions'  => array( 'length' => 12, 'width' => 9, 'height' => 4 ),
                'outer_dimensions'  => array( 'length' => 12.5, 'width' => 9.5, 'height' => 4.5 ),
                'max_weight'        => 20,
                'cost'              => 1.50,
                'typical_use'       => '1-3 shirts, small apparel orders',
                'status'            => 'active',
            ),
            array(
                'id'                => 'medium-box',
                'name'              => 'Medium Box',
                'type'              => 'box',
                'inner_dimensions'  => array( 'length' => 16, 'width' => 12, 'height' => 6 ),
                'outer_dimensions'  => array( 'length' => 16.5, 'width' => 12.5, 'height' => 6.5 ),
                'max_weight'        => 35,
                'cost'              => 2.00,
                'typical_use'       => '4-8 shirts, medium apparel orders',
                'status'            => 'active',
            ),
            array(
                'id'                => 'large-box',
                'name'              => 'Large Box',
                'type'              => 'box',
                'inner_dimensions'  => array( 'length' => 18, 'width' => 14, 'height' => 8 ),
                'outer_dimensions'  => array( 'length' => 18.5, 'width' => 14.5, 'height' => 8.5 ),
                'max_weight'        => 50,
                'cost'              => 2.50,
                'typical_use'       => '9+ shirts, bulk apparel orders',
                'status'            => 'active',
            ),
            array(
                'id'                => 'dtf-tube',
                'name'              => 'DTF Roll Tube',
                'type'              => 'tube',
                'inner_dimensions'  => array( 'length' => 24, 'width' => 4, 'height' => 4 ),
                'outer_dimensions'  => array( 'length' => 25, 'width' => 5, 'height' => 5 ),
                'max_weight'        => 10,
                'cost'              => 2.00,
                'typical_use'       => 'DTF prints (22" wide rolls), cannot combine with apparel',
                'status'            => 'active',
            ),
        );
    }

    /**
     * Get a single box by ID
     *
     * @param string $box_id Box ID
     * @return array|null Box data or null if not found
     */
    public static function get_box( $box_id ) {
        $boxes = self::get_boxes();

        foreach ( $boxes as $box ) {
            if ( $box['id'] === $box_id ) {
                return $box;
            }
        }

        return null;
    }

    /**
     * Save a box (create or update)
     *
     * @param array $box_data Box data
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public static function save_box( $box_data ) {
        // Validate required fields
        $required = array( 'id', 'name', 'type', 'outer_dimensions', 'max_weight' );
        foreach ( $required as $field ) {
            if ( empty( $box_data[ $field ] ) ) {
                return new WP_Error( 'missing_field', sprintf( __( 'Missing required field: %s', 'epic-marks-shipping' ), $field ) );
            }
        }

        // Validate dimensions
        if ( ! isset( $box_data['outer_dimensions']['length'] ) ||
             ! isset( $box_data['outer_dimensions']['width'] ) ||
             ! isset( $box_data['outer_dimensions']['height'] ) ) {
            return new WP_Error( 'invalid_dimensions', __( 'Outer dimensions must include length, width, and height', 'epic-marks-shipping' ) );
        }

        // Get existing boxes
        $boxes = self::get_boxes();

        // Check if box exists (update) or is new (create)
        $found = false;
        foreach ( $boxes as $key => $box ) {
            if ( $box['id'] === $box_data['id'] ) {
                $boxes[ $key ] = $box_data;
                $found = true;
                break;
            }
        }

        // Add new box
        if ( ! $found ) {
            $boxes[] = $box_data;
        }

        // Save to database
        update_option( 'em_shipping_boxes', $boxes );

        return true;
    }

    /**
     * Delete a box
     *
     * @param string $box_id Box ID
     * @return bool True on success, false on failure
     */
    public static function delete_box( $box_id ) {
        $boxes = self::get_boxes();

        foreach ( $boxes as $key => $box ) {
            if ( $box['id'] === $box_id ) {
                unset( $boxes[ $key ] );
                update_option( 'em_shipping_boxes', array_values( $boxes ) );
                return true;
            }
        }

        return false;
    }

    /**
     * Get box for items
     *
     * Selects the smallest box that fits all items based on volume and weight
     *
     * @param array $items Cart items
     * @return array|null Selected box or null if none fit
     */
    public static function get_box_for_items( $items ) {
        $boxes = self::get_boxes();

        // Check for incompatible items (requires tube)
        $requires_tube = false;
        $ships_separately = array();

        foreach ( $items as $item ) {
            $product_id = isset( $item['product_id'] ) ? $item['product_id'] : $item['data']->get_id();
            
            if ( get_post_meta( $product_id, '_requires_tube', true ) ) {
                $requires_tube = true;
            }

            if ( get_post_meta( $product_id, '_ships_separately', true ) ) {
                $ships_separately[] = $product_id;
            }
        }

        // If requires tube, only consider tube boxes
        if ( $requires_tube ) {
            return self::get_box_by_type( $boxes, 'tube' );
        }

        // Calculate total weight and volume needed
        $total_weight = 0;
        $total_volume = 0;

        foreach ( $items as $item ) {
            $product = isset( $item['data'] ) ? $item['data'] : wc_get_product( $item['product_id'] );
            $quantity = isset( $item['quantity'] ) ? $item['quantity'] : 1;

            if ( $product ) {
                $weight = (float) $product->get_weight();
                $total_weight += $weight * $quantity;

                // Estimate volume from product dimensions if available
                $length = (float) $product->get_length();
                $width = (float) $product->get_width();
                $height = (float) $product->get_height();

                if ( $length && $width && $height ) {
                    $total_volume += ( $length * $width * $height ) * $quantity;
                }
            }
        }

        // Filter active boxes and sort by volume (smallest first)
        $active_boxes = array_filter( $boxes, function( $box ) {
            return isset( $box['status'] ) && $box['status'] === 'active';
        });

        usort( $active_boxes, function( $a, $b ) {
            $vol_a = $a['outer_dimensions']['length'] * $a['outer_dimensions']['width'] * $a['outer_dimensions']['height'];
            $vol_b = $b['outer_dimensions']['length'] * $b['outer_dimensions']['width'] * $b['outer_dimensions']['height'];
            return $vol_a <=> $vol_b;
        });

        // Find smallest box that fits
        foreach ( $active_boxes as $box ) {
            // Skip tubes for regular items
            if ( isset( $box['type'] ) && $box['type'] === 'tube' && ! $requires_tube ) {
                continue;
            }

            // Check weight capacity
            if ( $total_weight > $box['max_weight'] ) {
                continue;
            }

            // Check volume capacity (if we have volume data)
            if ( $total_volume > 0 ) {
                $box_volume = $box['outer_dimensions']['length'] * $box['outer_dimensions']['width'] * $box['outer_dimensions']['height'];
                if ( $total_volume > $box_volume ) {
                    continue;
                }
            }

            // This box fits!
            return $box;
        }

        // No box fits - return largest box or null
        return ! empty( $active_boxes ) ? end( $active_boxes ) : null;
    }

    /**
     * Get box by type
     *
     * @param array  $boxes Box array
     * @param string $type  Box type (envelope, box, tube)
     * @return array|null First box matching type or null
     */
    public static function get_box_by_type( $boxes, $type ) {
        foreach ( $boxes as $box ) {
            if ( isset( $box['type'] ) && $box['type'] === $type && $box['status'] === 'active' ) {
                return $box;
            }
        }

        return null;
    }

    /**
     * Calculate dimensional weight
     *
     * @param array $box Box definition with outer_dimensions
     * @return float Dimensional weight in pounds
     */
    public static function calculate_dim_weight( $box ) {
        if ( ! isset( $box['outer_dimensions'] ) ) {
            return 0;
        }

        $dims = $box['outer_dimensions'];
        $dim_weight = ( $dims['length'] * $dims['width'] * $dims['height'] ) / apply_filters( 'em_dim_weight_divisor', self::DIM_WEIGHT_DIVISOR );

        return round( $dim_weight, 2 );
    }

    /**
     * Get billable weight (greater of actual or dimensional)
     *
     * @param float $actual_weight Actual weight in pounds
     * @param array $box           Box definition
     * @return float Billable weight in pounds
     */
    public static function get_billable_weight( $actual_weight, $box ) {
        $dim_weight = self::calculate_dim_weight( $box );
        return max( $actual_weight, $dim_weight );
    }

    /**
     * Calculate volume of box
     *
     * @param array $dimensions Dimensions array (length, width, height)
     * @return float Volume in cubic inches
     */
    public static function calculate_volume( $dimensions ) {
        if ( ! isset( $dimensions['length'] ) || ! isset( $dimensions['width'] ) || ! isset( $dimensions['height'] ) ) {
            return 0;
        }

        return $dimensions['length'] * $dimensions['width'] * $dimensions['height'];
    }

    /**
     * Check if items are incompatible (need separate packages)
     *
     * @param array $items Cart items
     * @return array Array of incompatible item groups
     */
    public static function get_incompatible_items( $items ) {
        $tube_items = array();
        $regular_items = array();
        $separate_items = array();

        foreach ( $items as $item ) {
            $product_id = isset( $item['product_id'] ) ? $item['product_id'] : $item['data']->get_id();

            if ( get_post_meta( $product_id, '_requires_tube', true ) ) {
                $tube_items[] = $item;
            } elseif ( get_post_meta( $product_id, '_ships_separately', true ) ) {
                $separate_items[] = array( $item );
            } else {
                $regular_items[] = $item;
            }
        }

        $groups = array();

        if ( ! empty( $tube_items ) ) {
            $groups[] = $tube_items;
        }

        if ( ! empty( $regular_items ) ) {
            $groups[] = $regular_items;
        }

        // Each "ships separately" item gets its own group
        foreach ( $separate_items as $separate_group ) {
            $groups[] = $separate_group;
        }

        return $groups;
    }

    /**
     * Generate unique box ID
     *
     * @param string $name Box name
     * @return string Unique box ID
     */
    public static function generate_box_id( $name ) {
        $id = sanitize_title( $name );
        $boxes = self::get_boxes();
        $counter = 1;
        $original_id = $id;

        // Ensure unique ID
        while ( self::box_id_exists( $id, $boxes ) ) {
            $id = $original_id . '-' . $counter;
            $counter++;
        }

        return $id;
    }

    /**
     * Check if box ID exists
     *
     * @param string $box_id Box ID
     * @param array  $boxes  Boxes array
     * @return bool True if exists
     */
    private static function box_id_exists( $box_id, $boxes ) {
        foreach ( $boxes as $box ) {
            if ( $box['id'] === $box_id ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get box types
     *
     * @return array Available box types
     */
    public static function get_box_types() {
        return apply_filters( 'em_box_types', array(
            'envelope' => __( 'Padded Envelope', 'epic-marks-shipping' ),
            'box'      => __( 'Box', 'epic-marks-shipping' ),
            'tube'     => __( 'Tube', 'epic-marks-shipping' ),
        ) );
    }
}
