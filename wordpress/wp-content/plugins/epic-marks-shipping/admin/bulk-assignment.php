<?php
/**
 * Bulk Product Assignment Tool
 *
 * Handles bulk assignment of products to shipping profiles
 *
 * @package Epic_Marks_Shipping
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render bulk assignment UI (integrated into Routing tab)
 */
function em_render_bulk_assignment_section() {
    $all_profiles = EM_Shipping_Profile::get_all();
    $unassigned_count = EM_Profile_Manager::get_unassigned_products( true );
    ?>
    <div class="em-bulk-assignment-section">
        <h3><?php esc_html_e( 'Bulk Product Assignment', 'epic-marks-shipping' ); ?></h3>
        <p class="description">
            <?php esc_html_e( 'Assign multiple products to a profile at once based on product tags.', 'epic-marks-shipping' ); ?>
        </p>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="em_bulk_tag"><?php esc_html_e( 'Product Tag', 'epic-marks-shipping' ); ?></label>
                </th>
                <td>
                    <select id="em_bulk_tag" name="em_bulk_tag" class="regular-text">
                        <option value=""><?php esc_html_e( '-- Select Tag --', 'epic-marks-shipping' ); ?></option>
                        <option value="SSAW-App"><?php esc_html_e( 'SSAW-App', 'epic-marks-shipping' ); ?></option>
                        <option value="available-in-store"><?php esc_html_e( 'available-in-store', 'epic-marks-shipping' ); ?></option>
                        <?php
                        // Get all product tags
                        $tags = get_terms( array(
                            'taxonomy' => 'product_tag',
                            'hide_empty' => true,
                            'number' => 50, // Limit to top 50 tags
                        ) );
                        if ( ! is_wp_error( $tags ) ) {
                            foreach ( $tags as $tag ) {
                                if ( ! in_array( $tag->name, array( 'SSAW-App', 'available-in-store' ), true ) ) {
                                    echo '<option value="' . esc_attr( $tag->name ) . '">' . esc_html( $tag->name ) . ' (' . $tag->count . ')</option>';
                                }
                            }
                        }
                        ?>
                    </select>
                    <p class="description">
                        <?php esc_html_e( 'Select a product tag to filter products for assignment.', 'epic-marks-shipping' ); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="em_bulk_profile"><?php esc_html_e( 'Assign to Profile', 'epic-marks-shipping' ); ?></label>
                </th>
                <td>
                    <select id="em_bulk_profile" name="em_bulk_profile" class="regular-text">
                        <option value=""><?php esc_html_e( '-- Select Profile --', 'epic-marks-shipping' ); ?></option>
                        <?php foreach ( $all_profiles as $profile_id => $profile ) : ?>
                            <option value="<?php echo esc_attr( $profile_id ); ?>">
                                <?php echo esc_html( $profile['name'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php esc_html_e( 'All products with the selected tag will be assigned to this profile.', 'epic-marks-shipping' ); ?>
                    </p>
                </td>
            </tr>
        </table>

        <p>
            <button type="button" id="em_start_bulk_assignment" class="button button-primary">
                <?php esc_html_e( 'Start Bulk Assignment', 'epic-marks-shipping' ); ?>
            </button>
        </p>

        <div id="em_bulk_progress" style="display: none; margin-top: 20px;">
            <div class="em-progress-bar" style="background: #f0f0f0; border: 1px solid #ccc; border-radius: 3px; height: 30px; position: relative; overflow: hidden;">
                <div id="em_progress_bar_fill" style="background: #2271b1; height: 100%; width: 0; transition: width 0.3s;"></div>
                <div id="em_progress_text" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-weight: bold; color: #333;">0%</div>
            </div>
            <p id="em_progress_status" style="margin-top: 10px;"></p>
        </div>

        <div id="em_bulk_result" style="margin-top: 20px;"></div>

        <hr style="margin: 30px 0;">

        <h4><?php esc_html_e( 'Unassigned Products', 'epic-marks-shipping' ); ?></h4>
        <p>
            <?php
            printf(
                esc_html__( 'There are %s products without a shipping profile assigned.', 'epic-marks-shipping' ),
                '<strong>' . number_format( $unassigned_count ) . '</strong>'
            );
            ?>
        </p>
        <p class="description">
            <?php esc_html_e( 'Products without a profile will fall back to tag-based routing.', 'epic-marks-shipping' ); ?>
        </p>
    </div>
    <?php
}

/**
 * AJAX handler: Get product count by tag
 */
function em_ajax_get_tag_product_count() {
    check_ajax_referer( 'em_bulk_assignment_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'epic-marks-shipping' ) ) );
    }

    $tag = sanitize_text_field( $_POST['tag'] ?? '' );

    if ( empty( $tag ) ) {
        wp_send_json_error( array( 'message' => __( 'Tag is required', 'epic-marks-shipping' ) ) );
    }

    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'tax_query' => array(
            array(
                'taxonomy' => 'product_tag',
                'field' => 'name',
                'terms' => $tag,
            ),
        ),
    );

    $products = get_posts( $args );

    wp_send_json_success( array(
        'count' => count( $products ),
        'tag' => $tag
    ) );
}
add_action( 'wp_ajax_em_get_tag_product_count', 'em_ajax_get_tag_product_count' );

/**
 * AJAX handler: Start bulk assignment
 */
function em_ajax_bulk_assign_products() {
    check_ajax_referer( 'em_bulk_assignment_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'epic-marks-shipping' ) ) );
    }

    $tag = sanitize_text_field( $_POST['tag'] ?? '' );
    $profile_id = sanitize_text_field( $_POST['profile_id'] ?? '' );
    $batch_size = absint( $_POST['batch_size'] ?? 100 );
    $offset = absint( $_POST['offset'] ?? 0 );

    if ( empty( $tag ) || empty( $profile_id ) ) {
        wp_send_json_error( array( 'message' => __( 'Tag and profile are required', 'epic-marks-shipping' ) ) );
    }

    // Verify profile exists
    if ( ! EM_Shipping_Profile::exists( $profile_id ) ) {
        wp_send_json_error( array( 'message' => __( 'Invalid profile ID', 'epic-marks-shipping' ) ) );
    }

    // Get products with this tag (paginated)
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => $batch_size,
        'offset' => $offset,
        'fields' => 'ids',
        'tax_query' => array(
            array(
                'taxonomy' => 'product_tag',
                'field' => 'name',
                'terms' => $tag,
            ),
        ),
    );

    $products = get_posts( $args );
    $assigned_count = 0;

    foreach ( $products as $product_id ) {
        if ( EM_Profile_Manager::assign_product_to_profile( $product_id, $profile_id ) ) {
            $assigned_count++;
        }
    }

    // Get total count for progress calculation
    $total_args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'tax_query' => array(
            array(
                'taxonomy' => 'product_tag',
                'field' => 'name',
                'terms' => $tag,
            ),
        ),
    );
    $total_products = get_posts( $total_args );
    $total_count = count( $total_products );

    $processed = $offset + $assigned_count;
    $remaining = max( 0, $total_count - $processed );
    $complete = ( $remaining === 0 );

    wp_send_json_success( array(
        'assigned' => $assigned_count,
        'processed' => $processed,
        'total' => $total_count,
        'remaining' => $remaining,
        'complete' => $complete,
        'percentage' => $total_count > 0 ? round( ( $processed / $total_count ) * 100 ) : 100
    ) );
}
add_action( 'wp_ajax_em_bulk_assign_products', 'em_ajax_bulk_assign_products' );
