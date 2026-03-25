<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CM_Nav_Walker extends Walker_Nav_Menu {

    public function start_lvl( &$output, $depth = 0, $args = null ) {
        $output .= '<ul class="cm-nav-dropdown">';
    }

    public function end_lvl( &$output, $depth = 0, $args = null ) {
        $output .= '</ul>';
    }

    public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
        $classes = implode( ' ', $item->classes ?? array() );
        $active  = in_array( 'current-menu-item', $item->classes ?? array() );

        $output .= sprintf(
            '<li class="cm-nav-item %s %s">',
            esc_attr( $classes ),
            $active ? 'is-active' : ''
        );

        $output .= sprintf(
            '<a href="%s" class="cm-nav-link %s" %s>%s</a>',
            esc_url( $item->url ),
            $active ? 'is-active' : '',
            $item->target ? 'target="_blank" rel="noopener"' : '',
            esc_html( $item->title )
        );
    }

    public function end_el( &$output, $item, $depth = 0, $args = null ) {
        $output .= '</li>';
    }
}
