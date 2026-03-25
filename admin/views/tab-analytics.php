<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Feature gate
if ( ! CMCalc_License::can( 'analytics' ) ) : ?>
<div class="cmcalc-upgrade-gate">
    <div class="cmcalc-upgrade-gate-inner">
        <div class="cmcalc-upgrade-icon">📊</div>
        <h3>Analytics Dashboard</h3>
        <p>Krijg inzicht in uw boekingen, omzet, populaire diensten en klantgedrag.<br>Beschikbaar vanaf het <strong>Pro</strong> abonnement.</p>
        <a href="?page=cmcalc&tab=licentie" class="button button-primary button-hero">Upgrade naar Pro</a>
    </div>
</div>
<?php return; endif;

// ─── Datumbereik ──────────────────────────────────────────────────────────────
$period    = sanitize_key( $_GET['period'] ?? '30' );
$valid_p   = array( '7', '30', '90', '365' );
if ( ! in_array( $period, $valid_p ) ) $period = '30';

$date_from = date( 'Y-m-d', strtotime( "-{$period} days" ) );
$date_to   = date( 'Y-m-d' );

// ─── Data ophalen ─────────────────────────────────────────────────────────────
$all_boekingen = get_posts( array(
    'post_type'      => 'boeking',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
    'date_query'     => array( array( 'after' => $date_from, 'before' => $date_to, 'inclusive' => true ) ),
) );

$totaal_boekingen = count( $all_boekingen );
$totaal_omzet     = 0;
$dienst_counts    = array();
$status_counts    = array( 'nieuw' => 0, 'bevestigd' => 0, 'gepland' => 0, 'afgerond' => 0, 'geannuleerd' => 0 );
$dagelijks        = array(); // date => omzet
$gem_waarde       = 0;

foreach ( $all_boekingen as $b ) {
    $total   = floatval( get_post_meta( $b->ID, '_cm_total', true ) );
    $status  = get_post_meta( $b->ID, '_cm_status', true ) ?: 'nieuw';
    $dienst  = get_post_meta( $b->ID, '_cm_service_name', true ) ?: 'Onbekend';
    $dag     = get_the_date( 'Y-m-d', $b );

    $totaal_omzet += $total;
    $dienst_counts[$dienst] = ( $dienst_counts[$dienst] ?? 0 ) + 1;

    if ( isset( $status_counts[$status] ) ) {
        $status_counts[$status]++;
    } else {
        $status_counts[$status] = 1;
    }

    if ( ! isset( $dagelijks[$dag] ) ) $dagelijks[$dag] = 0;
    $dagelijks[$dag] += $total;
}

if ( $totaal_boekingen > 0 ) $gem_waarde = $totaal_omzet / $totaal_boekingen;

// Vorige periode voor vergelijking
$prev_from = date( 'Y-m-d', strtotime( "-" . ($period*2) . " days" ) );
$prev_to   = date( 'Y-m-d', strtotime( "-{$period} days" ) );
$prev_boek = get_posts( array(
    'post_type'      => 'boeking',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
    'date_query'     => array( array( 'after' => $prev_from, 'before' => $prev_to, 'inclusive' => true ) ),
) );
$prev_count = count( $prev_boek );
$prev_omzet = array_sum( array_map( fn($b) => floatval(get_post_meta($b->ID,'_cm_total',true)), $prev_boek ) );

$delta_boek  = $prev_count  > 0 ? round( (($totaal_boekingen - $prev_count)  / $prev_count) * 100 ) : null;
$delta_omzet = $prev_omzet  > 0 ? round( (($totaal_omzet    - $prev_omzet)   / $prev_omzet)  * 100 ) : null;

// Grafiekdata voorbereiden (dag-voor-dag laatste 30 punten)
$chart_labels = array();
$chart_data   = array();
for ( $i = min(intval($period),30)-1; $i >= 0; $i-- ) {
    $d = date( 'Y-m-d', strtotime( "-{$i} days" ) );
    $chart_labels[] = date( 'd M', strtotime($d) );
    $chart_data[]   = round( $dagelijks[$d] ?? 0, 2 );
}

