<?php
/**
 * Template de detalle de desarrollo estilo Terra Meridiana
 * Variables esperadas: $title, $location, $price_from, $price_to, $reference, $property_type, $photos[], $features[], $description_html, $units[]
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= esc_html($title) ?> – <?= esc_html($location) ?></title>
  <link rel="canonical" href="<?= esc_url(get_permalink()) ?>">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<main class="ld-main" role="main">
  <header class="ld-header">
    <h1 class="ld-title"><?= esc_html($title) ?></h1>
    <div class="ld-location"><?= esc_html($location) ?></div>
    <div class="ld-price-range">
      <?php if ($price_from || $price_to): ?>
        Desde <?= esc_html($price_from) ?> <?= $price_to ? 'hasta ' . esc_html($price_to) : '' ?>
      <?php else: ?> Consultar precio <?php endif; ?>
    </div>
    <div class="ld-meta">
      <span class="ld-ref">Ref. <?= esc_html($reference) ?></span>
      <span class="ld-type"><?= esc_html($property_type) ?></span>
    </div>
  </header>
  <section class="ld-gallery">
    <?php if (!empty($photos)): ?>
      <?php foreach ($photos as $i => $img): ?>
        <?php if ($i === 0): ?><link rel="preload" as="image" href="<?= esc_url($img) ?>" /><?php endif; ?>
        <img src="<?= esc_url($img) ?>" alt="Foto <?= $i+1 ?> de <?= esc_attr($title) ?>" loading="lazy" decoding="async">
      <?php endforeach; ?>
    <?php endif; ?>
  </section>
  <?php if (!empty($features)): ?>
    <section>
      <h2>Amenities</h2>
      <ul class="ld-amenities">
        <?php foreach ($features as $f): ?>
          <li><?= esc_html($f) ?></li>
        <?php endforeach; ?>
      </ul>
    </section>
  <?php endif; ?>
  <section class="ld-description">
    <?= wp_kses_post($description_html) ?>
  </section>
  <?php if (!empty($units)): ?>
    <section class="ld-units">
      <h2>Viviendas disponibles en esta promoción</h2>
      <div class="ld-units-grid">
        <?php foreach ($units as $u): ?>
          <article class="ld-unit-card">
            <h3><?= esc_html($u['title']) ?></h3>
            <div class="ld-unit-meta">
              <span><?= esc_html($u['beds']) ?> dorm</span>
              <span><?= esc_html($u['baths']) ?> baños</span>
              <span><?= esc_html($u['built']) ?> m² const.</span>
              <?php if(!empty($u['plot'])): ?><span><?= esc_html($u['plot']) ?> m² parcela</span><?php endif; ?>
            </div>
            <div class="ld-unit-price"><?= esc_html($u['price_label'] ?? 'Consultar precio') ?></div>
            <a class="ld-unit-cta" href="<?= esc_url($u['detail_url']) ?>" aria-label="Ver detalles de <?= esc_attr($u['title']) ?>">Ver detalles</a>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</main>
<?php wp_footer(); ?>
</body>
</html>
