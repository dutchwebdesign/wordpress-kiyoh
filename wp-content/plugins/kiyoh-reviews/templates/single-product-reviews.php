<?php
global $product;
$kiyoh_reviews = Kiyoh::get_product_review_data($product->get_id());

$count = $product->get_review_count(); ?>
<h2>
    <?php
    if ( $count ) {
        printf(esc_html__( '%s reviews for %s', 'kiyoh' ), esc_html( $count ), get_the_title());
    } else {
        esc_html_e('Reviews', 'kiyoh');
    }
    ?>
</h2>
<?php if($kiyoh_reviews) :?>
    <div class="comments alignfull">
    <?php foreach ($kiyoh_reviews as $kiyoh_review) :
        $rating = $kiyoh_review['rating']; ?>
        <div class="comment">
            <div class="star-rating" role="img"><span style="width:<?php echo ( ( ($rating/2) / 5 ) * 100 ) ?>%"><?php echo sprintf( esc_html__( 'Rated %s out of 5', 'kiyoh' ), '<strong class="rating">' . esc_html( $rating ) . '</strong>' ); ?></span></div>
            <p class="meta">
                <strong><?php echo $kiyoh_review['reviewAuthor'] ?></strong>
                <span>–</span>
                <span><?php echo date_i18n(get_option('date_format'), strtotime($kiyoh_review['dateSince'])); ?></span>
            </p>
            <p><?php echo $kiyoh_review['oneliner'] ?> -
            <?php echo $kiyoh_review['description'] ?></p>
        </div>
    <?php endforeach ?>
    </div>
<?php endif ?>