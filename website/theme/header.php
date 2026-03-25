<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#000000">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<!-- Custom cursor -->
<div class="cm-cursor" id="cmCursor">
    <div class="cm-cursor-dot"></div>
    <div class="cm-cursor-ring"></div>
</div>

<!-- Noise overlay -->
<div class="cm-noise" aria-hidden="true"></div>

<!-- Navigation -->
<header class="cm-header" id="cmHeader">
    <div class="cm-header-inner">

        <!-- Logo -->
        <a href="<?php echo esc_url( home_url('/') ); ?>" class="cm-logo">
            <?php if ( has_custom_logo() ) :
                the_custom_logo();
            else : ?>
            <span class="cm-logo-text">
                <span class="cm-logo-clean">Clean</span><span class="cm-logo-masterzz">masterzz</span>
            </span>
            <?php endif; ?>
        </a>

        <!-- Nav links -->
        <nav class="cm-nav" id="cmNav" aria-label="Hoofdmenu">
            <?php wp_nav_menu( array(
                'theme_location' => 'primary',
                'container'      => false,
                'menu_class'     => 'cm-nav-list',
                'walker'         => new CM_Nav_Walker(),
                'fallback_cb'    => function() {
                    echo '<ul class="cm-nav-list">
                        <li class="cm-nav-item"><a href="#features" class="cm-nav-link">Features</a></li>
                        <li class="cm-nav-item"><a href="' . esc_url(home_url('/prijzen')) . '" class="cm-nav-link">Prijzen</a></li>
                        <li class="cm-nav-item"><a href="' . esc_url(home_url('/demo')) . '" class="cm-nav-link">Demo</a></li>
                    </ul>';
                },
            ) ); ?>
        </nav>

        <!-- CTA -->
        <div class="cm-header-cta">
            <a href="<?php echo esc_url( home_url('/prijzen') ); ?>" class="cm-btn cm-btn--ghost">Prijzen</a>
            <a href="<?php echo esc_url( home_url('/demo') ); ?>" class="cm-btn cm-btn--primary cm-btn--magnetic">
                <span>Gratis proberen</span>
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
        </div>

        <!-- Mobile hamburger -->
        <button class="cm-hamburger" id="cmHamburger" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>

    </div>
</header>

<main class="cm-main" id="cmMain">
