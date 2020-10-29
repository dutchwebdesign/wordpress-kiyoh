<?php
$kiyoh_comapany_review = Kiyoh::get_company_review_data();
if($kiyoh_comapany_review) {
$maxrating = 10
?>
<link rel="stylesheet" href="<?php echo plugin_dir_url( __DIR__ ).'css/kiyoh-stylesheet.css'; ?>" />
<div class="kiyoh-shop-snippets">
    <div class="rating-box">
        <div class="rating" style="width:<?php echo floatval($kiyoh_comapany_review['averageRating'])*$maxrating; ?>%"></div>
    </div>
    <div class="kiyoh-schema" itemscope="itemscope" itemtype="http://schema.org/Organization">
        <meta itemprop="name" content="<?php echo $kiyoh_comapany_review['locationName'] ?>"/>
        <a href="<?php echo get_site_url() ?>" itemprop="url"
           style="display: none"><?php echo get_site_url() ?></a>
        <div itemprop="aggregateRating" itemscope="itemscope" itemtype="http://schema.org/AggregateRating">
            <meta itemprop="bestRating" content="<?php echo $maxrating ?>">
            <p>
                <a href="<?php echo $kiyoh_comapany_review['viewReviewUrl'] ?>" target="_blank" class="kiyoh-link">
                    <?php  printf(esc_html__( 'Rating %s out of %s, based on %s customer reviews', 'kiyoh' ), "<span
                        itemprop=\"ratingValue\">".$kiyoh_comapany_review['averageRating']."</span>", $maxrating, "<span
                        itemprop=\"ratingCount\">".$kiyoh_comapany_review['numberReviews']."</span>"); ?>
                </a>
            </p>
        </div>
    </div>
</div>
<?php } ?>