arsort( $dienst_counts );
$top_diensten = array_slice( $dienst_counts, 0, 5, true );

// Werkgebied verdeling
$werkgebied_counts = array();
foreach ( $all_boekingen as $b ) {
    $wg = get_post_meta( $b->ID, '_cm_city', true ) ?: 'Onbekend';
    $werkgebied_counts[$wg] = ($werkgebied_counts[$wg] ?? 0) + 1;
}
arsort($werkgebied_counts);
$top_wg = array_slice($werkgebied_counts, 0, 6, true);
?>

<div class="cmcalc-analytics">

    <!-- Header + periode filter -->
    <div class="cmcalc-section-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
        <div>
            <h3 style="margin:0;">Analytics Dashboard</h3>
            <p style="color:var(--cmcalc-text-light);margin:4px 0 0;"><?php echo date_i18n('d F Y', strtotime($date_from)); ?> – <?php echo date_i18n('d F Y', strtotime($date_to)); ?></p>
        </div>
        <div class="cmcalc-period-tabs">
            <?php foreach ( array('7'=>'7 dagen','30'=>'30 dagen','90'=>'3 maanden','365'=>'Jaar') as $p => $label ) : ?>
            <a href="?page=cmcalc&tab=analytics&period=<?php echo $p; ?>"
               class="cmcalc-period-tab <?php echo $period === $p ? 'active' : ''; ?>">
                <?php echo $label; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- KPI cards -->
    <div class="cmcalc-analytics-kpis">

        <div class="cmcalc-kpi-card">
            <div class="cmcalc-kpi-icon" style="background:linear-gradient(135deg,#3b82f6,#6366f1)">📦</div>
            <div class="cmcalc-kpi-body">
                <div class="cmcalc-kpi-value"><?php echo number_format($totaal_boekingen); ?></div>
                <div class="cmcalc-kpi-label">Boekingen</div>
                <?php if ( $delta_boek !== null ) : ?>
                <div class="cmcalc-kpi-delta <?php echo $delta_boek >= 0 ? 'positive' : 'negative'; ?>">
                    <?php echo $delta_boek >= 0 ? '↑' : '↓'; ?> <?php echo abs($delta_boek); ?>% vs. vorige periode
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="cmcalc-kpi-card">
            <div class="cmcalc-kpi-icon" style="background:linear-gradient(135deg,#10b981,#06b6d4)">💶</div>
            <div class="cmcalc-kpi-body">
                <div class="cmcalc-kpi-value">&euro;<?php echo number_format($totaal_omzet, 2, ',', '.'); ?></div>
                <div class="cmcalc-kpi-label">Totale omzet</div>
                <?php if ( $delta_omzet !== null ) : ?>
                <div class="cmcalc-kpi-delta <?php echo $delta_omzet >= 0 ? 'positive' : 'negative'; ?>">
                    <?php echo $delta_omzet >= 0 ? '↑' : '↓'; ?> <?php echo abs($delta_omzet); ?>% vs. vorige periode
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="cmcalc-kpi-card">
            <div class="cmcalc-kpi-icon" style="background:linear-gradient(135deg,#f59e0b,#ef4444)">📈</div>
            <div class="cmcalc-kpi-body">
                <div class="cmcalc-kpi-value">&euro;<?php echo number_format($gem_waarde, 2, ',', '.'); ?></div>
                <div class="cmcalc-kpi-label">Gem. orderwaarde</div>
            </div>
        </div>

        <div class="cmcalc-kpi-card">
            <div class="cmcalc-kpi-icon" style="background:linear-gradient(135deg,#8b5cf6,#ec4899)">✅</div>
            <div class="cmcalc-kpi-body">
                <?php $conv = $totaal_boekingen > 0 ? round(($status_counts['afgerond']/$totaal_boekingen)*100) : 0; ?>
                <div class="cmcalc-kpi-value"><?php echo $conv; ?>%</div>
                <div class="cmcalc-kpi-label">Conversieratio afgerond</div>
            </div>
        </div>

    </div>

    <!-- Omzet grafiek -->
    <div class="cmcalc-analytics-row" style="margin-top:24px;">
        <div class="cmcalc-analytics-card cmcalc-analytics-card--wide">
            <div class="cmcalc-analytics-card-header">
                <h4>Omzet over tijd</h4>
                <span class="cmcalc-analytics-card-sub">Laatste <?php echo min(intval($period),30); ?> dagen</span>
            </div>
            <div style="position:relative;height:260px;">
                <canvas id="cmcalcRevenueChart"></canvas>
            </div>
        </div>

        <div class="cmcalc-analytics-card">
            <div class="cmcalc-analytics-card-header">
                <h4>Status verdeling</h4>
            </div>
            <div style="position:relative;height:200px;margin-bottom:16px;">
                <canvas id="cmcalcStatusChart"></canvas>
            </div>
            <div class="cmcalc-analytics-legend">
                <?php
                $status_colors = array(
                    'nieuw'       => '#6366f1',
                    'bevestigd'   => '#3b82f6',
                    'gepland'     => '#f59e0b',
                    'afgerond'    => '#10b981',
                    'geannuleerd' => '#ef4444',
                );
                foreach ( $status_counts as $s => $c ) : if (!$c) continue; ?>
                <div class="cmcalc-analytics-legend-item">
                    <span class="cmcalc-analytics-legend-dot" style="background:<?php echo $status_colors[$s] ?? '#94a3b8'; ?>"></span>
                    <?php echo ucfirst($s); ?> (<?php echo $c; ?>)
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Diensten + werkgebieden -->
    <div class="cmcalc-analytics-row" style="margin-top:20px;">

        <div class="cmcalc-analytics-card">
            <div class="cmcalc-analytics-card-header"><h4>Top diensten</h4></div>
            <?php if ( $top_diensten ) : ?>
            <div class="cmcalc-analytics-bars">
                <?php
                $max_d = max($top_diensten);
                foreach ( $top_diensten as $naam => $count ) :
                    $pct = $max_d > 0 ? round(($count/$max_d)*100) : 0;
                ?>
                <div class="cmcalc-bar-row">
                    <div class="cmcalc-bar-label"><?php echo esc_html($naam); ?></div>
                    <div class="cmcalc-bar-track">
                        <div class="cmcalc-bar-fill" style="width:<?php echo $pct; ?>%;background:linear-gradient(90deg,#3b82f6,#6366f1)"></div>
                    </div>
                    <div class="cmcalc-bar-value"><?php echo $count; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else : ?>
            <p style="color:var(--cmcalc-text-light);padding:20px 0;">Geen data beschikbaar.</p>
            <?php endif; ?>
        </div>

        <div class="cmcalc-analytics-card">
            <div class="cmcalc-analytics-card-header"><h4>Top werkgebieden</h4></div>
            <?php if ( $top_wg ) : ?>
            <div class="cmcalc-analytics-bars">
                <?php
                $max_wg = max($top_wg);
                foreach ( $top_wg as $wg => $count ) :
                    $pct = $max_wg > 0 ? round(($count/$max_wg)*100) : 0;
                ?>
                <div class="cmcalc-bar-row">
                    <div class="cmcalc-bar-label"><?php echo esc_html($wg); ?></div>
                    <div class="cmcalc-bar-track">
                        <div class="cmcalc-bar-fill" style="width:<?php echo $pct; ?>%;background:linear-gradient(90deg,#10b981,#06b6d4)"></div>
                    </div>
                    <div class="cmcalc-bar-value"><?php echo $count; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else : ?>
            <p style="color:var(--cmcalc-text-light);padding:20px 0;">Geen data beschikbaar.</p>
            <?php endif; ?>
        </div>

        <!-- Recente boekingen tabel -->
        <div class="cmcalc-analytics-card cmcalc-analytics-card--wide">
            <div class="cmcalc-analytics-card-header">
                <h4>Recente boekingen</h4>
                <a href="?page=cmcalc&tab=boekingen" class="cmcalc-analytics-card-link">Alles bekijken →</a>
            </div>
            <?php $recent = array_slice($all_boekingen, 0, 8); ?>
            <?php if ($recent) : ?>
            <table class="cmcalc-analytics-table">
                <thead><tr><th>ID</th><th>Naam</th><th>Dienst</th><th>Status</th><th>Bedrag</th><th>Datum</th></tr></thead>
                <tbody>
                <?php foreach ($recent as $b) :
                    $status = get_post_meta($b->ID,'_cm_status',true) ?: 'nieuw';
                    $naam   = trim(get_post_meta($b->ID,'_cm_name',true) ?: get_post_meta($b->ID,'_cm_first_name',true).' '.get_post_meta($b->ID,'_cm_last_name',true));
                    $dienst = get_post_meta($b->ID,'_cm_service_name',true) ?: '—';
                    $total  = floatval(get_post_meta($b->ID,'_cm_total',true));
                ?>
                <tr>
                    <td><code>#<?php echo $b->ID; ?></code></td>
                    <td><?php echo esc_html($naam ?: '—'); ?></td>
                    <td><?php echo esc_html($dienst); ?></td>
                    <td><span class="cmcalc-status-badge cmcalc-status-badge--<?php echo esc_attr($status); ?>"><?php echo ucfirst($status); ?></span></td>
                    <td>&euro;<?php echo number_format($total,2,',','.'); ?></td>
                    <td><?php echo get_the_date('d-m-Y',$b); ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else : ?>
            <p style="color:var(--cmcalc-text-light);padding:20px 0;">Geen boekingen in deze periode.</p>
            <?php endif; ?>
        </div>

    </div>

