<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @param $assign_taxes
 * @param $tx_name
 * @param $pid
 * @param $import_id
 * @return array
 */
function pmxi_wp_all_import_set_post_terms($assign_taxes, $tx_name, $pid, $import_id){

    // Handle category taxonomy and other taxonomies with default terms
    if ($tx_name == 'category' || get_option('default_term_' . $tx_name, false)) {

        $default_tt_id = pmxi_get_default_term_taxonomy_id($tx_name);

        // Get import record to check update mode
        $import = new PMXI_Import_Record();
        $import->getById($import_id);

        // Check if we're in "add_new" mode
        if (!$import->isEmpty() &&
            isset($import->options['update_categories_logic']) &&
            $import->options['update_categories_logic'] == 'add_new') {

            // In "add_new" mode, get existing terms
            $existing_terms = wp_get_object_terms($pid, $tx_name, array('fields' => 'tt_ids'));

            if (!is_wp_error($existing_terms)) {

                if (!empty($assign_taxes)) {
                    // We have terms to assign (may include default + new terms)
                    if (!empty($existing_terms)) {
                        // Check if existing terms are ONLY the default term
                        if (count($existing_terms) == 1 && $default_tt_id && in_array($default_tt_id, $existing_terms)) {
                            // Only default term exists, remove it from assign_taxes if present
                            $filtered_taxes = array_diff($assign_taxes, array($default_tt_id));
                            if (!empty($filtered_taxes)) {
                                return array_values($filtered_taxes); // Re-index array
                            } else {
                                // Only default term in assign_taxes, keep existing
                                return $existing_terms;
                            }
                        } else {
                            // Has real terms, use assign_taxes as-is (already merged by import logic)
                            return $assign_taxes;
                        }
                    } else {
                        // No existing terms, just use assign_taxes
                        return $assign_taxes;
                    }
                } else {
                    // No new terms to import, keep existing terms
                    return $existing_terms;
                }
            }
        }

        // Not in "add_new" mode, or no existing terms - add default if no terms assigned
        if (empty($assign_taxes) && $default_tt_id) {
            $assign_taxes[] = $default_tt_id;
        }
    }

    return $assign_taxes;
}

/**
 * Resolve a taxonomy's configured default term to a term_taxonomy_id.
 *
 * $assign_taxes carries term_taxonomy_ids — associate_terms() writes them straight
 * into term_relationships — while WordPress stores default terms as term_ids, so
 * the raw option value can't be used in their place.
 *
 * @param string $tx_name
 * @return int term_taxonomy_id, or 0 when the taxonomy has no resolvable default.
 */
function pmxi_get_default_term_taxonomy_id($tx_name){

    if ($tx_name == 'category') {
        $default_term_id = (int) get_option('default_category', 0);
    } elseif ($tx_name == 'product_cat') {
        $default_term_id = (int) get_option('default_product_cat', 0);
    } else {
        $default_term_id = (int) get_option('default_term_' . $tx_name, 0);
    }

    if ($default_term_id) {
        $term = get_term($default_term_id, $tx_name);
        if ( ! empty($term) and ! is_wp_error($term) ) {
            return (int) $term->term_taxonomy_id;
        }
        // WooCommerce seeds default_product_cat with a term_taxonomy_id on install but
        // stores a term_id when "Make default" is used, so either form can be present.
        $term = get_term_by('term_taxonomy_id', $default_term_id, $tx_name);
        if ( ! empty($term) and ! is_wp_error($term) ) {
            return (int) $term->term_taxonomy_id;
        }
    }

    // No default configured, or it points at a deleted term. Fall back to
    // Uncategorized so posts aren't left with no category at all.
    if ($tx_name == 'category') {
        $term = is_exists_term('uncategorized', $tx_name, 0);
        if ( ! empty($term) and ! is_wp_error($term) ) {
            return (int) $term['term_taxonomy_id'];
        }
    }

    return 0;
}
