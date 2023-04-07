<?php
/**
 * Page
 *
 * Template for a page.
 *
 * @package    Umzugmeister
 * @subpackage Pagetemplates
 * @author     Ilja Weber
 * @license    https://www.gnu.org/licenses/gpl-3.0.txt GNU/GPLv3
 * @link       https://www.dimitri-schneider.de
 * @since      1.0.0
 */

?>
<?php get_header(); ?>

<?php $staticbackground = get_field( 'singular_background' ); ?>
<?php if ( $staticbackground ) : ?>
	<div class="static">

		<div class="static__background-container">
			<div class="static__background-inner">
				<picture>
						<source media="(min-width: 1200px)" srcset="<?php echo esc_url( $staticbackground['url'] ); ?>">
						<img class="static__background" alt="<?php echo esc_attr( $staticbackground['alt'] ); ?>" src="<?php echo esc_url( $staticbackground['sizes']['large'] ); ?>">
				</picture>
			</div>
		</div>

	</div>
<?php endif; ?>

<div class="section">
	<div class="container">
		<div class="content">
			<?php if ( have_posts() ) : ?>
				<?php while ( have_posts() ) : ?>
					<?php the_post(); ?>
					<?php the_content(); ?>
				<?php endwhile; ?>
			<?php endif; ?>
		</div>
	</div>

	<div class="explanation-steps section">
		<div class="container">
			<div class="steps">
				<div class="box box--small">
					<i class="box__icon <?php the_field( 'success_tab_icon_1' ); ?>"></i>
					<div class="box__body">
						<?php the_field( 'success_tab_text_1' ); ?>
					</div>
				</div>
				<div class="box box--small">
					<i class="box__icon <?php the_field( 'success_tab_icon_2' ); ?>"></i>
					<div class="box__body">
						<?php the_field( 'success_tab_text_2' ); ?>
					</div>
				</div>
				<div class="box box--small">
					<i class="box__icon <?php the_field( 'success_tab_icon_3' ); ?>"></i>
					<div class="box__body">
						<?php the_field( 'success_tab_text_3' ); ?>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="container">
		<div class="content">
			<?php the_field( 'success_text_2' ); ?>
		</div>
	</div>
</div>

<?php if ( get_field( 'custom_code' ) ) : ?>
	<script>
	<?php the_field( 'custom_code' ); ?>
	</script>
<?php endif; ?>

<?php get_footer(); ?>