</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function(){
    const isDark  = document.body.classList.contains('cmcalc-dark') ||
                    getComputedStyle(document.documentElement).getPropertyValue('--cmcalc-bg').trim() === '#0f1117';
    const gridCol = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
    const textCol = isDark ? '#9ca3af' : '#6b7280';

    // Omzet lijn grafiek
    const revenueCtx = document.getElementById('cmcalcRevenueChart');
    if (revenueCtx) {
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [{
                    label: 'Omzet (€)',
                    data: <?php echo json_encode($chart_data); ?>,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99,102,241,0.12)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#6366f1',
                    pointRadius: 3,
                    pointHoverRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: {
                        ticks: { color: textCol, maxTicksLimit: 8 },
                        grid: { color: gridCol }
                    },
                    y: {
                        ticks: {
                            color: textCol,
                            callback: v => '€' + v.toLocaleString('nl-NL')
                        },
                        grid: { color: gridCol },
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Status donut
    const statusCtx = document.getElementById('cmcalcStatusChart');
    if (statusCtx) {
        const statusData = <?php echo json_encode(array_values($status_counts)); ?>;
        const statusLabels = <?php echo json_encode(array_keys($status_counts)); ?>;
        if (statusData.some(v => v > 0)) {
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: statusLabels.map(s => s.charAt(0).toUpperCase() + s.slice(1)),
                    datasets: [{
                        data: statusData,
                        backgroundColor: ['#6366f1','#3b82f6','#f59e0b','#10b981','#ef4444'],
                        borderWidth: 0,
                        hoverOffset: 6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => ` ${ctx.label}: ${ctx.raw} (${Math.round(ctx.raw/ctx.chart.data.datasets[0].data.reduce((a,b)=>a+b,0)*100)}%)`
                            }
                        }
                    }
                }
            });
        }
    }
})();
</script>
